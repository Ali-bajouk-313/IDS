<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketInternalNoteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('ticket_internal_notes');
        Schema::dropIfExists('activitylogs');
        Schema::dropIfExists('tickethistory');
        Schema::dropIfExists('ticket_assignments');
        Schema::dropIfExists('ticketcomments');
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

        Schema::create('activitylogs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->string('action', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('ipAddress', 50)->nullable();
            $table->timestamp('createdAt')->nullable();
        });

        Schema::create('ticket_internal_notes', function ($table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id');
            $table->text('note');
            $table->timestamps();
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

        Schema::create('ticketcomments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('ticketId');
            $table->unsignedBigInteger('userId');
            $table->text('commentText');
            $table->timestamp('createdAt')->nullable();
        });
    }

    public function test_admin_can_add_internal_note(): void
    {
        $ctx = $this->buildFixture();

        $this->actingAs($ctx['admin'], 'api');

        $response = $this->postJson("/api/tickets/{$ctx['ticket']->id}/internal-notes", [
            'note' => 'Admin private troubleshooting note.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('note.user.fullName', $ctx['admin']->fullName);

        $this->assertDatabaseHas('ticket_internal_notes', [
            'ticket_id' => $ctx['ticket']->id,
            'user_id' => $ctx['admin']->id,
            'note' => 'Admin private troubleshooting note.',
        ]);
    }

    public function test_assigned_it_support_can_add_internal_note(): void
    {
        $ctx = $this->buildFixture();

        $this->actingAs($ctx['assignedSupport'], 'api');

        $response = $this->postJson("/api/tickets/{$ctx['ticket']->id}/internal-notes", [
            'note' => 'Assigned support private technical note.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('note.user.fullName', $ctx['assignedSupport']->fullName);
    }

    public function test_other_it_support_forbidden(): void
    {
        $ctx = $this->buildFixture();

        $this->actingAs($ctx['otherSupport'], 'api');

        $this->postJson("/api/tickets/{$ctx['ticket']->id}/internal-notes", [
            'note' => 'Forbidden note',
        ])->assertStatus(403);

        $this->getJson("/api/tickets/{$ctx['ticket']->id}/internal-notes")
            ->assertStatus(403);
    }

    public function test_employee_forbidden(): void
    {
        $ctx = $this->buildFixture();

        $this->actingAs($ctx['employee'], 'api');

        $this->postJson("/api/tickets/{$ctx['ticket']->id}/internal-notes", [
            'note' => 'Forbidden employee note',
        ])->assertStatus(403);

        $this->getJson("/api/tickets/{$ctx['ticket']->id}/internal-notes")
            ->assertStatus(403);
    }

    public function test_manager_forbidden(): void
    {
        $ctx = $this->buildFixture();

        $this->actingAs($ctx['manager'], 'api');

        $this->postJson("/api/tickets/{$ctx['ticket']->id}/internal-notes", [
            'note' => 'Forbidden manager note',
        ])->assertStatus(403);

        $this->getJson("/api/tickets/{$ctx['ticket']->id}/internal-notes")
            ->assertStatus(403);
    }

    public function test_delete_permissions(): void
    {
        $ctx = $this->buildFixture();

        $this->actingAs($ctx['admin'], 'api');
        $createResponse = $this->postJson("/api/tickets/{$ctx['ticket']->id}/internal-notes", [
            'note' => 'Delete me',
        ])->assertStatus(201);

        $noteId = $createResponse->json('note.id');

        $this->actingAs($ctx['assignedSupport'], 'api');
        $this->deleteJson("/api/internal-notes/{$noteId}")->assertStatus(403);

        $this->actingAs($ctx['admin'], 'api');
        $this->deleteJson("/api/internal-notes/{$noteId}")->assertStatus(200);
    }

    public function test_visibility_permissions(): void
    {
        $ctx = $this->buildFixture();

        $this->actingAs($ctx['admin'], 'api');
        $this->postJson("/api/tickets/{$ctx['ticket']->id}/internal-notes", [
            'note' => 'Visible internal note',
        ])->assertStatus(201);

        $this->actingAs($ctx['admin'], 'api')
            ->getJson("/api/tickets/{$ctx['ticket']->id}/internal-notes")
            ->assertStatus(200)
            ->assertJsonCount(1, 'notes');

        $this->actingAs($ctx['assignedSupport'], 'api')
            ->getJson("/api/tickets/{$ctx['ticket']->id}/internal-notes")
            ->assertStatus(200)
            ->assertJsonCount(1, 'notes');
    }

    public function test_history_created(): void
    {
        $ctx = $this->buildFixture();

        $this->actingAs($ctx['admin'], 'api');

        $this->postJson("/api/tickets/{$ctx['ticket']->id}/internal-notes", [
            'note' => 'History trace internal note',
        ])->assertStatus(201);

        $this->assertDatabaseHas('tickethistory', [
            'ticketId' => $ctx['ticket']->id,
            'changedBy' => $ctx['admin']->id,
            'comment' => 'Internal note added',
        ]);
    }

    public function test_activity_log_created(): void
    {
        $ctx = $this->buildFixture();

        $this->actingAs($ctx['assignedSupport'], 'api');

        $this->postJson("/api/tickets/{$ctx['ticket']->id}/internal-notes", [
            'note' => 'Activity trace internal note',
        ])->assertStatus(201);

        $this->assertDatabaseHas('activitylogs', [
            'userId' => $ctx['assignedSupport']->id,
            'action' => 'INTERNAL_NOTE_ADDED',
        ]);
    }

    protected function buildFixture(): array
    {
        $roles = $this->seedRoles();

        $admin = $this->makeUser($roles['Admin'], 'admin-internal@example.com', 'Admin Internal', 1);
        $manager = $this->makeUser($roles['Manager'], 'manager-internal@example.com', 'Manager Internal', 1);
        $employee = $this->makeUser($roles['Employee'], 'employee-internal@example.com', 'Employee Internal', 1);
        $assignedSupport = $this->makeUser($roles['IT Support'], 'support-assigned-internal@example.com', 'Support Assigned Internal', 1);
        $otherSupport = $this->makeUser($roles['IT Support'], 'support-other-internal@example.com', 'Support Other Internal', 1);

        Category::create([
            'id' => 1,
            'categoryName' => 'Hardware',
        ]);

        $ticket = Ticket::create([
            'ticketNumber' => 'TICKET-40001',
            'title' => 'Internal notes test ticket',
            'description' => 'Internal notes test description',
            'categoryId' => 1,
            'createdBy' => $employee->id,
            'assignedTo' => $assignedSupport->id,
            'priority' => 'High',
            'status' => 'Assigned',
        ]);

        return [
            'admin' => $admin,
            'manager' => $manager,
            'employee' => $employee,
            'assignedSupport' => $assignedSupport,
            'otherSupport' => $otherSupport,
            'ticket' => $ticket,
        ];
    }

    protected function seedRoles(): array
    {
        return [
            'Employee' => Role::firstOrCreate(['id' => 1], ['roleName' => 'Employee']),
            'IT Support' => Role::firstOrCreate(['id' => 2], ['roleName' => 'IT Support']),
            'Manager' => Role::firstOrCreate(['id' => 3], ['roleName' => 'Manager']),
            'Admin' => Role::firstOrCreate(['id' => 4], ['roleName' => 'Admin']),
        ];
    }

    protected function makeUser(Role $role, string $email, string $fullName, int $departmentId): User
    {
        return User::create([
            'roleId' => $role->id,
            'departmentId' => $departmentId,
            'fullName' => $fullName,
            'email' => $email,
            'password' => 'secret123',
            'status' => 'Active',
        ]);
    }
}
