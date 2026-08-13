<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Models\Category;


Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);



Route::middleware('auth:api')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'changePassword']);
    Route::get('/users', [AuthController::class, 'index']);
    Route::get('/users/{id}', [AuthController::class, 'showUser']);
    Route::put('/users/{id}', [AuthController::class, 'updateUser']);
    Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/categories', function () {
        return response()->json(['categories' => Category::all()]);
    });

    Route::get('/tickets', [\App\Http\Controllers\TicketController::class, 'index']);
    Route::post('/tickets', [\App\Http\Controllers\TicketController::class, 'store']);
    Route::get('/tickets/my-assigned', [\App\Http\Controllers\TicketController::class, 'myAssigned']);
    Route::put('/tickets/{id}/status', [\App\Http\Controllers\TicketController::class, 'updateStatus']);
    Route::get('/tickets/{id}', [\App\Http\Controllers\TicketController::class, 'show']);
    Route::put('/tickets/{id}', [\App\Http\Controllers\TicketController::class, 'update']);
    Route::delete('/tickets/{id}', [\App\Http\Controllers\TicketController::class, 'destroy']);
    Route::post('/tickets/{ticket}/assign', [\App\Http\Controllers\TicketController::class, 'assignTicket']);
    Route::post('/tickets/{ticket}/unassign', [\App\Http\Controllers\TicketController::class, 'unassignTicket']);
    Route::post('/tickets/{ticket}/return-to-admin', [\App\Http\Controllers\TicketController::class, 'returnToAdmin']);
    Route::get('/tickets/{ticket}/history', [\App\Http\Controllers\TicketController::class, 'history']);
    Route::get('/tickets/{ticket}/comments', [\App\Http\Controllers\TicketCommentController::class, 'index']);
    Route::post('/tickets/{ticket}/comments', [\App\Http\Controllers\TicketCommentController::class, 'store']);
    Route::get('/tickets/{ticket}/internal-notes', [\App\Http\Controllers\TicketInternalNoteController::class, 'index']);
    Route::post('/tickets/{ticket}/internal-notes', [\App\Http\Controllers\TicketInternalNoteController::class, 'store']);
    Route::delete('/internal-notes/{id}', [\App\Http\Controllers\TicketInternalNoteController::class, 'destroy']);
    Route::get('/tickets/{ticket}/attachments', [\App\Http\Controllers\TicketAttachmentController::class, 'index']);
    Route::post('/tickets/{ticket}/attachments', [\App\Http\Controllers\TicketAttachmentController::class, 'store']);
    Route::delete('/attachments/{id}', [\App\Http\Controllers\TicketAttachmentController::class, 'destroy']);
    Route::get('/attachments/{attachment}/download', [\App\Http\Controllers\TicketAttachmentController::class, 'download'])->name('ticket-attachments.download');
    Route::get('/tickets/{ticket}/ai/summary', [\App\Http\Controllers\AiTicketController::class, 'summary']);
    Route::get('/tickets/{ticket}/ai/priority', [\App\Http\Controllers\AiTicketController::class, 'priority']);
    Route::get('/tickets/{ticket}/ai/troubleshooting', [\App\Http\Controllers\AiTicketController::class, 'troubleshooting']);
    Route::middleware('role:Admin,Manager,IT Support')->post('/ai/knowledge-base/ask', [\App\Http\Controllers\KnowledgeBaseAiController::class, 'ask']);
    Route::post('/ai/chat', [\App\Http\Controllers\AiChatController::class, 'chat']);
    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index']);
    Route::get('/reports/export/pdf', [\App\Http\Controllers\ReportController::class, 'exportPdf']);
    Route::get('/reports/export/excel', [\App\Http\Controllers\ReportController::class, 'exportExcel']);
    Route::get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index']);
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);

});


Route::middleware(['auth:api', 'role:Admin'])->group(function () {

    Route::get('/dashboard/admin', [\App\Http\Controllers\DashboardController::class, 'admin']);

    Route::get('/admin/dashboard', function () {

        return response()->json([
            'message' => 'Welcome Admin',
            'access' => 'Full system access'
        ]);

    });

});



Route::middleware(['auth:api','role:Employee'])->group(function () {

    Route::get('/dashboard/employee', [\App\Http\Controllers\DashboardController::class, 'employee']);

    Route::get('/employee/dashboard', function () {

        return response()->json([
            'message' => 'Welcome Employee'
        ]);

    });

});

Route::middleware(['auth:api','role:IT Support'])->group(function () {

    Route::get('/dashboard/support', [\App\Http\Controllers\DashboardController::class, 'support']);

    Route::get('/support/dashboard', function () {

        return response()->json([
            'message' => 'Welcome IT Support',
            'access' => [
                'View assigned tickets',
                'Update ticket status',
                'Add comments',
                'Resolve tickets'
            ]
        ]);

    });

});


Route::middleware(['auth:api','role:Manager'])->group(function () {

    Route::get('/dashboard/manager', [\App\Http\Controllers\DashboardController::class, 'manager']);

    Route::get('/manager/dashboard', function () {

        return response()->json([
            'message' => 'Welcome Manager',
            'access' => [
                'View reports',
                'Monitor team performance',
                'View ticket statistics'
            ]
        ]);

    });

});


use Illuminate\Support\Facades\Mail;

Route::get('/test-email', function () {
    Mail::raw('This is a test email from HelpDeskPro.', function ($message) {
        $message->to('mohammadzaiter567@gmail.com')
                ->subject('HelpDeskPro Test Email');
    });

    return response()->json([
        'message' => 'Test email sent!'
    ]);
});


Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::get('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);