<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:workspaces,slug',
            'description' => 'nullable|string',
            'default_llm_model' => 'nullable|string|max:100',
            'default_embedding_model' => 'nullable|string|max:100',
            'system_prompt' => 'nullable|string',
        ];
    }
}
