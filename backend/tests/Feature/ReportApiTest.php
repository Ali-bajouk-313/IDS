<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->string('assignedSupportName')->nullable();
            $table->string('priority')->default('Medium');
            $table->string('status')->default('Open');
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
            $table->timestamp('closedAt')->nullable();
        });
    }

    public function test_admin_receives_full_report_data(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'admin-reports@example.com', 'Admin Reports');
        $departmentOneEmployee = $this->makeUser($roles['Employee'], 'employee-a@example.com', 'Employee A', 1);
        $departmentTwoEmployee = $this->makeUser($roles['Employee'], 'employee-b@example.com', 'Employee B', 2);
        $supportOne = $this->makeUser($roles['IT Support'], 'support-a@example.com', 'Support A');
        $supportTwo = $this->makeUser($roles['IT Support'], 'support-b@example.com', 'Support B');

        $this->makeCategory(1, 'Hardware');
        $this->makeCategory(2, 'Software');

        $this->makeTicket('TICKET-1001', $departmentOneEmployee->id, $supportOne->id, 'Open', 'Low', 1, now()->subMonth());
        $this->makeTicket('TICKET-1002', $departmentOneEmployee->id, $supportOne->id, 'In Progress', 'High', 2, now()->subWeeks(3));
        $this->makeTicket('TICKET-1003', $departmentTwoEmployee->id, $supportTwo->id, 'Resolved', 'Critical', 1, now()->subWeeks(2), now()->subDays(1));
        $this->makeTicket('TICKET-1004', $departmentTwoEmployee->id, null, 'Closed', 'Medium', 2, now()->subDays(10), now()->subDays(2));

        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/reports');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.scope.role', 'Admin')
            ->assertJsonPath('data.summary.totalTickets', 4)
            ->assertJsonPath('data.summary.openTickets', 1)
            ->assertJsonPath('data.summary.assignedTickets', 0)
            ->assertJsonPath('data.summary.inProgressTickets', 1)
            ->assertJsonPath('data.summary.resolvedTickets', 1)
            ->assertJsonPath('data.summary.closedTickets', 1)
            ->assertJsonPath('data.priorityBreakdown.0.name', 'Low');

        $response->assertJsonCount(2, 'data.assignedAgents');
    }

    public function test_manager_report_shows_the_full_ticket_queue(): void
    {
        $roles = $this->seedRoles();
        $manager = $this->makeUser($roles['Manager'], 'manager-reports@example.com', 'Manager Reports');
        $employee = $this->makeUser($roles['Employee'], 'employee-c@example.com', 'Employee C');
        $supportOne = $this->makeUser($roles['IT Support'], 'support-c@example.com', 'Support C');

        $this->makeCategory(1, 'Hardware');

        $this->makeTicket('TICKET-2001', $manager->id, $supportOne->id, 'Open', 'Low', 1, now()->subDays(4));
        $this->makeTicket('TICKET-2002', $employee->id, $supportOne->id, 'Resolved', 'High', 1, now()->subDays(3), now()->subDay());

        $this->actingAs($manager, 'api');

        $response = $this->getJson('/api/reports');

        $response->assertStatus(200)
            ->assertJsonPath('data.scope.role', 'Manager')
            ->assertJsonPath('data.summary.totalTickets', 2)
            ->assertJsonPath('data.summary.openTickets', 1)
            ->assertJsonPath('data.summary.resolvedTickets', 1)
            ->assertJsonCount(1, 'data.assignedAgents');
    }

    public function test_support_report_only_includes_assigned_tickets(): void
    {
        $roles = $this->seedRoles();
        $support = $this->makeUser($roles['IT Support'], 'support-reports@example.com', 'Support Reports');
        $otherSupport = $this->makeUser($roles['IT Support'], 'support-other-reports@example.com', 'Other Support');
        $employee = $this->makeUser($roles['Employee'], 'employee-e@example.com', 'Employee E', 1);

        $this->makeCategory(1, 'Hardware');

        $this->makeTicket('TICKET-3001', $employee->id, $support->id, 'Assigned', 'High', 1, now()->subDays(5));
        $this->makeTicket('TICKET-3002', $employee->id, $otherSupport->id, 'Resolved', 'Low', 1, now()->subDays(2), now()->subDay());

        $this->actingAs($support, 'api');

        $response = $this->getJson('/api/reports');

        $response->assertStatus(200)
            ->assertJsonPath('data.scope.role', 'IT Support')
            ->assertJsonPath('data.summary.totalTickets', 1)
            ->assertJsonPath('data.summary.assignedTickets', 1)
            ->assertJsonPath('data.summary.resolvedTickets', 0)
            ->assertJsonCount(0, 'data.assignedAgents');
    }

    public function test_employee_report_only_includes_their_own_tickets(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee-reports@example.com', 'Employee Reports', 1);
        $otherEmployee = $this->makeUser($roles['Employee'], 'employee-other-reports@example.com', 'Other Employee', 2);

        $this->makeCategory(1, 'Software');

        $this->makeTicket('TICKET-4001', $employee->id, null, 'Open', 'Medium', 1, now()->subDays(7));
        $this->makeTicket('TICKET-4002', $otherEmployee->id, null, 'Closed', 'High', 1, now()->subDays(6), now()->subDays(1));

        $this->actingAs($employee, 'api');

        $response = $this->getJson('/api/reports');

        $response->assertStatus(200)
            ->assertJsonPath('data.scope.role', 'Employee')
            ->assertJsonPath('data.summary.totalTickets', 1)
            ->assertJsonPath('data.summary.openTickets', 1)
            ->assertJsonPath('data.summary.closedTickets', 0)
            ->assertJsonCount(0, 'data.assignedAgents');
    }

    public function test_admin_can_export_reports_as_pdf(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'admin-pdf@example.com', 'Admin PDF');
        $employee = $this->makeUser($roles['Employee'], 'employee-pdf@example.com', 'Employee PDF', 1);

        $this->makeCategory(1, 'Hardware');
        $this->makeTicket('TICKET-5001', $employee->id, null, 'Open', 'Low', 1, now()->subDay());

        $this->actingAs($admin, 'api');

        $response = $this->get('/api/reports/export/pdf');

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringContainsString('attachment; filename=', (string) $response->headers->get('content-disposition'));
    }

    public function test_employee_can_export_only_their_scope_as_excel(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee-xlsx@example.com', 'Employee XLSX', 1);
        $otherEmployee = $this->makeUser($roles['Employee'], 'employee-xlsx-other@example.com', 'Other XLSX', 2);

        $this->makeCategory(1, 'Software');
        $this->makeTicket('TICKET-6001', $employee->id, null, 'Open', 'Medium', 1, now()->subDays(2));
        $this->makeTicket('TICKET-6002', $otherEmployee->id, null, 'Closed', 'High', 1, now()->subDays(3), now()->subDay());

        $this->actingAs($employee, 'api');

        $response = $this->get('/api/reports/export/excel');

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $xml = $this->extractSpreadsheetXml($response->streamedContent());

        $this->assertStringContainsString('HelpDeskPro Reports', $xml);
        $this->assertStringContainsString('TICKET-6001', $xml);
        $this->assertStringNotContainsString('TICKET-6002', $xml);
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

    private function makeCategory(int $id, string $name): void
    {
        \DB::table('categories')->insert([
            'id' => $id,
            'categoryName' => $name,
            'createdAt' => now(),
            'updatedAt' => now(),
        ]);
    }

    private function makeTicket(
        string $ticketNumber,
        int $createdBy,
        ?int $assignedTo,
        string $status,
        string $priority,
        int $categoryId,
        $createdAt,
        $closedAt = null
    ): Ticket {
        return Ticket::create([
            'ticketNumber' => $ticketNumber,
            'title' => 'Sample Ticket',
            'description' => 'Sample description',
            'categoryId' => $categoryId,
            'createdBy' => $createdBy,
            'assignedTo' => $assignedTo,
            'priority' => $priority,
            'status' => $status,
            'createdAt' => $createdAt,
            'updatedAt' => now(),
            'closedAt' => $closedAt,
        ]);
    }

    private function extractSpreadsheetXml(string $binaryContent): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'helpdeskpro-xlsx-');
        file_put_contents($tempFile, $binaryContent);

        $zip = new \ZipArchive();
        $zip->open($tempFile);

        $xml = '';
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if ($name !== false && str_ends_with($name, '.xml')) {
                $xml .= $zip->getFromIndex($index) ?: '';
            }
        }

        $zip->close();
        @unlink($tempFile);

        return $xml;
    }
}