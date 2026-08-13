<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Workspaces
    Route::apiResource('workspaces', WorkspaceController::class);

    // Documents
    Route::get('workspaces/{workspace}/documents', [DocumentController::class, 'index']);
    Route::post('workspaces/{workspace}/documents', [DocumentController::class, 'store']);
    Route::get('documents/{document}', [DocumentController::class, 'show']);
    Route::delete('documents/{document}', [DocumentController::class, 'destroy']);

    // Conversations & Chat
    Route::get('workspaces/{workspace}/conversations', [ChatController::class, 'index']);
    Route::post('workspaces/{workspace}/conversations', [ChatController::class, 'createConversation']);
    Route::get('conversations/{conversation}', [ChatController::class, 'showConversation']);
    Route::post('conversations/{conversation}/messages', [ChatController::class, 'sendMessage']);
    Route::get('conversations/{conversation}/stream', [ChatController::class, 'stream']);
    Route::post('conversations/{conversation}/stream', [ChatController::class, 'stream']);
});
