<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'assignedSupportName')) {
                $table->string('assignedSupportName', 100)
                    ->nullable()
                    ->after('assignedTo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'assignedSupportName')) {
                $table->dropColumn('assignedSupportName');
            }
        });
    }
};
