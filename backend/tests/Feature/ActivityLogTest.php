<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        Schema::dropIfExists('activitylogs');
        Schema::dropIfExists('email_verification_tokens');
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

        Schema::create('email_verification_tokens', function ($table) {
            $table->id();
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('activitylogs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->string('action', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('ipAddress', 50)->nullable();
            $table->timestamp('createdAt')->nullable();
        });
    }

    public function test_admin_can_view_logs(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'admin-logs@example.com', 'Admin Logs', 1);

        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/activity-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'logs',
            ]);
    }

    public function test_manager_is_forbidden_from_viewing_logs(): void
    {
        $roles = $this->seedRoles();
        $manager = $this->makeUser($roles['Manager'], 'manager-logs@example.com', 'Manager Logs', 1);

        $this->actingAs($manager, 'api');

        $this->getJson('/api/activity-logs')->assertStatus(403);
    }

    public function test_employee_is_forbidden_from_viewing_logs(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee-logs@example.com', 'Employee Logs', 1);

        $this->actingAs($employee, 'api');

        $this->getJson('/api/activity-logs')->assertStatus(403);
    }

    public function test_it_support_is_forbidden_from_viewing_logs(): void
    {
        $roles = $this->seedRoles();
        $support = $this->makeUser($roles['IT Support'], 'support-logs@example.com', 'Support Logs', 1);

        $this->actingAs($support, 'api');

        $this->getJson('/api/activity-logs')->assertStatus(403);
    }

    public function test_login_creates_activity(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'login-employee@example.com', 'Login Employee', 1, true);

        $this->postJson('/api/login', [
            'email' => 'login-employee@example.com',
            'password' => 'secret123',
        ])->assertStatus(200);

        $this->assertDatabaseHas('activitylogs', [
            'userId' => $employee->id,
            'action' => 'LOGIN',
        ]);
    }

    public function test_logout_creates_activity(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'logout-employee@example.com', 'Logout Employee', 1, true);

        $token = JWTAuth::fromUser($employee);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertStatus(200);

        $this->assertDatabaseHas('activitylogs', [
            'userId' => $employee->id,
            'action' => 'LOGOUT',
        ]);
    }

    public function test_register_creates_activity(): void
    {
        $this->seedRoles();

        $this->postJson('/api/register', [
            'fullName' => 'Register Employee',
            'email' => 'register-employee@example.com',
            'password' => 'secret123',
            'phone' => '96170000000',
        ])->assertStatus(201);

        $user = User::where('email', 'register-employee@example.com')->first();

        $this->assertNotNull($user);
        $this->assertDatabaseHas('activitylogs', [
            'userId' => $user->id,
            'action' => 'REGISTER',
        ]);
    }

    public function test_assignment_creates_activity(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'assign-admin@example.com', 'Assign Admin', 1);
        $employee = $this->makeUser($roles['Employee'], 'assign-employee@example.com', 'Assign Employee', 1);
        $support = $this->makeUser($roles['IT Support'], 'assign-support@example.com', 'Assign Support', 1);

        $ticket = $this->makeTicket('TICKET-30001', $employee->id, null, 'Open');

        $this->actingAs($admin, 'api');

        $this->postJson("/api/tickets/{$ticket->id}/assign", [
            'assigned_to' => $support->id,
        ])->assertStatus(200);

        $this->assertDatabaseHas('activitylogs', [
            'userId' => $admin->id,
            'action' => 'ASSIGN_TICKET',
        ]);
    }

    public function test_comment_creates_activity(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'comment-employee@example.com', 'Comment Employee', 1);

        $ticket = $this->makeTicket('TICKET-30002', $employee->id, null, 'Open');

        $this->actingAs($employee, 'api');

        $this->postJson("/api/tickets/{$ticket->id}/comments", [
            'commentText' => 'Please review quickly.',
        ])->assertStatus(201);

        $this->assertDatabaseHas('activitylogs', [
            'userId' => $employee->id,
            'action' => 'COMMENT_ADDED',
        ]);
    }

    public function test_status_change_creates_activity(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'status-admin@example.com', 'Status Admin', 1);
        $employee = $this->makeUser($roles['Employee'], 'status-employee@example.com', 'Status Employee', 1);

        $ticket = $this->makeTicket('TICKET-30003', $employee->id, null, 'Assigned');

        $this->actingAs($admin, 'api');

        $this->putJson("/api/tickets/{$ticket->id}/status", [
            'status' => 'In Progress',
        ])->assertStatus(200);

        $this->assertDatabaseHas('activitylogs', [
            'userId' => $admin->id,
            'action' => 'STATUS_CHANGED',
        ]);
    }

    protected function seedRoles(): array
    {
        $employee = Role::firstOrCreate(['id' => 1], ['roleName' => 'Employee']);
        $support = Role::firstOrCreate(['id' => 2], ['roleName' => 'IT Support']);
        $manager = Role::firstOrCreate(['id' => 3], ['roleName' => 'Manager']);
        $admin = Role::firstOrCreate(['id' => 4], ['roleName' => 'Admin']);

        return [
            'Employee' => $employee,
            'IT Support' => $support,
            'Manager' => $manager,
            'Admin' => $admin,
        ];
    }

    protected function makeUser(Role $role, string $email, string $name, int $departmentId, bool $verified = true): User
    {
        $user = User::create([
            'roleId' => $role->id,
            'departmentId' => $departmentId,
            'fullName' => $name,
            'email' => $email,
            'password' => Hash::make('secret123'),
            'status' => 'Active',
        ]);

        if ($verified) {
            DB::table('users')->where('id', $user->id)->update([
                'email_verified_at' => now(),
            ]);
            $user = User::find($user->id);
        }

        return $user;
    }

    protected function makeTicket(string $ticketNumber, int $createdBy, ?int $assignedTo, string $status = 'Open', string $priority = 'Medium'): Ticket
    {
        $category = Category::firstOrCreate(['id' => 1], ['categoryName' => 'Hardware']);

        return Ticket::create([
            'ticketNumber' => $ticketNumber,
            'title' => 'Activity Ticket',
            'description' => 'Activity description',
            'categoryId' => $category->id,
            'createdBy' => $createdBy,
            'assignedTo' => $assignedTo,
            'priority' => $priority,
            'status' => $status,
        ]);
    }
}
