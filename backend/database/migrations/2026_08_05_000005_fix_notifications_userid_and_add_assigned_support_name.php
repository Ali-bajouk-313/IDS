<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        // If legacy userId exists, migrate values into user_id then drop FK and column
        if (Schema::hasColumn('notifications', 'userId')) {
            // copy values where user_id is null
            \DB::statement("UPDATE notifications SET user_id = userId WHERE user_id IS NULL AND userId IS NOT NULL");

            // drop foreign key if present
            $fk = \DB::selectOne("SELECT CONSTRAINT_NAME as name FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'userId' AND REFERENCED_TABLE_NAME = 'users'");
            // perform raw ALTER statements outside Blueprint to avoid Laravel naming transformations
            try {
                if ($fk && !empty($fk->name)) {
                    \DB::statement("ALTER TABLE `notifications` DROP FOREIGN KEY `" . $fk->name . "`");
                }
            } catch (\Exception $e) {
                // ignore
            }

            try {
                $idx = \DB::selectOne("SELECT INDEX_NAME as name FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'userId' LIMIT 1");
                if ($idx && !empty($idx->name)) {
                    \DB::statement("ALTER TABLE `notifications` DROP INDEX `" . $idx->name . "`");
                }
            } catch (\Exception $e) {
            }

            try {
                Schema::table('notifications', function (Blueprint $table) {
                    $table->dropColumn('userId');
                });
            } catch (\Exception $e) {
            }
        }

        // Add assigned_support_name for clearer notification text if not exists
        if (!Schema::hasColumn('notifications', 'assigned_support_name')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->string('assigned_support_name', 150)->nullable()->after('type');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'assigned_support_name')) {
                $table->dropColumn('assigned_support_name');
            }
            // We do not recreate legacy userId column on down migration to avoid accidental conflicts.
        });
    }
};
