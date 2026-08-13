<?php

namespace App\Http\Controllers;

use App\Services\Ai\ChatbotAssistantService;
use App\Services\Ai\Exceptions\AiProviderUnavailableException;
use App\Services\Ai\Exceptions\AiRequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AiChatController extends Controller
{
    public function __construct(protected ChatbotAssistantService $chatbotAssistantService)
    {
    }

    public function chat(Request $request)
    {
        $user = auth('api')->user();

        if (!$user || !$user->role) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
            'conversation' => 'nullable|array|max:6',
            'conversation.*.role' => 'required_with:conversation|string|in:user,assistant',
            'conversation.*.content' => 'required_with:conversation|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $conversation = array_slice((array) $request->input('conversation', []), -6);

        try {
            $result = $this->chatbotAssistantService->chat($user, (string) $request->input('message'), $conversation);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (AiProviderUnavailableException $exception) {
            Log::warning('AI chatbot unavailable.', [
                'provider' => 'openai',
                'user_id' => $user->id,
                'message' => 'provider_unavailable',
            ]);

            return response()->json(['message' => 'The AI service is temporarily unavailable. Please try again.'], 503);
        } catch (AiRequestException $exception) {
            Log::warning('AI chatbot request failed.', [
                'provider' => 'openai',
                'user_id' => $user->id,
                'status_code' => $exception->statusCode ?? 0,
            ]);

            $status = $exception->statusCode && $exception->statusCode >= 400 && $exception->statusCode <= 599
                ? $exception->statusCode
                : 502;

            return response()->json(['message' => 'The AI service is temporarily unavailable. Please try again.'], $status);
        } catch (Throwable $exception) {
            Log::error('AI chatbot unexpected error.', [
                'provider' => 'openai',
                'user_id' => $user->id,
                'exception' => class_basename($exception),
            ]);

            return response()->json(['message' => 'The AI service is temporarily unavailable. Please try again.'], 500);
        }
    }
}