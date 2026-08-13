<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'context_sources',
        'mcp_tool_calls',
        'prompt_tokens',
        'completion_tokens',
    ];

    protected $casts = [
        'context_sources' => 'array',
        'mcp_tool_calls' => 'array',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
