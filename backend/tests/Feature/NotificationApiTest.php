<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('notifications');
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

        Schema::create('notifications', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tickets', function ($table) {
            $table->id();
            $table->string('ticketNumber');
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('categoryId')->nullable();
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('assignedTo')->nullable();
            $table->string('priority')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('closedAt')->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
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
            $table->string('comment')->nullable();
            $table->timestamp('changedAt')->nullable();
        });

        Schema::create('activitylogs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->string('action');
            $table->text('description');
            $table->string('ipAddress')->nullable();
            $table->timestamp('createdAt')->nullable();
        });

        Schema::create('ticket_assignments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('old_assigned_to')->nullable();
            $table->unsignedBigInteger('new_assigned_to')->nullable();
            $table->unsignedBigInteger('assigned_by');
            $table->string('action');
            $table->timestamps();
        });
    }

    public function test_authenticated_user_can_fetch_their_notifications(): void
    {
        $role = Role::create(['roleName' => 'Employee', 'description' => 'Employee']);
        $user = User::create([
            'roleId' => $role->id,
            'fullName' => 'Employee One',
            'email' => 'employee@example.com',
            'password' => 'secret',
            'status' => 'Active',
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'ticket_assigned',
            'title' => 'New ticket assigned',
            'message' => 'A ticket was assigned to you.',
            'data' => ['ticket_id' => 42],
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.title', 'New ticket assigned');
    }

    public function test_authenticated_user_can_mark_a_notification_as_read(): void
    {
        $role = Role::create(['roleName' => 'Employee', 'description' => 'Employee']);
        $user = User::create([
            'roleId' => $role->id,
            'fullName' => 'Employee Two',
            'email' => 'employee2@example.com',
            'password' => 'secret',
            'status' => 'Active',
        ]);

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'ticket_updated',
            'title' => 'Ticket updated',
            'message' => 'Your ticket changed status.',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/notifications/' . $notification->id . '/read');

        $response->assertOk()
            ->assertJsonPath('notification.id', $notification->id)
            ->assertJsonPath('notification.read', true);
    }

    public function test_assigning_a_ticket_creates_a_notification_for_the_assigned_support_user(): void
    {
        $adminRole = Role::create(['roleName' => 'Admin', 'description' => 'Admin']);
        $supportRole = Role::create(['roleName' => 'IT Support', 'description' => 'IT Support']);
        $managerRole = Role::create(['roleName' => 'Manager', 'description' => 'Manager']);

        $admin = User::create([
            'roleId' => $adminRole->id,
            'fullName' => 'Admin User',
            'email' => 'admin-notify@example.com',
            'password' => 'secret',
            'status' => 'Active',
        ]);

        $support = User::create([
            'roleId' => $supportRole->id,
            'fullName' => 'Support User',
            'email' => 'support-notify@example.com',
            'password' => 'secret',
            'status' => 'Active',
        ]);

        $manager = User::create([
            'roleId' => $managerRole->id,
            'fullName' => 'Manager User',
            'email' => 'manager-notify@example.com',
            'password' => 'secret',
            'status' => 'Active',
        ]);

        $ticket = Ticket::create([
            'ticketNumber' => 'TICKET-00001',
            'title' => 'Printer issue',
            'description' => 'Printer is offline',
            'createdBy' => $admin->id,
            'priority' => 'High',
            'status' => 'Open',
        ]);

        $token = JWTAuth::fromUser($admin);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/tickets/' . $ticket->id . '/assign', ['assigned_to' => $support->id]);

        $response->assertOk();
        $this->assertDatabaseHas('notifications', [
            'user_id' => $support->id,
            'type' => 'ticket_assigned',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $manager->id,
            'type' => 'ticket_assigned',
        ]);
    }

    public function test_commenting_on_a_ticket_creates_notifications_for_the_creator_and_assigned_support(): void
    {
        $employeeRole = Role::create(['roleName' => 'Employee', 'description' => 'Employee']);
        $supportRole = Role::create(['roleName' => 'IT Support', 'description' => 'IT Support']);
        $adminRole = Role::create(['roleName' => 'Admin', 'description' => 'Admin']);

        $creator = User::create([
            'roleId' => $employeeRole->id,
            'fullName' => 'Creator User',
            'email' => 'creator-notify@example.com',
            'password' => 'secret',
            'status' => 'Active',
        ]);

        $support = User::create([
            'roleId' => $supportRole->id,
            'fullName' => 'Support User',
            'email' => 'support-comment@example.com',
            'password' => 'secret',
            'status' => 'Active',
        ]);

        $admin = User::create([
            'roleId' => $adminRole->id,
            'fullName' => 'Admin User',
            'email' => 'admin-comment@example.com',
            'password' => 'secret',
            'status' => 'Active',
        ]);

        $ticket = Ticket::create([
            'ticketNumber' => 'TICKET-00002',
            'title' => 'VPN issue',
            'description' => 'VPN is failing',
            'createdBy' => $creator->id,
            'assignedTo' => $support->id,
            'priority' => 'High',
            'status' => 'Assigned',
        ]);

        $token = JWTAuth::fromUser($admin);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/tickets/' . $ticket->id . '/comments', ['commentText' => 'I reviewed the issue.']);

        $response->assertCreated();
        $this->assertDatabaseHas('notifications', ['user_id' => $creator->id, 'type' => 'ticket_comment']);
        $this->assertDatabaseHas('notifications', ['user_id' => $support->id, 'type' => 'ticket_comment']);
    }

    public function test_status_change_creates_notifications_for_the_creator_and_assigned_support(): void
    {
        $employeeRole = Role::create(['roleName' => 'Employee', 'description' => 'Employee']);
        $supportRole = Role::create(['roleName' => 'IT Support', 'description' => 'IT Support']);
        $adminRole = Role::create(['roleName' => 'Admin', 'description' => 'Admin']);

        $creator = User::create([
            'roleId' => $employeeRole->id,
            'fullName' => 'Creator User',
            'email' => 'creator-status@example.com',
            'password' => 'secret',
            'status' => 'Active',
        ]);

        $support = User::create([
            'roleId' => $supportRole->id,
            'fullName' => 'Support User',
            'email' => 'support-status@example.com',
            'password' => 'secret',
            'status' => 'Active',
        ]);

        $admin = User::create([
            'roleId' => $adminRole->id,
            'fullName' => 'Admin User',
            'email' => 'admin-status@example.com',
            'password' => 'secret',
            'status' => 'Active',
        ]);

        $ticket = Ticket::create([
            'ticketNumber' => 'TICKET-00003',
            'title' => 'Email issue',
            'description' => 'Mail delivery issue',
            'createdBy' => $creator->id,
            'assignedTo' => $support->id,
            'priority' => 'High',
            'status' => 'Assigned',
        ]);

        $token = JWTAuth::fromUser($admin);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/tickets/' . $ticket->id . '/status', ['status' => 'In Progress']);

        $response->assertOk();
        $this->assertDatabaseHas('notifications', ['user_id' => $creator->id, 'type' => 'ticket_status_changed']);
        $this->assertDatabaseHas('notifications', ['user_id' => $support->id, 'type' => 'ticket_status_changed']);
    }
}
