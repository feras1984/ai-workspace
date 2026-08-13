<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MCPServer extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'mcp_servers';

    protected $fillable = [
        'workspace_id',
        'name',
        'transport_type',
        'command',
        'url',
        'environment_vars',
        'is_active',
        'capabilities',
    ];

    protected $casts = [
        'environment_vars' => 'array',
        'is_active' => 'boolean',
        'capabilities' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
