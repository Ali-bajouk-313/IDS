<?php

return [
    'provider' => env('AI_PROVIDER', 'openai'),
    'model' => env('AI_MODEL', 'gpt-4o-mini'),
    'timeout' => (int) env('AI_TIMEOUT', 30),
    'temperature' => (float) env('AI_TEMPERATURE', 0.2),
    'max_tokens' => (int) env('AI_MAX_TOKENS', 1024),

    'providers' => [
        'openai' => [
            'api_key' => trim((string) env('AI_OPENAI_API_KEY', '')),
            'base_url' => rtrim(env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/'),
            'model' => env('AI_OPENAI_MODEL', env('AI_MODEL', 'gpt-4o-mini')),
        ],

        'azure_openai' => [
            'api_key' => env('AI_AZURE_API_KEY'),
            'endpoint' => rtrim(env('AI_AZURE_ENDPOINT', ''), '/'),
            'deployment' => env('AI_AZURE_DEPLOYMENT', env('AI_MODEL', 'gpt-4o-mini')),
            'api_version' => env('AI_AZURE_API_VERSION', '2024-10-21'),
            'model' => env('AI_AZURE_MODEL', env('AI_MODEL', 'gpt-4o-mini')),
        ],

        'groq' => [
            'api_key' => trim((string) env('AI_GROQ_API_KEY', '')),
            'base_url' => rtrim(env('AI_GROQ_BASE_URL', 'https://api.groq.com/openai/v1'), '/'),
            'model' => env('AI_GROQ_MODEL', env('AI_MODEL', 'llama-3.1-8b-instant')),
        ],

        'ollama' => [
            'base_url' => rtrim(env('AI_OLLAMA_BASE_URL', 'http://127.0.0.1:11434'), '/'),
            'model' => env('AI_OLLAMA_MODEL', env('AI_MODEL', 'llama3.1')),
        ],
    ],
];