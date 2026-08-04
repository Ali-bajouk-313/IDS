<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('tickethistory');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');

        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('roleName');
            $table->string('description')->nullable();
            $table->timestamp('createdAt')->nullable();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->unsignedBigInteger('roleId');
            $table->unsignedBigInteger('departmentId')->nullable();
            $table->string('fullName');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('status')->default('Active');
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
        });

        Schema::create('categories', function ($table) {
            $table->id();
            $table->string('categoryName');
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
        });

        Schema::create('tickets', function ($table) {
            $table->id();
            $table->string('ticketNumber')->unique();
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('categoryId')->nullable();
            $table->unsignedBigInteger('createdBy');
            $table->unsignedBigInteger('assignedTo')->nullable();
            $table->string('priority')->default('Medium');
            $table->string('status')->default('Open');
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
            $table->timestamp('closedAt')->nullable();
        });

        Schema::create('tickethistory', function ($table) {
            $table->id();
            $table->unsignedBigInteger('ticketId');
            $table->unsignedBigInteger('changedBy');
            $table->string('oldStatus');
            $table->string('newStatus');
            $table->text('comment')->nullable();
            $table->timestamp('changedAt')->nullable();
        });
    }

    public function test_employee_cannot_change_ticket_status(): void
    {
        $employeeRole = Role::create(['roleName' => 'Employee']);
        $employee = User::create([
            'roleId' => $employeeRole->id,
            'fullName' => 'Employee One',
            'email' => 'employee@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $ticket = Ticket::create([
            'ticketNumber' => 'TICKET-00001',
            'title' => 'Printer issue',
            'description' => 'The printer is offline.',
            'categoryId' => 1,
            'createdBy' => $employee->id,
            'priority' => 'High',
            'status' => 'Open',
        ]);

        $this->actingAs($employee, 'api');

        $response = $this->putJson("/api/tickets/{$ticket->id}/status", ['status' => 'Closed']);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'You cannot perform this action');
    }

    public function test_it_support_can_transition_assigned_to_in_progress(): void
    {
        $supportRole = Role::create(['roleName' => 'IT Support']);
        $support = User::create([
            'roleId' => $supportRole->id,
            'fullName' => 'Support One',
            'email' => 'support@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $employeeRole = Role::create(['roleName' => 'Employee']);
        $employee = User::create([
            'roleId' => $employeeRole->id,
            'fullName' => 'Employee Two',
            'email' => 'employee2@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $ticket = Ticket::create([
            'ticketNumber' => 'TICKET-00002',
            'title' => 'VPN issue',
            'description' => 'VPN not connecting.',
            'categoryId' => 1,
            'createdBy' => $employee->id,
            'assignedTo' => $support->id,
            'priority' => 'High',
            'status' => 'Assigned',
        ]);

        $this->actingAs($support, 'api');

        $response = $this->putJson("/api/tickets/{$ticket->id}/status", ['status' => 'In Progress']);

        $response->assertStatus(200)
            ->assertJsonPath('ticket.status', 'In Progress');

        $this->assertDatabaseHas('tickethistory', [
            'ticketId' => $ticket->id,
            'newStatus' => 'In Progress',
            'oldStatus' => 'Assigned',
        ]);
    }

    public function test_admin_cannot_edit_in_progress_ticket(): void
    {
        $adminRole = Role::create(['roleName' => 'Admin']);
        $admin = User::create([
            'roleId' => $adminRole->id,
            'fullName' => 'Admin Two',
            'email' => 'admin2@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $employeeRole = Role::create(['roleName' => 'Employee']);
        $employee = User::create([
            'roleId' => $employeeRole->id,
            'fullName' => 'Employee Four',
            'email' => 'employee4@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $ticket = Ticket::create([
            'ticketNumber' => 'TICKET-00004',
            'title' => 'Network outage',
            'description' => 'Intermittent connectivity.
',
            'categoryId' => 1,
            'createdBy' => $employee->id,
            'assignedTo' => null,
            'priority' => 'High',
            'status' => 'In Progress',
        ]);

        $this->actingAs($admin, 'api');

        $response = $this->putJson("/api/tickets/{$ticket->id}", [
            'title' => 'Network outage updated',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'In Progress tickets cannot be edited');
    }

    public function test_admin_can_close_resolved_ticket(): void
    {
        $adminRole = Role::create(['roleName' => 'Admin']);
        $admin = User::create([
            'roleId' => $adminRole->id,
            'fullName' => 'Admin One',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $employeeRole = Role::create(['roleName' => 'Employee']);
        $employee = User::create([
            'roleId' => $employeeRole->id,
            'fullName' => 'Employee Three',
            'email' => 'employee3@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $ticket = Ticket::create([
            'ticketNumber' => 'TICKET-00003',
            'title' => 'Email issue',
            'description' => 'Emails are delayed.',
            'categoryId' => 1,
            'createdBy' => $employee->id,
            'priority' => 'Medium',
            'status' => 'Resolved',
        ]);

        $this->actingAs($admin, 'api');

        $response = $this->putJson("/api/tickets/{$ticket->id}/status", ['status' => 'Closed']);

        $response->assertStatus(200)
            ->assertJsonPath('ticket.status', 'Closed');
    }
}
