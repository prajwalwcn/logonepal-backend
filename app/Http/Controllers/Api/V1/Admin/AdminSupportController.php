<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentReport;
use App\Models\SupportTicket;
use App\Models\UserDesign;
use App\Services\Admin\AdminActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSupportController extends Controller
{
    public function __construct(private readonly AdminActivityLogger $activity)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'max:24'],
            'category' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', 'string', 'max:20'],
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tickets = SupportTicket::query()
            ->with(['user:id,name,email', 'assignee:id,name'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->where(fn ($inner) => $inner->where('subject', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            })
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 30));

        $reports = ContentReport::query()
            ->with(['reporter:id,name,email', 'reviewer:id,name'])
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'data' => [
                'tickets' => $tickets->items(),
                'reports' => $reports,
                'stats' => [
                    'open' => SupportTicket::query()->where('status', 'open')->count(),
                    'in_progress' => SupportTicket::query()->where('status', 'in_progress')->count(),
                    'resolved' => SupportTicket::query()->where('status', 'resolved')->count(),
                    'open_reports' => ContentReport::query()->where('status', 'open')->count(),
                ],
            ],
            'meta' => ['current_page' => $tickets->currentPage(), 'last_page' => $tickets->lastPage(), 'total' => $tickets->total()],
        ]);
    }

    public function showTicket(SupportTicket $ticket): JsonResponse
    {
        return response()->json(['data' => $ticket->load(['user:id,name,email', 'assignee:id,name', 'replies.user:id,name'])]);
    }

    public function storeTicket(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'subject' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['sometimes', 'in:support,bug,feature,feedback,report'],
            'priority' => ['sometimes', 'in:low,normal,high,urgent'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $ticket = SupportTicket::create($data);
        $this->activity->log('support.ticket.created', $ticket, "Created ticket {$ticket->subject}.");

        return response()->json(['data' => $ticket], 201);
    }

    public function updateTicket(Request $request, SupportTicket $ticket): JsonResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', 'in:open,in_progress,resolved,closed'],
            'priority' => ['sometimes', 'in:low,normal,high,urgent'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
        ]);

        if (($data['status'] ?? null) === 'resolved' && $ticket->resolved_at === null) {
            $data['resolved_at'] = now();
        }

        $ticket->update($data);
        $this->activity->log('support.ticket.updated', $ticket, "Updated ticket {$ticket->subject}.", $data);

        return response()->json(['data' => $ticket->fresh(['user:id,name,email', 'assignee:id,name', 'replies.user:id,name'])]);
    }

    public function reply(Request $request, SupportTicket $ticket): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
            'is_internal' => ['sometimes', 'boolean'],
        ]);

        $reply = $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'is_internal' => (bool) ($data['is_internal'] ?? false),
        ]);

        if (! $reply->is_internal && $ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        $this->activity->log('support.ticket.replied', $ticket, "Replied to ticket {$ticket->subject}.");

        return response()->json(['data' => $reply->load('user:id,name')], 201);
    }

    public function updateReport(Request $request, ContentReport $report): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,reviewing,resolved,rejected'],
            'resolution_note' => ['nullable', 'string', 'max:5000'],
            'moderation_status' => ['nullable', 'in:approved,flagged,hidden'],
        ]);

        $report->update([
            'status' => $data['status'],
            'resolution_note' => $data['resolution_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if (($data['moderation_status'] ?? null) !== null && $report->reportable instanceof UserDesign) {
            $report->reportable->update([
                'moderation_status' => $data['moderation_status'],
                'moderated_by' => $request->user()->id,
                'moderated_at' => now(),
                'moderation_note' => $data['resolution_note'] ?? null,
            ]);
        }

        $this->activity->log('support.report.updated', $report, "Updated content report {$report->uuid}.", $data);

        return response()->json(['data' => $report->fresh(['reporter:id,name,email', 'reviewer:id,name'])]);
    }
}
