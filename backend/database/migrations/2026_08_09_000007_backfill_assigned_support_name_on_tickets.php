<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tickets', 'assignedSupportName')) {
            return;
        }

        DB::table('tickets')
            ->join('users', 'tickets.assignedTo', '=', 'users.id')
            ->whereNull('tickets.assignedSupportName')
            ->update([
                'tickets.assignedSupportName' => DB::raw('users.fullName'),
            ]);
    }

    public function down(): void
    {
        // No rollback needed for data backfill migration.
    }
};
