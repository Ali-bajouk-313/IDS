<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'assignedTo')) {
                $table->integer('assignedTo')->nullable()->after('createdBy');

                $table->foreign('assignedTo')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });

        Schema::create('ticket_assignments', function (Blueprint $table) {

            $table->id();

            // Match existing database IDs (INT)
            $table->integer('ticket_id');

            $table->integer('old_assigned_to')->nullable();

            $table->integer('new_assigned_to')->nullable();

            $table->integer('assigned_by')->nullable();

            $table->string('action');

            $table->timestamps();


            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->cascadeOnDelete();

            $table->foreign('old_assigned_to')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('new_assigned_to')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('assigned_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('ticket_assignments');

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'assignedTo')) {

                $table->dropForeign(['assignedTo']);

                $table->dropColumn('assignedTo');
            }
        });
    }
};