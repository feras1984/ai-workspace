<?php

namespace App\Services\RAG\Pipeline;

use App\Contracts\AI\LLMDriverInterface;
use App\Models\Conversation;
use App\Services\RAG\Prompt\PromptBuilder;

class RAGPipelineEngine
{
    public function __construct(
        protected RAGRetrievalService $retrievalService,
        protected PromptBuilder $promptBuilder,
        protected LLMDriverInterface $llmDriver
    ) {}

    /**
     * Run RAG completion pipeline and return response with context citations.
     *
     * @return array{content: string, role: string, context_sources: array, prompt_tokens: int|null, completion_tokens: int|null}
     */
    public function run(
        Conversation $conversation,
        string $userQuery,
        ?int $topK = null,
        ?float $minSimilarity = null
    ): array {
        $contextSources = $this->retrievalService->retrieveContext(
            workspaceId: $conversation->workspace_id,
            query: $userQuery,
            limit: $topK,
            minSimilarity: $minSimilarity
        );

        $history = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->take(10)
            ->get(['role', 'content'])
            ->toArray();

        $messages = $this->promptBuilder->buildAugmentedMessages(
            userQuery: $userQuery,
            contextSources: $contextSources,
            systemPrompt: $conversation->workspace?->system_prompt,
            history: $history
        );

        $model = $conversation->model ?: ($conversation->workspace?->default_llm_model ?: 'llama3.2');
        $llmResult = $this->llmDriver->chat(
            messages: $messages,
            model: $model,
            options: ['temperature' => (float) $conversation->temperature]
        );

        return [
            'content' => $llmResult['content'],
            'role' => $llmResult['role'] ?? 'assistant',
            'context_sources' => $contextSources,
            'prompt_tokens' => $llmResult['prompt_tokens'] ?? null,
            'completion_tokens' => $llmResult['completion_tokens'] ?? null,
        ];
    }
}
