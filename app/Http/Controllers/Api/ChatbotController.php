<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatbotController extends Controller
{
    public function __construct(private ChatbotService $chatbotService) {}

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|min:1|max:1000',
            'context' => 'nullable|array',
        ]);

        $message = $request->input('message');
        $context = $request->input('context', []);

        $response = $this->chatbotService->generateResponse($message, $context);

        return response()->json([
            'reply' => $response['reply'],
            'source' => $response['source'],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'available' => true,
            'ai_enabled' => $this->chatbotService->isAiEnabled(),
            'languages' => ['fr', 'wo', 'en'],
        ]);
    }
}
