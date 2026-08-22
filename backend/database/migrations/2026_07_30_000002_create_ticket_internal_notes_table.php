<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing tables may contain production data; only create the table when absent.
        if (Schema::hasTable('ticket_internal_notes')) {
            return;
        }

        Schema::create('ticket_internal_notes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id');

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
