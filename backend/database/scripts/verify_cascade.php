<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $tn = 'T' . time();
    $ticketId = DB::table('tickets')->insertGetId([
        'ticketNumber' => $tn,
        'title' => 'Cascade Test Ticket',
        'description' => 'Created by automated verification script',
        'categoryId' => 5,
        'createdBy' => 1,
    ]);

    DB::table('ticketcomments')->insert([
        'ticketId' => $ticketId,
        'userId' => 2,
        'commentText' => 'Test comment',
        'createdAt' => date('Y-m-d H:i:s'),
    ]);

    DB::table('tickethistory')->insert([
        'ticketId' => $ticketId,
        'changedBy' => 1,
        'oldStatus' => 'Open',
        'newStatus' => 'In Progress',
        'comment' => 'Status change',
        'changedAt' => date('Y-m-d H:i:s'),
    ]);

    DB::table('ticket_attachments')->insert([
        'ticket_id' => $ticketId,
        'user_id' => 2,
        'file_name' => 'test.txt',
        'stored_name' => 'test_stored.txt',
        'mime_type' => 'text/plain',
        'size_bytes' => 123,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    $before = [
        'tickets' => DB::table('tickets')->where('id', $ticketId)->count(),
        'comments' => DB::table('ticketcomments')->where('ticketId', $ticketId)->count(),
        'history' => DB::table('tickethistory')->where('ticketId', $ticketId)->count(),
        'attachments' => DB::table('ticket_attachments')->where('ticket_id', $ticketId)->count(),
    ];

    // delete the ticket
    DB::table('tickets')->where('id', $ticketId)->delete();

    $after = [
        'tickets' => DB::table('tickets')->where('id', $ticketId)->count(),
        'comments' => DB::table('ticketcomments')->where('ticketId', $ticketId)->count(),
        'history' => DB::table('tickethistory')->where('ticketId', $ticketId)->count(),
        'attachments' => DB::table('ticket_attachments')->where('ticket_id', $ticketId)->count(),
    ];

    echo "TICKET_ID: " . $ticketId . PHP_EOL;
    echo "BEFORE: " . json_encode($before) . PHP_EOL;
    echo "AFTER: " . json_encode($after) . PHP_EOL;
    exit(0);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(2);
}
