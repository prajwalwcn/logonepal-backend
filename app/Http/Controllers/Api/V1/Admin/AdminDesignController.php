<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserDesign;
use App\Services\Admin\AdminActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDesignController extends Controller
{
    public function __construct(private readonly AdminActivityLogger $activity)
    {
    }

    public function show(UserDesign $design): JsonResponse
    {
        $design->load(['user:id,name,email', 'reports.reporter:id,name', 'moderator:id,name']);

        return response()->json(['data' => $design]);
    }

    public function moderate(Request $request, UserDesign $design): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,flagged,hidden'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $design->update([
            'moderation_status' => $data['status'],
            'moderation_note' => $data['note'] ?? null,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        $this->activity->log(
            'design.moderated',
            $design,
            "Set design {$design->name} moderation to {$data['status']}.",
            ['note' => $data['note'] ?? null],
        );

        return response()->json(['data' => $design->fresh(['moderator:id,name'])]);
    }

    public function archive(Request $request, UserDesign $design): JsonResponse
    {
        $design->update(['is_archived' => true]);
        $this->activity->log('design.archived', $design, "Archived design {$design->name}.");

        return response()->json(['data' => $design->fresh(), 'message' => 'Design archived.']);
    }

    public function restore(UserDesign $design): JsonResponse
    {
        $design->update(['is_archived' => false]);
        $this->activity->log('design.restored', $design, "Restored design {$design->name}.");

        return response()->json(['data' => $design->fresh(), 'message' => 'Design restored.']);
    }
}
