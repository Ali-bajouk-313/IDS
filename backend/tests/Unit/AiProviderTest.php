<?php

namespace Tests\Unit;

use App\Services\Ai\AiRequest;
use App\Services\Ai\Exceptions\AiProviderUnavailableException;
use App\Services\Ai\Providers\AzureOpenAiProvider;
use App\Services\Ai\Providers\GroqProvider;
use App\Services\Ai\Providers\OllamaProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Http::preventStrayRequests();

        parent::tearDown();
    }

    public function test_openai_provider_uses_backend_configuration_and_parses_the_response(): void
    {
        config([
            'ai.timeout' => 11,
            'ai.model' => 'gpt-4o-mini',
            'ai.providers.openai.api_key' => 'test-openai-key',
            'ai.providers.openai.base_url' => 'https://api.openai.com/v1',
            'ai.providers.openai.model' => 'gpt-4o-mini',
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'OpenAI summary',
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 7,
                    'completion_tokens' => 8,
                ],
            ], 200),
        ]);

        $response = (new OpenAiProvider())->complete(AiRequest::chat('Summarize the ticket'));

        $this->assertSame('openai', $response->provider);
        $this->assertSame('gpt-4o-mini', $response->model);
        $this->assertSame('OpenAI summary', $response->content);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-openai-key');
        });
    }

    public function test_groq_provider_uses_openai_compatible_endpoint_and_parses_the_response(): void
    {
        config([
            'ai.timeout' => 11,
            'ai.model' => 'openai/gpt-oss-20b',
            'ai.providers.groq.api_key' => 'test-groq-key',
            'ai.providers.groq.base_url' => 'https://api.groq.com/openai/v1',
            'ai.providers.groq.model' => 'openai/gpt-oss-20b',
        ]);

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Groq summary',
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 9,
                    'completion_tokens' => 10,
                ],
            ], 200),
        ]);

        $response = (new GroqProvider())->complete(AiRequest::chat('Summarize the ticket'));

        $this->assertSame('groq', $response->provider);
        $this->assertSame('openai/gpt-oss-20b', $response->model);
        $this->assertSame('Groq summary', $response->content);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-groq-key');
        });
    }

    public function test_azure_provider_uses_deployment_endpoint_and_parses_the_response(): void
    {
        config([
            'ai.timeout' => 11,
            'ai.model' => 'gpt-4o-mini',
            'ai.providers.azure_openai.api_key' => 'test-azure-key',
            'ai.providers.azure_openai.endpoint' => 'https://helpdeskpro.azure.com',
            'ai.providers.azure_openai.deployment' => 'helpdesk-gpt',
            'ai.providers.azure_openai.api_version' => '2024-10-21',
            'ai.providers.azure_openai.model' => 'gpt-4o-mini',
        ]);

        Http::fake([
            'https://helpdeskpro.azure.com/openai/deployments/helpdesk-gpt/chat/completions*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Azure summary',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = (new AzureOpenAiProvider())->complete(AiRequest::chat('Classify the ticket'));

        $this->assertSame('azure_openai', $response->provider);
        $this->assertSame('gpt-4o-mini', $response->model);
        $this->assertSame('Azure summary', $response->content);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/openai/deployments/helpdesk-gpt/chat/completions?api-version=2024-10-21')
                && $request->hasHeader('api-key', 'test-azure-key');
        });
    }

    public function test_ollama_provider_uses_local_endpoint_and_parses_the_response(): void
    {
        config([
            'ai.timeout' => 11,
            'ai.model' => 'llama3.1',
            'ai.providers.ollama.base_url' => 'http://127.0.0.1:11434',
            'ai.providers.ollama.model' => 'llama3.1',
        ]);

        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::response([
                'message' => [
                    'content' => 'Ollama answer',
                ],
            ], 200),
        ]);

        $response = (new OllamaProvider())->complete(AiRequest::chat('Suggest next steps'));

        $this->assertSame('ollama', $response->provider);
        $this->assertSame('llama3.1', $response->model);
        $this->assertSame('Ollama answer', $response->content);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://127.0.0.1:11434/api/chat';
        });
    }

    public function test_openai_provider_is_unavailable_without_api_key(): void
    {
        config([
            'ai.providers.openai.api_key' => '',
            'ai.providers.openai.base_url' => 'https://api.openai.com/v1',
        ]);

        $this->expectException(AiProviderUnavailableException::class);

        (new OpenAiProvider())->complete(AiRequest::chat('Summarize the ticket'));
    }
}