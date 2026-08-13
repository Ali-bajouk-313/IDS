<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\Ai\AiProviderFactory;
use App\Services\Ai\AiResponse;
use App\Services\Ai\AiService;
use App\Services\Ai\Exceptions\AiRequestException;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KnowledgeBaseAiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->text('description')->nullable();
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
    }

    public function test_it_support_can_ask_the_knowledge_base_and_receive_contextual_guidance(): void
    {
        $roles = $this->seedRoles();
        $support = $this->makeUser($roles['IT Support'], 'support-kb@example.com', 'Support KB');
        $employee = $this->makeUser($roles['Employee'], 'employee-kb@example.com', 'Employee KB', 1);

        $category = $this->makeCategory('Network', 'VPN, Wi-Fi, and connectivity issues');
        $ticket = $this->makeTicket('TICKET-7001', $employee->id, $support->id, 'Resolved', 'High', $category->id, now()->subDays(4), now()->subDay());
        $this->makeComment($ticket->id, $support->id, 'Reset the VPN client and clear cached credentials before reconnecting.');

        $fakeAi = new KnowledgeBaseFakeAiService(
            new AiResponse(
                provider: 'fake',
                model: 'demo-model',
                content: json_encode([
                    'answer' => 'Start by checking the VPN client, cached credentials, and the active profile. If it still fails, reinstall the client.',
                    'troubleshootingSteps' => [
                        'Confirm the user can reach the network gateway.',
                        'Clear cached credentials and restart the VPN client.',
                        'Reinstall the client if the issue persists.',
                    ],
                ])
            )
        );

        $this->app->instance(AiService::class, $fakeAi);

        $this->actingAs($support, 'api');

        $response = $this->postJson('/api/ai/knowledge-base/ask', [
            'question' => 'How do I fix a VPN connection that keeps asking for credentials?',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.answer', 'Start by checking the VPN client, cached credentials, and the active profile. If it still fails, reinstall the client.')
            ->assertJsonPath('data.troubleshootingSteps.1', 'Clear cached credentials and restart the VPN client.');

        $this->assertSame('knowledge_base', $fakeAi->lastOptions['metadata']['feature']);
        $this->assertStringContainsString('VPN, Wi-Fi, and connectivity issues', $fakeAi->lastMessages[1]['content']);
        $this->assertStringContainsString('Reset the VPN client and clear cached credentials before reconnecting.', $fakeAi->lastMessages[1]['content']);
        $this->assertSame('Network', $response->json('data.sourceContext.categories.0.name'));
        $this->assertSame('TICKET-7001', $response->json('data.sourceContext.tickets.0.ticketNumber'));
    }

    public function test_employee_is_denied_access_to_the_knowledge_base_assistant(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'employee-denied-kb@example.com', 'Employee Denied', 1);

        $this->actingAs($employee, 'api');

        $this->postJson('/api/ai/knowledge-base/ask', [
            'question' => 'How do I troubleshoot Outlook?',
        ])->assertStatus(403);
    }

    public function test_guest_is_rejected_before_the_controller_runs(): void
    {
        $this->postJson('/api/ai/knowledge-base/ask', [
            'question' => 'How do I troubleshoot Outlook?',
        ])->assertStatus(401);
    }

    public function test_ai_failure_is_returned_as_an_error_response(): void
    {
        $roles = $this->seedRoles();
        $support = $this->makeUser($roles['IT Support'], 'support-failure-kb@example.com', 'Support Failure');

        $this->app->instance(AiService::class, new KnowledgeBaseThrowingAiService());

        $this->actingAs($support, 'api');

        $this->postJson('/api/ai/knowledge-base/ask', [
            'question' => 'How do I fix a printer queue issue?',
        ])->assertStatus(503);
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

    private function makeCategory(string $name, string $description): Category
    {
        return Category::create([
            'categoryName' => $name,
            'description' => $description,
            'createdAt' => now(),
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
            'title' => 'VPN access issue',
            'description' => 'The user cannot connect to the VPN after a laptop update.',
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
}

final class KnowledgeBaseFakeAiService extends AiService
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
}

final class KnowledgeBaseThrowingAiService extends AiService
{
    public function __construct()
    {
        parent::__construct(new AiProviderFactory());
    }

    public function completeMessages(array $messages, ?string $provider = null, array $options = []): AiResponse
    {
        throw new AiRequestException('AI provider unavailable for knowledge base requests.', 503);
    }
}