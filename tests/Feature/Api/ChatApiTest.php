<?php

namespace Tests\Feature\Api;

use App\Contracts\AI\EmbeddingDriverInterface;
use App\Contracts\AI\LLMDriverInterface;
use App\Contracts\AI\VectorStoreInterface;
use App\Models\Conversation;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_conversation_and_send_message(): void
    {
        $workspace = Workspace::create([
            'name' => 'AI Assistant',
            'slug' => 'ai-assistant',
        ]);

        $conversation = Conversation::create([
            'workspace_id' => $workspace->id,
            'title' => 'General Discussion',
        ]);

        $mockEmbeddingDriver = $this->createMock(EmbeddingDriverInterface::class);
        $mockEmbeddingDriver->method('embedText')->willReturn([0.1, 0.2, 0.3]);
        $this->app->instance(EmbeddingDriverInterface::class, $mockEmbeddingDriver);

        $mockVectorStore = $this->createMock(VectorStoreInterface::class);
        $mockVectorStore->method('similaritySearch')->willReturn([]);
        $this->app->instance(VectorStoreInterface::class, $mockVectorStore);

        $mockLLMDriver = $this->createMock(LLMDriverInterface::class);
        $mockLLMDriver->method('chat')->willReturn([
            'role' => 'assistant',
            'content' => 'Hello! How can I assist you today?',
            'prompt_tokens' => 10,
            'completion_tokens' => 8,
        ]);
        $this->app->instance(LLMDriverInterface::class, $mockLLMDriver);

        $response = $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'content' => 'Hello AI',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.assistant_message.content', 'Hello! How can I assist you today?');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hello AI',
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Hello! How can I assist you today?',
        ]);
    }
}
