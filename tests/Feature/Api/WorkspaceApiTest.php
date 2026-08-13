<?php

namespace Tests\Feature\Api;

use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_workspace_via_api(): void
    {
        $response = $this->postJson('/api/v1/workspaces', [
            'name' => 'Data Science Hub',
            'description' => 'Workspace for ML documentation',
            'default_llm_model' => 'llama3.2',
            'system_prompt' => 'You are a data science assistant.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Data Science Hub');

        $this->assertDatabaseHas('workspaces', [
            'name' => 'Data Science Hub',
        ]);
    }

    public function test_can_list_and_show_workspaces(): void
    {
        $workspace = Workspace::create([
            'name' => 'Analytics Workspace',
            'slug' => 'analytics-workspace',
        ]);

        $response = $this->getJson('/api/v1/workspaces');
        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $showResponse = $this->getJson("/api/v1/workspaces/{$workspace->id}");
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.id', $workspace->id);
    }
}
