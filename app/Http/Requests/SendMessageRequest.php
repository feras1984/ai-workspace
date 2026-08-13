<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
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
            'content' => 'required|string',
            'top_k' => 'nullable|integer|min:1|max:20',
            'min_similarity' => 'nullable|numeric|min:0|max:1',
        ];
    }
}
