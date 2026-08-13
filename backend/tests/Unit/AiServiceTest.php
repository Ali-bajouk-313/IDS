<?php

namespace Tests\Unit;

use App\Services\Ai\AiProviderFactory;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResponse;
use App\Services\Ai\AiService;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Exceptions\AiProviderUnavailableException;
use Tests\TestCase;

class AiServiceTest extends TestCase
{
    public function test_service_delegates_to_the_selected_provider(): void
    {
        $provider = new FakeProvider();
        $factory = new class ($provider) extends AiProviderFactory {
            public function __construct(private AiProviderInterface $provider)
            {
            }

            public function make(?string $provider = null): AiProviderInterface
            {
                return $this->provider;
            }
        };

        $service = new AiService($factory);
        $response = $service->completePrompt('Summarize this ticket');

        $this->assertSame('fake-provider', $response->provider);
        $this->assertSame('demo-model', $response->model);
        $this->assertSame('Stubbed AI response', $response->content);
        $this->assertSame('Summarize this ticket', $provider->lastRequest?->messages[0]['content'] ?? null);
    }

    public function test_factory_rejects_unsupported_provider_names(): void
    {
        $factory = new AiProviderFactory();

        $this->expectException(AiProviderUnavailableException::class);

        $factory->make('not-a-real-provider');
    }

    public function test_service_reports_the_configured_provider_name(): void
    {
        config(['ai.provider' => 'ollama']);

        $service = new AiService(new AiProviderFactory());

        $this->assertSame('ollama', $service->currentProvider());
    }
}

final class FakeProvider implements AiProviderInterface
{
    public ?AiRequest $lastRequest = null;

    public function name(): string
    {
        return 'fake-provider';
    }

    public function complete(AiRequest $request): AiResponse
    {
        $this->lastRequest = $request;

        return new AiResponse(
            provider: $this->name(),
            model: 'demo-model',
            content: 'Stubbed AI response',
            raw: ['stub' => true],
            usage: ['prompt_tokens' => 1, 'completion_tokens' => 2],
        );
    }
}