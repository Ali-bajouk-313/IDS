<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\Ai\AiProviderFactory;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResponse;
use App\Services\Ai\AiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketAiInsightsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('tickethistory');
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

    public function test_admin_can_generate_summary_from_live_ticket_data(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'admin-ai@example.com', 'Admin AI');
        $employee = $this->makeUser($roles['Employee'], 'employee-ai@example.com', 'Employee AI', 1);

        $this->makeCategory(1, 'Network');
        $ticket = $this->makeTicket('TICKET-9001', $employee->id, null, 'Open', 'High', 1, now()->subDays(2));
        $this->makeComment($ticket->id, $employee->id, 'The VPN disconnects every morning.');
        $this->makeHistory($ticket->id, $admin->id, 'Open', 'Open', 'Ticket created and queued for review.');

        $fakeAi = new FakeAiService(
            new AiResponse(
                provider: 'fake',
                model: 'demo-model',
                content: json_encode([
                    'summary' => 'The user is experiencing a recurring VPN issue that appears to happen in the morning.',
                    'highlights' => ['VPN disconnects daily', 'Issue appears recurring', 'Requires support review'],
                ])
            )
        );

        $this->app->instance(AiService::class, $fakeAi);

        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/tickets/' . $ticket->id . '/ai/summary');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ticketNumber', 'TICKET-9001')
            ->assertJsonPath('data.summary', 'The user is experiencing a recurring VPN issue that appears to happen in the morning.')
            ->assertJsonPath('data.highlights.0', 'VPN disconnects daily');

        $this->assertStringContainsString('VPN disconnects every morning.', $fakeAi->lastMessages[1]['content']);
        $this->assertStringContainsString('Ticket created and queued for review.', $fakeAi->lastMessages[1]['content']);
        $this->assertSame('summary', $fakeAi->lastOptions['metadata']['feature']);
    }

    public function test_it_support_can_get_priority_recommendation_without_persisting_changes(): void
    {
        $roles = $this->seedRoles();
        $support = $this->makeUser($roles['IT Support'], 'support-ai@example.com', 'Support AI');
        $employee = $this->makeUser($roles['Employee'], 'employee-priority@example.com', 'Employee Priority', 1);

        $this->makeCategory(1, 'Hardware');
        $ticket = $this->makeTicket('TICKET-9002', $employee->id, $support->id, 'Assigned', 'Medium', 1, now()->subDay());
        $this->makeComment($ticket->id, $employee->id, 'The laptop will not power on after the latest update.');

        $fakeAi = new FakeAiService(
            new AiResponse(
                provider: 'fake',
                model: 'demo-model',
                content: json_encode([
                    'recommendedPriority' => 'High',
                    'explanation' => 'The issue blocks the user from working and affects core hardware.'
                ])
            )
        );

        $this->app->instance(AiService::class, $fakeAi);

        $this->actingAs($support, 'api');

        $response = $this->getJson('/api/tickets/' . $ticket->id . '/ai/priority');

        $response->assertStatus(200)
            ->assertJsonPath('data.recommendedPriority', 'High')
            ->assertJsonPath('data.explanation', 'The issue blocks the user from working and affects core hardware.');

        $this->assertSame('Assigned', Ticket::findOrFail($ticket->id)->status);
        $this->assertSame('Medium', Ticket::findOrFail($ticket->id)->priority);
    }

    public function test_it_support_receives_troubleshooting_steps_with_comments_and_history(): void
    {
        $roles = $this->seedRoles();
        $support = $this->makeUser($roles['IT Support'], 'support-troubleshoot@example.com', 'Support Troubleshoot');
        $employee = $this->makeUser($roles['Employee'], 'employee-troubleshoot@example.com', 'Employee Troubleshoot', 1);

        $this->makeCategory(1, 'Email');
        $ticket = $this->makeTicket('TICKET-9003', $employee->id, $support->id, 'In Progress', 'Critical', 1, now()->subDays(3));
        $this->makeComment($ticket->id, $employee->id, 'Outlook keeps asking for a password after restart.');
        $this->makeHistory($ticket->id, $support->id, 'Assigned', 'In Progress', 'Support started investigating the mail profile.');

        $fakeAi = new FakeAiService(
            new AiResponse(
                provider: 'fake',
                model: 'demo-model',
                content: json_encode([
                    'summary' => 'The issue appears related to an authentication or cached credential problem.',
                    'suggestions' => [
                        'Confirm the account password and recent changes.',
                        'Clear cached Outlook credentials and restart the client.',
                        'Rebuild the mail profile if the issue persists.'
                    ],
                ])
            )
        );

        $this->app->instance(AiService::class, $fakeAi);

        $this->actingAs($support, 'api');

        $response = $this->getJson('/api/tickets/' . $ticket->id . '/ai/troubleshooting');

        $response->assertStatus(200)
            ->assertJsonPath('data.summary', 'The issue appears related to an authentication or cached credential problem.')
            ->assertJsonPath('data.suggestions.0', 'Confirm the account password and recent changes.');

        $this->assertStringContainsString('Outlook keeps asking for a password after restart.', $fakeAi->lastMessages[1]['content']);
        $this->assertStringContainsString('Support started investigating the mail profile.', $fakeAi->lastMessages[1]['content']);
        $this->assertSame('troubleshooting', $fakeAi->lastOptions['metadata']['feature']);
    }

    public function test_users_cannot_request_ai_analysis_for_tickets_they_cannot_view(): void
    {
        $roles = $this->seedRoles();
        $manager = $this->makeUser($roles['Manager'], 'manager-ai@example.com', 'Manager AI', 10);
        $employee = $this->makeUser($roles['Employee'], 'employee-forbidden@example.com', 'Employee Forbidden', 1);
        $otherEmployee = $this->makeUser($roles['Employee'], 'employee-forbidden-2@example.com', 'Employee Forbidden 2', 2);

        $this->makeCategory(1, 'Software');
        $ticket = $this->makeTicket('TICKET-9004', $otherEmployee->id, null, 'Open', 'Low', 1, now()->subDay());

        $fakeAi = new FakeAiService(new AiResponse(provider: 'fake', model: 'demo-model', content: json_encode(['summary' => 'ignored'])));
        $this->app->instance(AiService::class, $fakeAi);

        $this->actingAs($manager, 'api');
        $this->getJson('/api/tickets/' . $ticket->id . '/ai/summary')->assertStatus(403);

        $this->actingAs($employee, 'api');
        $this->getJson('/api/tickets/' . $ticket->id . '/ai/priority')->assertStatus(403);
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
        DB::table('categories')->insert([
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

    private function makeComment(int $ticketId, int $userId, string $commentText): TicketComment
    {
        return TicketComment::create([
            'ticketId' => $ticketId,
            'userId' => $userId,
            'commentText' => $commentText,
            'createdAt' => now(),
        ]);
    }

    private function makeHistory(int $ticketId, int $changedBy, ?string $oldStatus, ?string $newStatus, string $comment): void
    {
        DB::table('tickethistory')->insert([
            'ticketId' => $ticketId,
            'changedBy' => $changedBy,
            'oldStatus' => $oldStatus,
            'newStatus' => $newStatus,
            'comment' => $comment,
            'changedAt' => now(),
        ]);
    }
}

final class FakeAiService extends AiService
{
    public array $lastMessages = [];
    public array $lastOptions = [];

    public function __construct(protected AiResponse $response)
    {
        parent::__construct(new AiProviderFactory());
    }

    public function completeMessages(array $messages, ?string $provider = null, array $options = []): AiResponse
    {
        $this->lastMessages = $messages;
        $this->lastOptions = $options;

        return $this->response;
    }

    public function completePrompt(string $prompt, ?string $provider = null, array $options = []): AiResponse
    {
        return $this->completeMessages([
            ['role' => 'user', 'content' => $prompt],
        ], $provider, $options);
    }
}