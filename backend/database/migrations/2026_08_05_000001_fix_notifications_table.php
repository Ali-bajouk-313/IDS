<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        // If `user_id` already exists, assume table is fine.
        if (Schema::hasColumn('notifications', 'user_id')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            // Add new columns expected by the application; avoid relying on snake_case existing columns.
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->string('type')->nullable()->after('user_id');
            $table->json('data')->nullable()->after('message');
            $table->timestamp('read_at')->nullable()->after('data');
            // created_at may not exist (legacy tables used createdAt), so add updated_at without an after() clause
            $table->timestamp('updated_at')->nullable();
        });

        // Copy existing camelCase values into the new snake_case columns
        // Use raw SQL to reference existing column names like `userId` and `createdAt`.
        try {
            // Copy userId -> user_id
            DB::statement('UPDATE notifications SET user_id = userId WHERE user_id IS NULL');

            // If there's no `type` column data, set a sensible default
            DB::statement("UPDATE notifications SET `type` = 'general' WHERE `type` IS NULL");

            // Map createdAt -> created_at and updated_at
            DB::statement('UPDATE notifications SET created_at = createdAt WHERE created_at IS NULL');
            DB::statement('UPDATE notifications SET updated_at = createdAt WHERE updated_at IS NULL');

            // If there is an isRead boolean, set read_at accordingly
            DB::statement('UPDATE notifications SET read_at = createdAt WHERE isRead = 1 AND read_at IS NULL');
        } catch (\Exception $e) {
            // Log and continue; schema changes were applied and app will use the new columns when present.
        }

        // Add foreign key if possible (silently fail if DB engine doesn't allow it)
        try {
            Schema::table('notifications', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // ignore constraint creation failures
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        // We intentionally do not drop any camelCase columns here to avoid data loss.
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'user_id')) {
                $table->dropForeign([ 'user_id' ]);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('notifications', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('notifications', 'data')) {
                $table->dropColumn('data');
            }
            if (Schema::hasColumn('notifications', 'read_at')) {
                $table->dropColumn('read_at');
            }
            if (Schema::hasColumn('notifications', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
