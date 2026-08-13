<?php

namespace App\Http\Controllers;

use App\Services\Ai\Exceptions\AiProviderUnavailableException;
use App\Services\Ai\Exceptions\AiRequestException;
use App\Services\Ai\KnowledgeBaseAssistantService;
use Illuminate\Http\Request;
use Throwable;

class KnowledgeBaseAiController extends Controller
{
    public function __construct(protected KnowledgeBaseAssistantService $knowledgeBaseAssistantService)
    {
    }

    public function ask(Request $request)
    {
        $user = auth('api')->user();

        if (!$user || !$user->role || !in_array($user->role->roleName, ['Admin', 'Manager', 'IT Support'], true)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $question = trim((string) $request->input('question', ''));

        if ($question === '') {
            return response()->json(['message' => 'The question field is required.'], 422);
        }

        try {
            $result = $this->knowledgeBaseAssistantService->askQuestion($question);

            return response()->json([
                'success' => true,
                'data' => array_merge([
                    'question' => $question,
                    'generatedAt' => now()->toIso8601String(),
                ], $result),
            ]);
        } catch (AiProviderUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        } catch (AiRequestException $exception) {
            $status = $exception->statusCode && $exception->statusCode >= 400 && $exception->statusCode <= 599
                ? $exception->statusCode
                : 502;

            return response()->json(['message' => $exception->getMessage()], $status);
        } catch (Throwable $exception) {
            return response()->json(['message' => 'Unable to answer the knowledge base question.'], 500);
        }
    }
}