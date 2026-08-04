<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ticketcomments.ticketId -> tickets.id
        if (Schema::hasTable('ticketcomments') && Schema::hasColumn('ticketcomments', 'ticketId')) {
            // determine existing FK constraint name and drop it safely
            $fk = \DB::selectOne("SELECT CONSTRAINT_NAME as name FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ticketcomments' AND COLUMN_NAME = 'ticketId' AND REFERENCED_TABLE_NAME = 'tickets'");
            Schema::table('ticketcomments', function (Blueprint $table) use ($fk) {
                try {
                    if ($fk && !empty($fk->name)) {
                        $table->dropForeign($fk->name);
                    } else {
                        $table->dropForeign(['ticketId']);
                    }
                } catch (\Exception $e) {
                    // ignore
                }

                $table->foreign('ticketId')->references('id')->on('tickets')->onDelete('cascade');
            });
        }

        // tickethistory.ticketId -> tickets.id
        if (Schema::hasTable('tickethistory') && Schema::hasColumn('tickethistory', 'ticketId')) {
            $fk = \DB::selectOne("SELECT CONSTRAINT_NAME as name FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickethistory' AND COLUMN_NAME = 'ticketId' AND REFERENCED_TABLE_NAME = 'tickets'");
            Schema::table('tickethistory', function (Blueprint $table) use ($fk) {
                try {
                    if ($fk && !empty($fk->name)) {
                        $table->dropForeign($fk->name);
                    } else {
                        $table->dropForeign(['ticketId']);
                    }
                } catch (\Exception $e) {
                    // ignore
                }

                $table->foreign('ticketId')->references('id')->on('tickets')->onDelete('cascade');
            });
        }

        // ticket_attachments.ticket_id -> tickets.id
        if (Schema::hasTable('ticket_attachments') && Schema::hasColumn('ticket_attachments', 'ticket_id')) {
            Schema::table('ticket_attachments', function (Blueprint $table) {
                try {
                    $table->dropForeign(['ticket_id']);
                } catch (\Exception $e) {
                    // ignore
                }

                $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            });
        }
        // no legacy ticketattachments handling (not present in this schema)
    }

    public function down(): void
    {
        // reverse: drop cascade fks and recreate without cascade (if desired)
        if (Schema::hasTable('ticketcomments') && Schema::hasColumn('ticketcomments', 'ticketId')) {
            Schema::table('ticketcomments', function (Blueprint $table) {
                try { $table->dropForeign(['ticketId']); } catch (\Exception $e) {}
                $table->foreign('ticketId')->references('id')->on('tickets');
            });
        }

        if (Schema::hasTable('tickethistory') && Schema::hasColumn('tickethistory', 'ticketId')) {
            Schema::table('tickethistory', function (Blueprint $table) {
                try { $table->dropForeign(['ticketId']); } catch (\Exception $e) {}
                $table->foreign('ticketId')->references('id')->on('tickets');
            });
        }

        if (Schema::hasTable('ticket_attachments') && Schema::hasColumn('ticket_attachments', 'ticket_id')) {
            Schema::table('ticket_attachments', function (Blueprint $table) {
                try { $table->dropForeign(['ticket_id']); } catch (\Exception $e) {}
                $table->foreign('ticket_id')->references('id')->on('tickets');
            });
        }

        if (Schema::hasTable('ticketattachments') && Schema::hasColumn('ticketattachments', 'ticketId')) {
            Schema::table('ticketattachments', function (Blueprint $table) {
                try { $table->dropForeign(['ticketId']); } catch (\Exception $e) {}
                $table->foreign('ticketId')->references('id')->on('tickets');
            });
        }
    }
};
