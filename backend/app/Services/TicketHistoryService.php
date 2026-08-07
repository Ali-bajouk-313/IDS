<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TicketHistoryService
{
    public function recordCreated(Ticket $ticket, User $actor): void
    {
        $this->insert($ticket->id, $actor->id, null, $ticket->status, 'Ticket created');
    }

    public function recordAssigned(Ticket $ticket, User $actor, string $assignedToName, ?string $oldStatus = null): void
    {
        $this->insert($ticket->id, $actor->id, $oldStatus, $ticket->status, "Assigned to IT Support: {$assignedToName}");
    }

    public function recordReassigned(Ticket $ticket, User $actor, string $fromName, string $toName): void
    {
        $this->insert($ticket->id, $actor->id, $ticket->status, $ticket->status, "Reassigned from {$fromName} to {$toName}");
    }

    public function recordUnassigned(Ticket $ticket, User $actor, ?string $oldStatus = null): void
    {
        $this->insert($ticket->id, $actor->id, $oldStatus, $ticket->status, 'Ticket returned to assignment queue');
    }

    public function recordReturnedToAdmin(Ticket $ticket, User $actor, ?string $oldStatus, string $reason): void
    {
        $safeReason = trim($reason);
        $this->insert(
            $ticket->id,
            $actor->id,
            $oldStatus,
            $ticket->status,
            "{$actor->fullName} returned ticket to Admin. Reason: {$safeReason}"
        );
    }

    public function recordStatusChanged(Ticket $ticket, User $actor, string $oldStatus, string $newStatus): void
    {
        $this->insert($ticket->id, $actor->id, $oldStatus, $newStatus, 'Status changed');
    }

    public function recordPriorityChanged(Ticket $ticket, User $actor, string $oldPriority, string $newPriority): void
    {
        $this->insert($ticket->id, $actor->id, $ticket->status, $ticket->status, "Priority changed from {$oldPriority} to {$newPriority}");
    }

    public function recordCommentAdded(Ticket $ticket, User $actor): void
    {
        $this->insert($ticket->id, $actor->id, $ticket->status, $ticket->status, 'Comment added');
    }

    public function recordInternalNoteAdded(Ticket $ticket, User $actor): void
    {
        $this->insert($ticket->id, $actor->id, $ticket->status, $ticket->status, 'Internal note added');
    }

    public function recordAttachmentAdded(Ticket $ticket, User $actor): void
    {
        $this->insert($ticket->id, $actor->id, $ticket->status, $ticket->status, 'Attachment added');
    }

    public function recordUpdated(Ticket $ticket, User $actor): void
    {
        $this->insert($ticket->id, $actor->id, $ticket->status, $ticket->status, 'Ticket updated');
    }

    public function recordClosed(Ticket $ticket, User $actor, string $oldStatus): void
    {
        $this->insert($ticket->id, $actor->id, $oldStatus, $ticket->status, 'Ticket closed');
    }

    public function recordReopened(Ticket $ticket, User $actor, string $oldStatus): void
    {
        $this->insert($ticket->id, $actor->id, $oldStatus, $ticket->status, 'Ticket reopened');
    }

    protected function insert(int $ticketId, int $changedBy, ?string $oldStatus, ?string $newStatus, string $comment): void
    {
        DB::table('tickethistory')->insert([
            'ticketId' => $ticketId,
            'changedBy' => $changedBy,
            'oldStatus' => $oldStatus,
            'newStatus' => $newStatus,
            'comment' => $comment,
            'changedAt' => now(),
        ]);
    }
}
