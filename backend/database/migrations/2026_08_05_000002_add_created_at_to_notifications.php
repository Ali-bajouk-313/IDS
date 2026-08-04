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

        if (! Schema::hasColumn('notifications', 'created_at')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->timestamp('created_at')->nullable();
            });

            try {
                DB::statement('UPDATE notifications SET created_at = createdAt WHERE created_at IS NULL');
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

        if (Schema::hasColumn('notifications', 'created_at')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('created_at');
            });
        }
    }
};
