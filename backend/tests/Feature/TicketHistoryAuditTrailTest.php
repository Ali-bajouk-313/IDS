<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketHistoryAuditTrailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('ticket_assignments');
        Schema::dropIfExists('ticketcomments');
        Schema::dropIfExists('tickethistory');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('categories');
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
            $table->timestamp('email_verified_at')->nullable();
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
            $table->string('oldStatus')->nullable();
            $table->string('newStatus')->nullable();
            $table->string('comment', 255)->nullable();
            $table->timestamp('changedAt')->nullable();
        });

        Schema::create('ticketcomments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('ticketId');
            $table->unsignedBigInteger('userId');
            $table->text('commentText');
            $table->timestamp('createdAt')->nullable();
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
    }

    public function test_ticket_creation_records_history(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee-create@example.com', 'Employee Create', 1);
        $category = Category::create(['categoryName' => 'Hardware']);

        $this->actingAs($employee, 'api');

        $response = $this->postJson('/api/tickets', [
            'title' => 'Laptop issue',
            'description' => 'Cannot boot laptop',
            'categoryId' => $category->id,
            'priority' => 'High',
        ]);

        $response->assertStatus(201);

        $ticketId = $response->json('ticket.id');

        $this->assertDatabaseHas('tickethistory', [
            'ticketId' => $ticketId,
            'changedBy' => $employee->id,
            'comment' => 'Ticket created',
            'newStatus' => 'Open',
        ]);
    }

    public function test_assignment_records_history(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'admin-assign@example.com', 'Admin Assign', 1);
        $employee = $this->makeUser($roles['Employee'], 'employee-assign@example.com', 'Employee Assign', 1);
        $support = $this->makeUser($roles['IT Support'], 'support-assign@example.com', 'John Smith', 1);

        $ticket = $this->makeTicket('TICKET-20001', $employee->id, null, 'Open');

        $this->actingAs($admin, 'api');

        $response = $this->postJson("/api/tickets/{$ticket->id}/assign", [
            'assigned_to' => $support->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tickethistory', [
            'ticketId' => $ticket->id,
            'changedBy' => $admin->id,
            'comment' => 'Assigned to IT Support: John Smith',
        ]);
    }

    public function test_reassignment_is_forbidden_when_ticket_is_already_assigned(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'admin-reassign@example.com', 'Admin Reassign', 1);
        $employee = $this->makeUser($roles['Employee'], 'employee-reassign@example.com', 'Employee Reassign', 1);
        $supportOne = $this->makeUser($roles['IT Support'], 'support1-reassign@example.com', 'John Smith', 1);
        $supportTwo = $this->makeUser($roles['IT Support'], 'support2-reassign@example.com', 'Ali Hassan', 1);

        $ticket = $this->makeTicket('TICKET-20002', $employee->id, $supportOne->id, 'Assigned');

        $this->actingAs($admin, 'api');

        $response = $this->postJson("/api/tickets/{$ticket->id}/assign", [
            'assigned_to' => $supportTwo->id,
        ]);

        $response->assertStatus(409);
    }

    public function test_unassignment_records_history(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee-unassign@example.com', 'Employee Unassign', 1);
        $support = $this->makeUser($roles['IT Support'], 'support-unassign@example.com', 'Support Unassign', 1);

        $ticket = $this->makeTicket('TICKET-20003', $employee->id, $support->id, 'Assigned');

        $this->actingAs($support, 'api');

        $response = $this->postJson("/api/tickets/{$ticket->id}/unassign", [
            'reason' => 'Escalating back to admin for reassignment',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tickethistory', [
            'ticketId' => $ticket->id,
            'changedBy' => $support->id,
            'comment' => 'Support Unassign returned ticket to Admin. Reason: Escalating back to admin for reassignment',
        ]);
    }

    public function test_status_change_records_history(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'admin-status@example.com', 'Admin Status', 1);
        $employee = $this->makeUser($roles['Employee'], 'employee-status@example.com', 'Employee Status', 1);

        $ticket = $this->makeTicket('TICKET-20004', $employee->id, null, 'Assigned');

        $this->actingAs($admin, 'api');

        $response = $this->putJson("/api/tickets/{$ticket->id}/status", [
            'status' => 'In Progress',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tickethistory', [
            'ticketId' => $ticket->id,
            'changedBy' => $admin->id,
            'oldStatus' => 'Assigned',
            'newStatus' => 'In Progress',
            'comment' => 'Status changed',
        ]);
    }

    public function test_comment_records_history(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee-comment@example.com', 'Employee Comment', 1);

        $ticket = $this->makeTicket('TICKET-20005', $employee->id, null, 'Open');

        $this->actingAs($employee, 'api');

        $response = $this->postJson("/api/tickets/{$ticket->id}/comments", [
            'commentText' => 'Please prioritize this issue.',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('tickethistory', [
            'ticketId' => $ticket->id,
            'changedBy' => $employee->id,
            'comment' => 'Comment added',
        ]);
    }

    public function test_priority_change_records_history(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'admin-priority@example.com', 'Admin Priority', 1);
        $employee = $this->makeUser($roles['Employee'], 'employee-priority@example.com', 'Employee Priority', 1);

        $ticket = $this->makeTicket('TICKET-20006', $employee->id, null, 'Open', 'Medium');

        $this->actingAs($admin, 'api');

        $response = $this->putJson("/api/tickets/{$ticket->id}", [
            'priority' => 'High',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tickethistory', [
            'ticketId' => $ticket->id,
            'changedBy' => $admin->id,
            'comment' => 'Priority changed from Medium to High',
        ]);
    }

    public function test_history_endpoint_authorization(): void
    {
        $roles = $this->seedRoles();

        $admin = $this->makeUser($roles['Admin'], 'admin-history@example.com', 'Admin History', 99);
        $managerDept1 = $this->makeUser($roles['Manager'], 'manager1-history@example.com', 'Manager Dept One', 1);
        $managerDept2 = $this->makeUser($roles['Manager'], 'manager2-history@example.com', 'Manager Dept Two', 2);

        $employeeOwner = $this->makeUser($roles['Employee'], 'employee-owner-history@example.com', 'Employee Owner', 1);
        $employeeOther = $this->makeUser($roles['Employee'], 'employee-other-history@example.com', 'Employee Other', 2);

        $supportAssigned = $this->makeUser($roles['IT Support'], 'support-assigned-history@example.com', 'Support Assigned', 1);
        $supportOther = $this->makeUser($roles['IT Support'], 'support-other-history@example.com', 'Support Other', 1);

        $ticket = $this->makeTicket('TICKET-20007', $employeeOwner->id, $supportAssigned->id, 'Assigned');

        DB::table('tickethistory')->insert([
            'ticketId' => $ticket->id,
            'changedBy' => $admin->id,
            'oldStatus' => 'Open',
            'newStatus' => 'Assigned',
            'comment' => 'Status changed',
            'changedAt' => now(),
        ]);

        $this->actingAs($admin, 'api')
            ->getJson("/api/tickets/{$ticket->id}/history")
            ->assertStatus(200)
            ->assertJsonStructure([
                'history' => [
                    [
                        'id',
                        'changedBy' => ['id', 'fullName'],
                        'oldStatus',
                        'newStatus',
                        'comment',
                        'changedAt',
                    ],
                ],
            ]);

        $this->actingAs($managerDept1, 'api')
            ->getJson("/api/tickets/{$ticket->id}/history")
            ->assertStatus(200);

        $this->actingAs($managerDept2, 'api')
            ->getJson("/api/tickets/{$ticket->id}/history")
            ->assertStatus(403);

        $this->actingAs($supportAssigned, 'api')
            ->getJson("/api/tickets/{$ticket->id}/history")
            ->assertStatus(200);

        $this->actingAs($supportOther, 'api')
            ->getJson("/api/tickets/{$ticket->id}/history")
            ->assertStatus(403);

        $this->actingAs($employeeOwner, 'api')
            ->getJson("/api/tickets/{$ticket->id}/history")
            ->assertStatus(200);

        $this->actingAs($employeeOther, 'api')
            ->getJson("/api/tickets/{$ticket->id}/history")
            ->assertStatus(403);
    }

    protected function seedRoles(): array
    {
        $employee = Role::create(['roleName' => 'Employee']);
        $support = Role::create(['roleName' => 'IT Support']);
        $manager = Role::create(['roleName' => 'Manager']);
        $admin = Role::create(['roleName' => 'Admin']);

        return [
            'Employee' => $employee,
            'IT Support' => $support,
            'Manager' => $manager,
            'Admin' => $admin,
        ];
    }

    protected function makeUser(Role $role, string $email, string $name, int $departmentId): User
    {
        return User::create([
            'roleId' => $role->id,
            'departmentId' => $departmentId,
            'fullName' => $name,
            'email' => $email,
            'password' => 'secret123',
            'status' => 'Active',
        ]);
    }

    protected function makeTicket(string $ticketNumber, int $createdBy, ?int $assignedTo, string $status = 'Open', string $priority = 'Medium'): Ticket
    {
        return Ticket::create([
            'ticketNumber' => $ticketNumber,
            'title' => 'Audit Ticket',
            'description' => 'Audit description',
            'categoryId' => 1,
            'createdBy' => $createdBy,
            'assignedTo' => $assignedTo,
            'priority' => $priority,
            'status' => $status,
        ]);
    }
}
