<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Services\ActivityLogService;
use App\Services\TicketHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TicketAttachmentController extends Controller
{
    public function __construct(
        protected TicketHistoryService $ticketHistoryService,
        protected ActivityLogService $activityLogService
    ) {
    }

    public function index($ticket)
    {
        $user = auth('api')->user();
        $ticketRecord = Ticket::find($ticket);

        if (!$ticketRecord) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        if (!$this->canViewAttachments($user, $ticketRecord)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $attachments = TicketAttachment::with('user:id,fullName')
            ->where('ticket_id', $ticketRecord->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function (TicketAttachment $attachment) {
                return [
                    'id' => $attachment->id,
                    'fileName' => $attachment->file_name,
                    'mimeType' => $attachment->mime_type,
                    'sizeBytes' => $attachment->size_bytes,
                    'uploadedAt' => $attachment->created_at,
                    'user' => [
                        'id' => $attachment->user?->id,
                        'fullName' => $attachment->user?->fullName,
                    ],
                    'downloadUrl' => route('ticket-attachments.download', ['attachment' => $attachment->id]),
                ];
            })
            ->values();

        return response()->json(['attachments' => $attachments]);
    }

    public function store(Request $request, $ticket)
    {
        $user = auth('api')->user();
        $ticketRecord = Ticket::find($ticket);

        if (!$ticketRecord) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        if (!$this->canUploadAttachments($user, $ticketRecord)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Allowed file types: images, documents, spreadsheets, csv, txt, zip/rar
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,csv,txt,zip,rar',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $storedName = uniqid('attachment_', true) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('ticket-attachments', $storedName, 'local');

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticketRecord->id,
            'user_id' => $user->id,
            'file_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        $this->ticketHistoryService->recordAttachmentAdded($ticketRecord, $user);
        $this->activityLogService->logAttachmentAdded($user, $ticketRecord, $request->ip());

        return response()->json([
            'message' => 'Attachment uploaded successfully',
            'attachment' => [
                'id' => $attachment->id,
                'fileName' => $attachment->file_name,
                'mimeType' => $attachment->mime_type,
                'sizeBytes' => $attachment->size_bytes,
                'uploadedAt' => $attachment->created_at,
                'downloadUrl' => route('ticket-attachments.download', ['attachment' => $attachment->id]),
            ],
        ], 201);
    }

    public function destroy($id)
    {
        $user = auth('api')->user();
        $attachment = TicketAttachment::find($id);

        if (!$attachment) {
            return response()->json(['message' => 'Attachment not found'], 404);
        }

        $ticketRecord = $attachment->ticket;

        if (!$this->canDeleteAttachment($user, $ticketRecord, $attachment)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        Storage::disk('local')->delete('ticket-attachments/' . $attachment->stored_name);
        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted successfully']);
    }

    public function download($id)
    {
        $user = auth('api')->user();
        $attachment = TicketAttachment::find($id);

        if (!$attachment) {
            return response()->json(['message' => 'Attachment not found'], 404);
        }

        $ticketRecord = $attachment->ticket;

        if (!$this->canViewAttachments($user, $ticketRecord)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $path = 'ticket-attachments/' . $attachment->stored_name;

        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'Attachment file not found'], 404);
        }

        return Storage::disk('local')->download($path, $attachment->file_name);
    }

    protected function canViewAttachments($user, Ticket $ticket): bool
    {
        return match ($user->role->roleName) {
            'Admin' => true,
            'Manager' => $ticket->createdBy === $user->id,
            'IT Support' => $ticket->assignedTo === $user->id,
            default => $ticket->createdBy === $user->id,
        };
    }

    protected function canUploadAttachments($user, Ticket $ticket): bool
    {
        return match ($user->role->roleName) {
            'Admin' => true,
            'IT Support' => $ticket->assignedTo === $user->id,
            'Employee' => $ticket->createdBy === $user->id,
            default => false,
        };
    }

    protected function canDeleteAttachment($user, ?Ticket $ticket, TicketAttachment $attachment): bool
    {
        return $user->role->roleName === 'Admin';
    }
}
