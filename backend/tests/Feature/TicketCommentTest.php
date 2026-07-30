<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketCommentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        Schema::create('ticketcomments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('ticketId');
            $table->unsignedBigInteger('userId');
            $table->text('commentText');
            $table->timestamp('createdAt')->nullable();
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

    public function test_employee_comments_on_own_ticket_success(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee1@example.com', 'Employee One');

        $ticket = $this->makeTicket('TICKET-10001', $employee->id, null);

        $this->actingAs($employee, 'api');

        $response = $this->postJson("/api/tickets/{$ticket->id}/comments", [
            'commentText' => 'I added more details to this issue.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('comment.commentText', 'I added more details to this issue.')
            ->assertJsonPath('comment.user.fullName', 'Employee One');

        $this->assertDatabaseHas('ticketcomments', [
            'ticketId' => $ticket->id,
            'userId' => $employee->id,
            'commentText' => 'I added more details to this issue.',
        ]);

        $this->assertDatabaseHas('tickethistory', [
            'ticketId' => $ticket->id,
            'changedBy' => $employee->id,
            'comment' => 'Comment added',
        ]);
    }

    public function test_employee_comments_on_another_employee_ticket_forbidden(): void
    {
        $roles = $this->seedRoles();
        $employeeOne = $this->makeUser($roles['Employee'], 'employee2@example.com', 'Employee Two');
        $employeeTwo = $this->makeUser($roles['Employee'], 'employee3@example.com', 'Employee Three');

        $ticket = $this->makeTicket('TICKET-10002', $employeeTwo->id, null);

        $this->actingAs($employeeOne, 'api');

        $response = $this->postJson("/api/tickets/{$ticket->id}/comments", [
            'commentText' => 'Trying to comment on another employee ticket.',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_it_support_comments_on_assigned_ticket_success(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee4@example.com', 'Employee Four');
        $support = $this->makeUser($roles['IT Support'], 'support1@example.com', 'Support One');

        $ticket = $this->makeTicket('TICKET-10003', $employee->id, $support->id, 'Assigned');

        $this->actingAs($support, 'api');

        $response = $this->postJson("/api/tickets/{$ticket->id}/comments", [
            'commentText' => 'I have started working on this issue.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('comment.user.fullName', 'Support One');

        $this->assertDatabaseHas('ticketcomments', [
            'ticketId' => $ticket->id,
            'userId' => $support->id,
            'commentText' => 'I have started working on this issue.',
        ]);
    }

    public function test_it_support_comments_on_other_agent_ticket_forbidden(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee5@example.com', 'Employee Five');
        $supportOne = $this->makeUser($roles['IT Support'], 'support2@example.com', 'Support Two');
        $supportTwo = $this->makeUser($roles['IT Support'], 'support3@example.com', 'Support Three');

        $ticket = $this->makeTicket('TICKET-10004', $employee->id, $supportTwo->id, 'Assigned');

        $this->actingAs($supportOne, 'api');

        $response = $this->postJson("/api/tickets/{$ticket->id}/comments", [
            'commentText' => 'Trying to comment on another agent ticket.',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_manager_tries_to_comment_forbidden(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee6@example.com', 'Employee Six');
        $manager = $this->makeUser($roles['Manager'], 'manager1@example.com', 'Manager One');

        $ticket = $this->makeTicket('TICKET-10005', $employee->id, null);

        $this->actingAs($manager, 'api');

        $response = $this->postJson("/api/tickets/{$ticket->id}/comments", [
            'commentText' => 'Manager comment attempt.',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_admin_comments_success(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee7@example.com', 'Employee Seven');
        $admin = $this->makeUser($roles['Admin'], 'admin1@example.com', 'Admin One');

        $ticket = $this->makeTicket('TICKET-10006', $employee->id, null);

        $this->actingAs($admin, 'api');

        $response = $this->postJson("/api/tickets/{$ticket->id}/comments", [
            'commentText' => 'Admin acknowledged and escalated this issue.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('comment.user.fullName', 'Admin One');

        $this->assertDatabaseHas('ticketcomments', [
            'ticketId' => $ticket->id,
            'userId' => $admin->id,
        ]);
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

    protected function makeUser(Role $role, string $email, string $name): User
    {
        return User::create([
            'roleId' => $role->id,
            'departmentId' => 1,
            'fullName' => $name,
            'email' => $email,
            'password' => 'secret123',
            'status' => 'Active',
        ]);
    }

    protected function makeTicket(string $ticketNumber, int $createdBy, ?int $assignedTo, string $status = 'Open'): Ticket
    {
        return Ticket::create([
            'ticketNumber' => $ticketNumber,
            'title' => 'Sample Ticket',
            'description' => 'Sample description',
            'categoryId' => 1,
            'createdBy' => $createdBy,
            'assignedTo' => $assignedTo,
            'priority' => 'Medium',
            'status' => $status,
        ]);
    }
}
