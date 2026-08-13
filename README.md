# Laravel AI Workspace

An enterprise-grade **AI Workspace** built on Laravel 12, PostgreSQL `pgvector`, local Ollama embeddings & LLMs, and the Model Context Protocol (MCP).

---

## Key Features

- **Multi-Tenant Workspaces**: Dynamic workspaces with isolated system prompts, model defaults, and file collections.
- **RAG Pipeline**: Ingestion, text parsing, sliding window/recursive chunking, vector embedding generation, and fast hybrid vector search via PostgreSQL `pgvector` HNSW indexes.
- **Local AI (Ollama Integration)**: 100% privacy-focused, zero external API costs using local Ollama models (`nomic-embed-text`, `llama3.2`, `qwen2.5`, etc.).
- **Model Context Protocol (MCP)**: Dynamic tool call engine supporting stdio process wrappers and remote SSE transports.
- **Real-Time Token Streaming**: Server-Sent Events (SSE) chat streaming endpoint for fast, interactive user experiences.

---

## Quick Start

1. **Start Database & Services**:
   ```bash
   docker compose up -d
   ```
2. **Setup Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. **Run Migrations**:
   ```bash
   php artisan migrate
   ```
4. **Run Test Suite**:
   ```bash
   php artisan test
   ```

---

## Documentation

Full system architecture, database schema, API documentation, and driver reference can be found in [DOCUMENTATION.md](file:///d:/PROJECTS/AI/ai-workspace/DOCUMENTATION.md).
