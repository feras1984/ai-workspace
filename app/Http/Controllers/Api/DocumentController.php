<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Jobs\ProcessDocumentIngestionJob;
use App\Models\Document;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    public function index(Workspace $workspace): JsonResponse
    {
        $documents = $workspace->documents()->withCount('chunks')->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $documents,
        ]);
    }

    public function store(StoreDocumentRequest $request, Workspace $workspace): JsonResponse
    {
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $title = $request->input('title') ?: $file->getClientOriginalName();

        $path = $file->storeAs("documents/{$workspace->id}", uniqid() . '.' . $extension, 'local');
        $fullPath = storage_path('app/' . $path);

        $document = $workspace->documents()->create([
            'title' => $title,
            'file_path' => $fullPath,
            'file_type' => $extension,
            'file_size' => $file->getSize(),
            'status' => 'pending',
        ]);

        dispatch(new ProcessDocumentIngestionJob($document));

        return response()->json([
            'status' => 'success',
            'message' => 'Document uploaded and queued for processing',
            'data' => $document,
        ], 201);
    }

    public function show(Document $document): JsonResponse
    {
        $document->loadCount('chunks');

        return response()->json([
            'status' => 'success',
            'data' => $document,
        ]);
    }

    public function destroy(Document $document): JsonResponse
    {
        if (file_exists($document->file_path)) {
            @unlink($document->file_path);
        }

        $document->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Document and vector embeddings deleted successfully',
        ]);
    }
}
