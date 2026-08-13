<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function index(): JsonResponse
    {
        $workspaces = Workspace::withCount(['documents', 'conversations'])->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $workspaces,
        ]);
    }

    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']) . '-' . Str::random(5);
        }

        $workspace = Workspace::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Workspace created successfully',
            'data' => $workspace,
        ], 201);
    }

    public function show(Workspace $workspace): JsonResponse
    {
        $workspace->load(['documents', 'conversations', 'mcpServers']);

        return response()->json([
            'status' => 'success',
            'data' => $workspace,
        ]);
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): JsonResponse
    {
        $workspace->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Workspace updated successfully',
            'data' => $workspace,
        ]);
    }

    public function destroy(Workspace $workspace): JsonResponse
    {
        $workspace->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Workspace deleted successfully',
        ]);
    }
}
