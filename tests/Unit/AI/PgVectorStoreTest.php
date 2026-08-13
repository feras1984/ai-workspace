<?php

namespace Tests\Unit\AI;

use App\Models\Document;
use App\Models\Workspace;
use App\Services\AI\PgVector\PgVectorStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PgVectorStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_and_searches_chunks_by_similarity(): void
    {
        $workspace = Workspace::create([
            'name' => 'Test Workspace',
            'slug' => 'test-workspace',
        ]);

        $document = Document::create([
            'workspace_id' => $workspace->id,
            'title' => 'Sample Doc',
            'file_path' => '/storage/sample.pdf',
            'file_type' => 'pdf',
        ]);

        $store = new PgVectorStore();

        // Chunk 1 (similar vector to query [1.0, 0.0, 0.0])
        $chunkId1 = $store->saveChunk(
            workspaceId: $workspace->id,
            documentId: $document->id,
            chunkIndex: 0,
            content: 'Artificial Intelligence and LLMs',
            embedding: [0.95, 0.05, 0.0],
            tokenCount: 10
        );

        // Chunk 2 (dissimilar vector)
        $chunkId2 = $store->saveChunk(
            workspaceId: $workspace->id,
            documentId: $document->id,
            chunkIndex: 1,
            content: 'Baking bread recipes',
            embedding: [0.0, 1.0, 0.0],
            tokenCount: 8
        );

        $results = $store->similaritySearch(
            workspaceId: $workspace->id,
            queryVector: [1.0, 0.0, 0.0],
            limit: 5,
            minSimilarity: 0.5
        );

        $this->assertCount(1, $results);
        $this->assertEquals($chunkId1, $results[0]['chunk_id']);
        $this->assertEquals('Artificial Intelligence and LLMs', $results[0]['content']);
        $this->assertGreaterThan(0.9, $results[0]['similarity']);
    }
}
