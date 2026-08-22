<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        $this->copyLegacyColumns();
        $this->validateNotificationData();
        $this->dropUserForeignKeys();

        Schema::table('notifications', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->change();
            $table->integer('user_id')->nullable(false)->change();
            $table->string('type')->nullable(false)->change();
            $table->string('title')->nullable(false)->change();
            $table->text('message')->nullable(false)->change();
        });

        $this->dropLegacyColumns();
        $this->addUserForeignKey();
    }

    public function down(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        $this->dropUserForeignKeys();

        Schema::table('notifications', function (Blueprint $table): void {
            if (!Schema::hasColumn('notifications', 'createdAt')) {
                $table->timestamp('createdAt')->nullable();
            }

            if (!Schema::hasColumn('notifications', 'isRead')) {
                $table->boolean('isRead')->default(false);
            }
        });

        DB::statement('UPDATE notifications SET createdAt = created_at WHERE createdAt IS NULL');
        DB::statement('UPDATE notifications SET isRead = read_at IS NOT NULL');

        $this->addUserForeignKey();
    }

    private function copyLegacyColumns(): void
    {
        if (Schema::hasColumn('notifications', 'userId')) {
            DB::statement('UPDATE notifications SET user_id = userId WHERE user_id IS NULL AND userId IS NOT NULL');
        }

        if (Schema::hasColumn('notifications', 'createdAt')) {
            DB::statement('UPDATE notifications SET created_at = createdAt WHERE created_at IS NULL AND createdAt IS NOT NULL');
        }

        if (Schema::hasColumn('notifications', 'isRead')) {
            DB::statement('UPDATE notifications SET read_at = COALESCE(read_at, created_at, CURRENT_TIMESTAMP) WHERE isRead = 1');
        }
    }

    private function validateNotificationData(): void
    {
        $invalid = DB::table('notifications')
            ->where(function ($query): void {
                $query->whereNull('user_id')
                    ->orWhereNull('title')
                    ->orWhereNull('message')
                    ->orWhereNull('type');
            })
            ->count();

        if ($invalid > 0) {
            throw new RuntimeException('Cannot normalize notifications: required values are missing.');
        }

        $orphans = DB::table('notifications')
            ->leftJoin('users', 'notifications.user_id', '=', 'users.id')
            ->whereNull('users.id')
            ->count();

        if ($orphans > 0) {
            throw new RuntimeException('Cannot normalize notifications: one or more user_id values do not reference users.');
        }
    }

    private function dropUserForeignKeys(): void
    {
        $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'notifications')
            ->where('COLUMN_NAME', 'user_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME');

        foreach ($foreignKeys as $foreignKey) {
            DB::statement('ALTER TABLE `notifications` DROP FOREIGN KEY `'.str_replace('`', '``', $foreignKey).'`');
        }
    }

    private function addUserForeignKey(): void
    {
        $exists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'notifications')
            ->where('COLUMN_NAME', 'user_id')
            ->where('REFERENCED_TABLE_NAME', 'users')
            ->exists();

        if (!$exists) {
            Schema::table('notifications', function (Blueprint $table): void {
                $table->foreign('user_id', 'notifications_user_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        }
    }

    private function dropLegacyColumns(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            foreach (['userId', 'createdAt', 'isRead'] as $column) {
                if (Schema::hasColumn('notifications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
