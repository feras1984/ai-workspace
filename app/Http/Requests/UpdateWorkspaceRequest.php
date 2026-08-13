<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $workspace = $this->route('workspace');
        $workspaceId = is_object($workspace) ? $workspace->id : $workspace;

        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:workspaces,slug,' . $workspaceId,
            'description' => 'nullable|string',
            'default_llm_model' => 'nullable|string|max:100',
            'default_embedding_model' => 'nullable|string|max:100',
            'system_prompt' => 'nullable|string',
        ];
    }
}
