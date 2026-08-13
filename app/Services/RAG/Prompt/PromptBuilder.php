<?php

namespace App\Services\RAG\Prompt;

use App\Services\AI\Ollama\DTOs\ChatMessageDTO;

class PromptBuilder
{
    /**
     * Build message stack augmented with RAG context snippets.
     *
     * @param array<array{content: string, similarity: float, metadata: array}> $contextSources
     * @param array<array{role: string, content: string}> $history
     * @return array<ChatMessageDTO>
     */
    public function buildAugmentedMessages(
        string $userQuery,
        array $contextSources = [],
        ?string $systemPrompt = null,
        array $history = []
    ): array {
        $messages = [];

        $systemText = $systemPrompt ?: "You are an intelligent AI workspace assistant. Answer user queries accurately using the provided document context sources.";

        if (!empty($contextSources)) {
            $contextBlock = "\n\n--- RELEVANT DOCUMENT CONTEXT SOURCES ---\n";
            foreach ($contextSources as $index => $source) {
                $sourceNum = $index + 1;
                $similarityPercent = round(($source['similarity'] ?? 0) * 100, 1);
                $contextBlock .= "[Source #{$sourceNum} (Relevance: {$similarityPercent}%)]:\n" . $source['content'] . "\n\n";
            }
            $contextBlock .= "--- END CONTEXT SOURCES ---\n\nUse the above context sources to answer the user's request. If the answer cannot be determined from the context, state that clearly.";
            $systemText .= $contextBlock;
        }

        $messages[] = ChatMessageDTO::make('system', $systemText);

        foreach ($history as $msg) {
            $messages[] = ChatMessageDTO::make($msg['role'] ?? 'user', $msg['content'] ?? '');
        }

        $messages[] = ChatMessageDTO::make('user', $userQuery, null, $contextSources);

        return $messages;
    }
}
