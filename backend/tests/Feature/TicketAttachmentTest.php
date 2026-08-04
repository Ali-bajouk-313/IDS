<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('ticket_attachments');
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

        Schema::create('ticket_attachments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id');
            $table->string('file_name');
            $table->string('stored_name');
            $table->string('mime_type');
            $table->unsignedInteger('size_bytes');
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

    public function test_employee_can_upload_attachment_to_own_ticket_success(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee-attachments@example.com', 'Employee Attachments');
        $ticket = $this->makeTicket('TICKET-20001', $employee->id, null);

        $this->actingAs($employee, 'api');

        $response = $this->postJson("/api/tickets/{$ticket->id}/attachments", [
            'file' => UploadedFile::fake()->create('proof.png', 100, 'image/png'),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('attachment.fileName', 'proof.png');

        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'user_id' => $employee->id,
            'file_name' => 'proof.png',
        ]);
    }

    public function test_employee_cannot_upload_attachment_to_another_ticket(): void
    {
        $roles = $this->seedRoles();
        $employeeOne = $this->makeUser($roles['Employee'], 'employee-one@example.com', 'Employee One');
        $employeeTwo = $this->makeUser($roles['Employee'], 'employee-two@example.com', 'Employee Two');
        $ticket = $this->makeTicket('TICKET-20002', $employeeTwo->id, null);

        $this->actingAs($employeeOne, 'api');

        $response = $this->postJson("/api/tickets/{$ticket->id}/attachments", [
            'file' => UploadedFile::fake()->create('notes.txt', 100, 'text/plain'),
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_admin_can_delete_attachment(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee-delete@example.com', 'Employee Delete');
        $admin = $this->makeUser($roles['Admin'], 'admin-delete@example.com', 'Admin Delete');
        $ticket = $this->makeTicket('TICKET-20003', $employee->id, null);
        $attachment = $this->makeAttachment($ticket->id, $employee->id, 'proof.png');

        $this->actingAs($admin, 'api');

        $response = $this->deleteJson("/api/attachments/{$attachment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Attachment deleted successfully');

        $this->assertDatabaseMissing('ticket_attachments', ['id' => $attachment->id]);
    }

    public function test_employee_cannot_delete_attachment(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee-delete2@example.com', 'Employee Delete Two');
        $ticket = $this->makeTicket('TICKET-20004', $employee->id, null);
        $attachment = $this->makeAttachment($ticket->id, $employee->id, 'proof.png');

        $this->actingAs($employee, 'api');

        $response = $this->deleteJson("/api/attachments/{$attachment->id}");

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_it_support_cannot_delete_attachment(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee-delete3@example.com', 'Employee Delete Three');
        $support = $this->makeUser($roles['IT Support'], 'support-delete@example.com', 'Support Delete');
        $ticket = $this->makeTicket('TICKET-20005', $employee->id, $support->id, 'Assigned');
        $attachment = $this->makeAttachment($ticket->id, $employee->id, 'proof.png');

        $this->actingAs($support, 'api');

        $response = $this->deleteJson("/api/attachments/{$attachment->id}");

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_manager_cannot_delete_attachment(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee-delete4@example.com', 'Employee Delete Four');
        $manager = $this->makeUser($roles['Manager'], 'manager-delete@example.com', 'Manager Delete');
        $ticket = $this->makeTicket('TICKET-20006', $employee->id, null);
        $attachment = $this->makeAttachment($ticket->id, $employee->id, 'proof.png');

        $this->actingAs($manager, 'api');

        $response = $this->deleteJson("/api/attachments/{$attachment->id}");

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Forbidden');
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

    protected function makeAttachment(int $ticketId, int $userId, string $fileName): TicketAttachment
    {
        return TicketAttachment::create([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'file_name' => $fileName,
            'stored_name' => 'attachment-test-' . $fileName,
            'mime_type' => 'image/png',
            'size_bytes' => 123,
        ]);
    }
}
