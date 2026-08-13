# Laravel AI Workspace Documentation

Welcome to the **Laravel AI Workspace** documentation. This system provides a multi-tenant AI workspace engine powered by Laravel 12, PostgreSQL `pgvector`, Ollama (for local embeddings & LLMs), and the Model Context Protocol (MCP).

---

## 1. Prerequisites & Environment Setup

### Prerequisites
* **PHP**: 8.2 or higher
* **Composer**: 2.x
* **Docker Desktop**: For running PostgreSQL 16 with `pgvector`
* **Ollama**: Local AI runner (listening at `http://localhost:11434`)

### Step 1: Start PostgreSQL + pgvector Container
Run Docker Compose in the project root:
```bash
docker compose up -d
```
This starts PostgreSQL 16 on port `5432` with pre-installed `pgvector`.

### Step 2: Configure Environment
Copy `.env.example` to `.env` if not already done:
```bash
cp .env.example .env
```
Ensure your `.env` contains:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ai_workspace
DB_USERNAME=postgres
DB_PASSWORD=postgres

OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_DEFAULT_MODEL=llama3.2
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
OLLAMA_EMBEDDING_DIMENSION=768
OLLAMA_TIMEOUT=300
```

### Step 3: Pull Ollama Models
Ensure Ollama is running and download the default embedding and LLM models:
```bash
ollama pull nomic-embed-text
ollama pull llama3.2
```

### Step 4: Run Migrations
```bash
php artisan migrate
```

---

## 2. Architecture & Directory Structure

The system follows a clean Domain/Service-Oriented Architecture under `app/`:

```
app/
├── Casts/
│   └── VectorCast.php            # Eloquent cast for pgvector arrays
├── Contracts/
│   ├── AI/
│   │   ├── EmbeddingDriverInterface.php
│   │   ├── LLMDriverInterface.php
│   │   └── VectorStoreInterface.php
│   ├── RAG/
│   └── MCP/
├── Models/
│   ├── Workspace.php
│   ├── Document.php
│   ├── DocumentChunk.php
│   ├── Conversation.php
│   ├── Message.php
│   └── MCPServer.php
├── Services/
│   ├── AI/
│   │   ├── Ollama/
│   │   │   ├── OllamaLLMDriver.php
│   │   │   ├── OllamaEmbeddingDriver.php
│   │   │   └── DTOs/
│   │   │       ├── ChatMessageDTO.php
│   │   │       └── ChatResponseStreamDTO.php
│   │   └── PgVector/
│   │       └── PgVectorStore.php
│   ├── RAG/                      # Document parsers, chunkers & RAG pipeline engine
│   └── MCP/                      # MCP client, transports (stdio/sse) & tool registry
└── Providers/
    └── AppServiceProvider.php    # Interface container bindings
```

---

## 3. Database Schema Overview

### Core Tables
1. **`workspaces`**: Dynamic tenant container storing system prompts and default models per workspace.
2. **`documents`**: Ingested files (PDF, Markdown, Text, DOCX, CSV) with status tracking.
3. **`document_chunks`**: Text segments with high-dimensional `vector(768)` embeddings and HNSW Cosine distance index (`vector_cosine_ops`).
4. **`conversations`**: Chat sessions tied to workspaces.
5. **`messages`**: Exchanged chat messages with RAG citations (`context_sources`) and MCP tool call logs.
6. **`mcp_servers`**: Connected Model Context Protocol servers (stdio/SSE).

---

## 4. Key Core Interfaces & Usage Examples

### Embedding Driver
```php
use App\Contracts\AI\EmbeddingDriverInterface;

$embeddingDriver = app(EmbeddingDriverInterface::class);

// Generate embedding for single text
$vector = $embeddingDriver->embedText("Artificial Intelligence in Laravel");

// Batch embedding
$vectors = $embeddingDriver->embedBatch(["Text 1", "Text 2"]);
```

### Vector Store (pgvector Search)
```php
use App\Contracts\AI\VectorStoreInterface;

$vectorStore = app(VectorStoreInterface::class);

// Cosine similarity search (1 - cosine_distance >= 0.5)
$results = $vectorStore->similaritySearch(
    workspaceId: $workspaceId,
    queryVector: $vector,
    limit: 5,
    minSimilarity: 0.5
);
```

### LLM Driver (Chat & Streaming)
```php
use App\Contracts\AI\LLMDriverInterface;
use App\Services\AI\Ollama\DTOs\ChatMessageDTO;

$llmDriver = app(LLMDriverInterface::class);

// Non-streaming completion
$response = $llmDriver->chat([
    ChatMessageDTO::make('user', 'Explain RAG pipelines'),
]);

// Streaming token generator
foreach ($llmDriver->chatStream($messages) as $token) {
    echo $token;
}
```

---

## 5. Testing Guide

Run the automated PHPUnit test suite:
```bash
php artisan test
```

All driver unit tests and vector store tests use isolated in-memory fallbacks so tests complete instantly without external dependencies.
