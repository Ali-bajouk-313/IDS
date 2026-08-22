<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeCoreValues();
        $this->validateCoreValues();
        $this->changeCoreColumns();
        $this->alignAssignedToForeignKey();
        $this->alignNotificationPrimaryKey();
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            DB::statement("ALTER TABLE `users` MODIFY `status` ENUM('Active','Inactive') NULL DEFAULT 'Active'");
            DB::statement("ALTER TABLE `users` MODIFY `updatedAt` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }

        if (Schema::hasTable('categories')) {
            DB::statement("ALTER TABLE `categories` MODIFY `status` ENUM('Active','Inactive') NULL DEFAULT 'Active'");
        }

        if (Schema::hasTable('tickets')) {
            DB::statement("ALTER TABLE `tickets` MODIFY `priority` ENUM('Low','Medium','High','Critical') NULL DEFAULT 'Medium'");
            DB::statement("ALTER TABLE `tickets` MODIFY `status` ENUM('Open','Assigned','In Progress','Resolved','Closed') NULL DEFAULT 'Open'");
            DB::statement("ALTER TABLE `tickets` MODIFY `updatedAt` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }
    }

    private function normalizeCoreValues(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')->whereNull('status')->update(['status' => 'Active']);
        }

        if (Schema::hasTable('categories')) {
            DB::table('categories')->whereNull('status')->update(['status' => 'Active']);
        }

        if (Schema::hasTable('tickets')) {
            DB::table('tickets')->whereNull('priority')->update(['priority' => 'Medium']);
            DB::table('tickets')->whereNull('status')->update(['status' => 'Open']);
        }
    }

    private function validateCoreValues(): void
    {
        $invalid = 0;

        if (Schema::hasTable('users')) {
            $invalid += DB::table('users')->whereNull('status')->count();
        }

        if (Schema::hasTable('categories')) {
            $invalid += DB::table('categories')->whereNull('status')->count();
        }

        if (Schema::hasTable('tickets')) {
            $invalid += DB::table('tickets')->whereNull('priority')->orWhereNull('status')->count();

            $invalid += DB::table('tickets')
                ->leftJoin('users', 'tickets.assignedTo', '=', 'users.id')
                ->whereNotNull('tickets.assignedTo')
                ->whereNull('users.id')
                ->count();
        }

        if ($invalid > 0) {
            throw new RuntimeException('Cannot align core schema: invalid existing values remain.');
        }
    }

    private function changeCoreColumns(): void
    {
        if (Schema::hasTable('users')) {
            DB::statement("ALTER TABLE `users` MODIFY `status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'");
            DB::statement("ALTER TABLE `users` MODIFY `updatedAt` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
        }

        if (Schema::hasTable('categories')) {
            DB::statement("ALTER TABLE `categories` MODIFY `status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'");
        }

        if (Schema::hasTable('tickets')) {
            DB::statement("ALTER TABLE `tickets` MODIFY `priority` ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium'");
            DB::statement("ALTER TABLE `tickets` MODIFY `status` ENUM('Open','Assigned','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open'");
            DB::statement("ALTER TABLE `tickets` MODIFY `updatedAt` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
        }
    }

    private function alignAssignedToForeignKey(): void
    {
        if (!Schema::hasTable('tickets') || !Schema::hasColumn('tickets', 'assignedTo')) {
            return;
        }

        $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'tickets')
            ->where('COLUMN_NAME', 'assignedTo')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME');

        foreach ($foreignKeys as $foreignKey) {
            DB::statement('ALTER TABLE `tickets` DROP FOREIGN KEY `'.str_replace('`', '``', $foreignKey).'`');
        }

        Schema::table('tickets', function (Blueprint $table): void {
            $table->foreign('assignedTo', 'tickets_assignedto_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    private function alignNotificationPrimaryKey(): void
    {
        if (Schema::hasTable('notifications')) {
            DB::statement('ALTER TABLE `notifications` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        }
    }
};
