<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('ticket_assignments');
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

        Schema::create('ticket_assignments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('old_assigned_to')->nullable();
            $table->unsignedBigInteger('new_assigned_to')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->string('action');
            $table->timestamps();
        });

        Schema::create('tickethistory', function ($table) {
            $table->id();
            $table->unsignedBigInteger('ticketId');
            $table->unsignedBigInteger('changedBy');
            $table->string('oldStatus')->nullable();
            $table->string('newStatus')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('changedAt')->nullable();
        });
    }

    public function test_admin_can_assign_ticket_to_it_support(): void
    {
        $adminRole = Role::create(['roleName' => 'Admin', 'description' => 'Administrator']);
        $itSupportRole = Role::create(['roleName' => 'IT Support', 'description' => 'Support staff']);
        $employeeRole = Role::create(['roleName' => 'Employee', 'description' => 'Employee']);

        $admin = User::create([
            'roleId' => $adminRole->id,
            'fullName' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $support = User::create([
            'roleId' => $itSupportRole->id,
            'fullName' => 'Support Agent',
            'email' => 'support@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $employee = User::create([
            'roleId' => $employeeRole->id,
            'fullName' => 'Employee User',
            'email' => 'employee@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $category = \App\Models\Category::create([
            'categoryName' => 'Hardware',
        ]);

        $ticket = Ticket::create([
            'ticketNumber' => 'TICKET-00001',
            'title' => 'Printer issue',
            'description' => 'The printer is offline.',
            'categoryId' => $category->id,
            'createdBy' => $employee->id,
            'priority' => 'High',
            'status' => 'Open',
        ]);

        $this->actingAs($admin, 'api');

        $response = $this->postJson("/api/tickets/{$ticket->id}/assign", [
            'assigned_to' => $support->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('ticket.assignedTo', $support->id)
            ->assertJsonPath('ticket.status', 'Assigned');

        $this->assertDatabaseHas('ticket_assignments', [
            'ticket_id' => $ticket->id,
            'new_assigned_to' => $support->id,
            'assigned_by' => $admin->id,
            'action' => 'ASSIGNED',
        ]);
    }

    public function test_admin_cannot_assign_ticket_when_already_assigned(): void
    {
        $adminRole = Role::create(['roleName' => 'Admin', 'description' => 'Administrator']);
        $itSupportRole = Role::create(['roleName' => 'IT Support', 'description' => 'Support staff']);
        $employeeRole = Role::create(['roleName' => 'Employee', 'description' => 'Employee']);

        $admin = User::create([
            'roleId' => $adminRole->id,
            'fullName' => 'Admin User',
            'email' => 'admin2@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $supportOne = User::create([
            'roleId' => $itSupportRole->id,
            'fullName' => 'Support One',
            'email' => 'support-one@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $supportTwo = User::create([
            'roleId' => $itSupportRole->id,
            'fullName' => 'Support Two',
            'email' => 'support-two@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $employee = User::create([
            'roleId' => $employeeRole->id,
            'fullName' => 'Employee User',
            'email' => 'employee2@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $category = \App\Models\Category::create([
            'categoryName' => 'Hardware',
        ]);

        $ticket = Ticket::create([
            'ticketNumber' => 'TICKET-00002',
            'title' => 'Monitor issue',
            'description' => 'Monitor is flickering.',
            'categoryId' => $category->id,
            'createdBy' => $employee->id,
            'assignedTo' => $supportOne->id,
            'priority' => 'High',
            'status' => 'Assigned',
        ]);

        $this->actingAs($admin, 'api');

        $response = $this->postJson("/api/tickets/{$ticket->id}/assign", [
            'assigned_to' => $supportTwo->id,
        ]);

        $response->assertStatus(409);
    }

    public function test_only_assigned_it_support_can_unassign_ticket(): void
    {
        $adminRole = Role::create(['roleName' => 'Admin', 'description' => 'Administrator']);
        $itSupportRole = Role::create(['roleName' => 'IT Support', 'description' => 'Support staff']);
        $employeeRole = Role::create(['roleName' => 'Employee', 'description' => 'Employee']);

        $admin = User::create([
            'roleId' => $adminRole->id,
            'fullName' => 'Admin User',
            'email' => 'admin3@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $support = User::create([
            'roleId' => $itSupportRole->id,
            'fullName' => 'Support Agent',
            'email' => 'support3@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $employee = User::create([
            'roleId' => $employeeRole->id,
            'fullName' => 'Employee User',
            'email' => 'employee3@example.com',
            'password' => 'secret123',
            'status' => 'Active',
        ]);

        $category = \App\Models\Category::create([
            'categoryName' => 'Software',
        ]);

        $ticket = Ticket::create([
            'ticketNumber' => 'TICKET-00003',
            'title' => 'Email issue',
            'description' => 'Email sync failed.',
            'categoryId' => $category->id,
            'createdBy' => $employee->id,
            'assignedTo' => $support->id,
            'priority' => 'Medium',
            'status' => 'Assigned',
        ]);

        $this->actingAs($admin, 'api');
        $this->postJson("/api/tickets/{$ticket->id}/unassign")
            ->assertStatus(403);

        $this->actingAs($support, 'api');
        $this->postJson("/api/tickets/{$ticket->id}/unassign")
            ->assertStatus(200)
            ->assertJsonPath('ticket.assignedTo', null)
            ->assertJsonPath('ticket.status', 'Open');
    }
}
