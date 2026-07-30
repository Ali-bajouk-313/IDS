<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Models\Category;


Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);



Route::middleware('auth:api')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/users', [AuthController::class, 'index']);

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
    Route::get('/tickets/{ticket}/history', [\App\Http\Controllers\TicketController::class, 'history']);
    Route::get('/tickets/{ticket}/comments', [\App\Http\Controllers\TicketCommentController::class, 'index']);
    Route::post('/tickets/{ticket}/comments', [\App\Http\Controllers\TicketCommentController::class, 'store']);
    Route::get('/tickets/{ticket}/internal-notes', [\App\Http\Controllers\TicketInternalNoteController::class, 'index']);
    Route::post('/tickets/{ticket}/internal-notes', [\App\Http\Controllers\TicketInternalNoteController::class, 'store']);
    Route::delete('/internal-notes/{id}', [\App\Http\Controllers\TicketInternalNoteController::class, 'destroy']);
    Route::get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index']);

});


Route::middleware(['auth:api', 'role:Admin'])->group(function () {

    Route::get('/admin/dashboard', function () {

        return response()->json([
            'message' => 'Welcome Admin',
            'access' => 'Full system access'
        ]);

    });

});



Route::middleware(['auth:api','role:Employee'])->group(function () {

    Route::get('/employee/dashboard', function () {

        return response()->json([
            'message' => 'Welcome Employee'
        ]);

    });

});

Route::middleware(['auth:api','role:IT Support'])->group(function () {

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