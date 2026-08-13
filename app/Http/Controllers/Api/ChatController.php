<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AI\LLMDriverInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Workspace;
use App\Services\RAG\Pipeline\RAGPipelineEngine;
use App\Services\RAG\Pipeline\RAGRetrievalService;
use App\Services\RAG\Prompt\PromptBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function index(Workspace $workspace): JsonResponse
    {
        $conversations = $workspace->conversations()->withCount('messages')->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $conversations,
        ]);
    }

    public function createConversation(Request $request, Workspace $workspace): JsonResponse
    {
        $conversation = $workspace->conversations()->create([
            'title' => $request->input('title', 'New Conversation'),
            'model' => $request->input('model', $workspace->default_llm_model),
            'temperature' => $request->input('temperature', 0.70),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Conversation created successfully',
            'data' => $conversation,
        ], 201);
    }

    public function showConversation(Conversation $conversation): JsonResponse
    {
        $conversation->load(['messages' => fn ($q) => $q->orderBy('created_at', 'asc')]);

        return response()->json([
            'status' => 'success',
            'data' => $conversation,
        ]);
    }

    public function sendMessage(SendMessageRequest $request, Conversation $conversation, RAGPipelineEngine $ragEngine): JsonResponse
    {
        $userContent = $request->input('content');
        $topK = $request->input('top_k');
        $minSimilarity = $request->input('min_similarity');

        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $userContent,
        ]);

        $result = $ragEngine->run($conversation, $userContent, $topK, $minSimilarity);

        $assistantMessage = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $result['content'],
            'context_sources' => $result['context_sources'],
            'prompt_tokens' => $result['prompt_tokens'],
            'completion_tokens' => $result['completion_tokens'],
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
            ],
        ]);
    }

    public function stream(
        SendMessageRequest $request,
        Conversation $conversation,
        RAGRetrievalService $retrievalService,
        PromptBuilder $promptBuilder,
        LLMDriverInterface $llmDriver
    ): StreamedResponse {
        $userContent = $request->input('content');
        $topK = $request->input('top_k');
        $minSimilarity = $request->input('min_similarity');

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $userContent,
        ]);

        if (session()->isStarted()) {
            session()->save();
        }

        return new StreamedResponse(function () use ($conversation, $userContent, $topK, $minSimilarity, $retrievalService, $promptBuilder, $llmDriver) {
            $contextSources = $retrievalService->retrieveContext(
                workspaceId: $conversation->workspace_id,
                query: $userContent,
                limit: $topK,
                minSimilarity: $minSimilarity
            );

            echo "event: context\n";
            echo "data: " . json_encode(['sources' => $contextSources]) . "\n\n";
            @ob_flush();
            @flush();

            $history = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->take(10)
                ->get(['role', 'content'])
                ->toArray();

            $messages = $promptBuilder->buildAugmentedMessages(
                userQuery: $userContent,
                contextSources: $contextSources,
                systemPrompt: $conversation->workspace?->system_prompt,
                history: $history
            );

            $fullResponse = '';
            $model = $conversation->model ?: ($conversation->workspace?->default_llm_model ?: 'llama3.2');

            foreach ($llmDriver->chatStream($messages, $model, ['temperature' => (float) $conversation->temperature]) as $token) {
                $fullResponse .= $token;
                echo "event: token\n";
                echo "data: " . json_encode(['token' => $token]) . "\n\n";
                @ob_flush();
                @flush();
            }

            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $fullResponse,
                'context_sources' => $contextSources,
            ]);

            echo "event: done\n";
            echo "data: [DONE]\n\n";
            @ob_flush();
            @flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
