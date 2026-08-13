<?php

namespace Tests\Feature\Api;

use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_document_and_queue_ingestion(): void
    {
        Queue::fake();
        Storage::fake('local');

        $workspace = Workspace::create([
            'name' => 'Docs Hub',
            'slug' => 'docs-hub',
        ]);

        $file = UploadedFile::fake()->create('architecture.md', 100, 'text/markdown');

        $response = $this->postJson("/api/v1/workspaces/{$workspace->id}/documents", [
            'file' => $file,
            'title' => 'System Architecture',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'System Architecture');

        $this->assertDatabaseHas('documents', [
            'workspace_id' => $workspace->id,
            'title' => 'System Architecture',
        ]);
    }
}
