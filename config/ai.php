<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ollama AI Configuration
    |--------------------------------------------------------------------------
    */
    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'default_model' => env('OLLAMA_DEFAULT_MODEL', 'llama3.2'),
        'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
        'embedding_dimension' => (int) env('OLLAMA_EMBEDDING_DIMENSION', 768),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retrieval-Augmented Generation (RAG) Configuration
    |--------------------------------------------------------------------------
    */
    'rag' => [
        'default_top_k' => (int) env('RAG_DEFAULT_TOP_K', 5),
        'min_similarity' => (float) env('RAG_MIN_SIMILARITY', 0.5),
        'chunk_size' => (int) env('RAG_CHUNK_SIZE', 1000),
        'chunk_overlap' => (int) env('RAG_CHUNK_OVERLAP', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Context Protocol (MCP) Configuration
    |--------------------------------------------------------------------------
    */
    'mcp' => [
        'default_timeout' => (int) env('MCP_DEFAULT_TIMEOUT', 60),
    ],

];
