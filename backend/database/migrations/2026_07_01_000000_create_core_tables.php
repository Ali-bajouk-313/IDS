<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $createTable = static function (string $name, \Closure $definition): void {
            if (!Schema::hasTable($name)) {
                Schema::create($name, $definition);
            }
        };

        $createTable('roles', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('roleName', 50)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamp('createdAt')->nullable()->useCurrent();
        });

        $createTable('users', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('roleId');
            $table->string('fullName', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamp('createdAt')->nullable()->useCurrent();
            $table->timestamp('updatedAt')->nullable()->useCurrent();
            $table->foreign('roleId')->references('id')->on('roles');
        });

        $createTable('categories', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('categoryName', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->integer('slaHours')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamp('createdAt')->nullable()->useCurrent();
        });

        $createTable('tickets', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('ticketNumber', 20)->unique();
            $table->string('title', 150);
            $table->text('description');
            $table->integer('categoryId');
            $table->integer('createdBy');
            $table->integer('assignedTo')->nullable();
            $table->enum('priority', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');
            $table->enum('status', ['Open', 'Assigned', 'In Progress', 'Resolved', 'Closed'])->default('Open');
            $table->timestamp('createdAt')->nullable()->useCurrent();
            $table->timestamp('updatedAt')->nullable()->useCurrent();
            $table->timestamp('closedAt')->nullable();
            $table->foreign('categoryId')->references('id')->on('categories');
            $table->foreign('createdBy')->references('id')->on('users');
            $table->foreign('assignedTo')->references('id')->on('users')->nullOnDelete();
        });

        $createTable('ticketcomments', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('ticketId');
            $table->integer('userId');
            $table->text('commentText');
            $table->timestamp('createdAt')->nullable()->useCurrent();
            $table->foreign('ticketId')->references('id')->on('tickets')->cascadeOnDelete();
            $table->foreign('userId')->references('id')->on('users');
        });

        $createTable('tickethistory', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('ticketId');
            $table->integer('changedBy');
            $table->string('oldStatus', 50)->nullable();
            $table->string('newStatus', 50)->nullable();
            $table->string('comment', 255)->nullable();
            $table->timestamp('changedAt')->nullable()->useCurrent();
            $table->foreign('ticketId')->references('id')->on('tickets')->cascadeOnDelete();
            $table->foreign('changedBy')->references('id')->on('users');
        });

        $createTable('activitylogs', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('userId');
            $table->string('action', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('ipAddress', 50)->nullable();
            $table->timestamp('createdAt')->nullable()->useCurrent();
            $table->foreign('userId')->references('id')->on('users');
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
