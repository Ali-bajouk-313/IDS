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

        if (! Schema::hasColumn('notifications', 'updated_at')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable();
            });

            try {
                // Prefer copying from createdAt if present, otherwise created_at
                DB::statement('UPDATE notifications SET updated_at = createdAt WHERE updated_at IS NULL AND createdAt IS NOT NULL');
                DB::statement('UPDATE notifications SET updated_at = created_at WHERE updated_at IS NULL AND created_at IS NOT NULL');
            } catch (\Exception $e) {
                // ignore
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        if (Schema::hasColumn('notifications', 'updated_at')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('updated_at');
            });
        }
    }
};
