<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // If a previous failed migration left a partial table, recreate it cleanly.
        if (Schema::hasTable('ticket_internal_notes')) {
            Schema::dropIfExists('ticket_internal_notes');
        }

        Schema::create('ticket_internal_notes', function (Blueprint $table) {
            $table->id();
            // Match existing legacy schema where user and ticket IDs are INT.
            $table->integer('ticket_id');
            $table->integer('user_id');
            $table->text('note');
            $table->timestamps();

            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_internal_notes');
    }
};
