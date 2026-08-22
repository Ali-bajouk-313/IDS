<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('roleName', 50)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamp('createdAt')->useCurrent();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('roleId')
                ->constrained('roles')
                ->cascadeOnDelete();

            $table->string('fullName', 100);
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->string('phone', 20)->nullable();

            $table->enum('status', ['Active', 'Inactive'])
                ->default('Active');

            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')
                ->useCurrent()
                ->useCurrentOnUpdate();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('categoryName', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->integer('slaHours')->nullable();

            $table->enum('status', ['Active', 'Inactive'])
                ->default('Active');

            $table->timestamp('createdAt')->useCurrent();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->string('ticketNumber', 20)->unique();
            $table->string('title', 150);
            $table->text('description');

            $table->foreignId('categoryId')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->foreignId('createdBy')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('assignedTo')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('assignedSupportName', 255)->nullable();

            $table->enum('priority', [
                'Low',
                'Medium',
                'High',
                'Critical'
            ])->default('Medium');

            $table->enum('status', [
                'Open',
                'Assigned',
                'In Progress',
                'Resolved',
                'Closed'
            ])->default('Open');

            $table->timestamp('createdAt')->useCurrent();

            $table->timestamp('updatedAt')
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->timestamp('closedAt')->nullable();
        });

        Schema::create('ticketcomments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticketId')
                ->constrained('tickets')
                ->cascadeOnDelete();

            $table->foreignId('userId')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('commentText');
            $table->timestamp('createdAt')->useCurrent();
        });

        Schema::create('tickethistory', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticketId')
                ->constrained('tickets')
                ->cascadeOnDelete();

            $table->foreignId('changedBy')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('oldStatus', 50)->nullable();
            $table->string('newStatus', 50)->nullable();
            $table->string('comment', 255)->nullable();

            $table->timestamp('changedAt')->useCurrent();
        });

        Schema::create('activitylogs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('userId')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('action', 100);
            $table->text('description')->nullable();
            $table->string('ipAddress', 50)->nullable();

            $table->timestamp('createdAt')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activitylogs');
        Schema::dropIfExists('tickethistory');
        Schema::dropIfExists('ticketcomments');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
