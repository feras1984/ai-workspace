<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');
            DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
        }

        Schema::create('document_chunks', function (Blueprint $table) use ($driver) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->integer('chunk_index');
            $table->text('content');
            $table->integer('token_count')->default(0);
            $table->jsonb('metadata')->nullable();
            if ($driver !== 'pgsql') {
                $table->text('embedding')->nullable();
            }
            $table->timestamps();
        });

        $dimension = config('ai.ollama.embedding_dimension', 768);

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE document_chunks ADD COLUMN embedding vector({$dimension});");
            DB::statement("CREATE INDEX idx_document_chunks_embedding_hnsw ON document_chunks USING hnsw (embedding vector_cosine_ops) WITH (m = 16, ef_construction = 64);");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
