<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('activitylogs');
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

        Schema::create('activitylogs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->string('action', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('ipAddress', 50)->nullable();
            $table->timestamp('createdAt')->nullable();
        });
    }

    public function test_admin_dashboard_returns_system_statistics(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'admin-dashboard@example.com', 'Admin Dashboard');
        $employee = $this->makeUser($roles['Employee'], 'employee-dashboard@example.com', 'Employee Dashboard');
        $support = $this->makeUser($roles['IT Support'], 'support-dashboard@example.com', 'Support Dashboard');
        $manager = $this->makeUser($roles['Manager'], 'manager-dashboard@example.com', 'Manager Dashboard');

        $this->makeTicket('TICKET-9001', $employee->id, null, 'Open', 'Low', 1);
        $this->makeTicket('TICKET-9002', $employee->id, $support->id, 'In Progress', 'High', 2);
        $this->makeTicket('TICKET-9003', $employee->id, $support->id, 'Resolved', 'Urgent', 1);
        $this->makeTicket('TICKET-9004', $employee->id, null, 'Closed', 'Medium', 2);

        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/dashboard/admin');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.totalTickets', 4)
            ->assertJsonPath('data.summary.openTickets', 1)
            ->assertJsonPath('data.summary.inProgressTickets', 1)
            ->assertJsonPath('data.summary.resolvedTickets', 1)
            ->assertJsonPath('data.summary.closedTickets', 1)
            ->assertJsonPath('data.priority.urgent', 1);
    }

    public function test_manager_dashboard_filters_to_department_tickets(): void
    {
        $roles = $this->seedRoles();
        $manager = $this->makeUser($roles['Manager'], 'manager-dept@example.com', 'Manager Dept', 1);
        $employeeSameDepartment = $this->makeUser($roles['Employee'], 'employee-same@example.com', 'Employee Same', 1);
        $employeeOtherDepartment = $this->makeUser($roles['Employee'], 'employee-other@example.com', 'Employee Other', 2);

        $this->makeTicket('TICKET-9101', $employeeSameDepartment->id, null, 'Open', 'Low', 1);
        $this->makeTicket('TICKET-9102', $employeeOtherDepartment->id, null, 'Open', 'Medium', 2);

        $this->actingAs($manager, 'api');

        $response = $this->getJson('/api/dashboard/manager');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.totalTickets', 1);
    }

    public function test_support_dashboard_only_shows_assigned_tickets(): void
    {
        $roles = $this->seedRoles();
        $support = $this->makeUser($roles['IT Support'], 'support-only@example.com', 'Support Only');
        $otherSupport = $this->makeUser($roles['IT Support'], 'support-other@example.com', 'Support Other');
        $employee = $this->makeUser($roles['Employee'], 'employee-support@example.com', 'Employee Support');

        $this->makeTicket('TICKET-9201', $employee->id, $support->id, 'Open', 'High', 1);
        $this->makeTicket('TICKET-9202', $employee->id, $otherSupport->id, 'Resolved', 'Low', 1);

        $this->actingAs($support, 'api');

        $response = $this->getJson('/api/dashboard/support');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.totalAssignedTickets', 1)
            ->assertJsonPath('data.summary.openAssignedTickets', 1);
    }

    public function test_employee_dashboard_only_shows_their_tickets(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee-own@example.com', 'Employee Own');
        $otherEmployee = $this->makeUser($roles['Employee'], 'employee-other-own@example.com', 'Employee Other Own');

        $this->makeTicket('TICKET-9301', $employee->id, null, 'Open', 'Low', 1);
        $this->makeTicket('TICKET-9302', $otherEmployee->id, null, 'Resolved', 'Medium', 1);

        $this->actingAs($employee, 'api');

        $response = $this->getJson('/api/dashboard/employee');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.totalCreatedTickets', 1)
            ->assertJsonPath('data.summary.openTickets', 1);
    }

    private function seedRoles(): array
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

    private function makeUser(Role $role, string $email, string $fullName, ?int $departmentId = null): User
    {
        return User::create([
            'roleId' => $role->id,
            'departmentId' => $departmentId,
            'fullName' => $fullName,
            'email' => $email,
            'password' => bcrypt('password123'),
            'status' => 'Active',
            'createdAt' => now(),
            'updatedAt' => now(),
        ]);
    }

    private function makeTicket(string $ticketNumber, int $createdBy, ?int $assignedTo, string $status, string $priority, int $categoryId): Ticket
    {
        return Ticket::create([
            'ticketNumber' => $ticketNumber,
            'title' => 'Sample Ticket',
            'description' => 'Sample description',
            'categoryId' => $categoryId,
            'createdBy' => $createdBy,
            'assignedTo' => $assignedTo,
            'priority' => $priority,
            'status' => $status,
            'createdAt' => now()->subDays(rand(1, 30)),
            'updatedAt' => now(),
            'closedAt' => $status === 'Resolved' || $status === 'Closed' ? now()->subDays(2) : null,
        ]);
    }
}
