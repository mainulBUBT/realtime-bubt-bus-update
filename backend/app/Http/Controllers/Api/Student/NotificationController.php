<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NotificationController extends Controller
{
    private function applicableCampaignsQuery(int $userId)
    {
        return DB::table('notification_campaigns as c')
            ->leftJoin('notification_campaign_reads as r', function ($join) use ($userId) {
                $join->on('r.campaign_id', '=', 'c.id')
                    ->where('r.user_id', '=', $userId);
            })
            ->whereNotNull('c.last_sent_at')
            ->where(function ($q) use ($userId) {
                $q->where('c.audience', '=', 'all_students')
                    ->orWhereExists(function ($sub) use ($userId) {
                        $sub->select(DB::raw(1))
                            ->from('notification_campaign_recipients as cr')
                            ->whereColumn('cr.campaign_id', 'c.id')
                            ->where('cr.user_id', '=', $userId);
                    });
            });
    }

    private function unreadCountForUser(int $userId): int
    {
        return (int) $this->applicableCampaignsQuery($userId)
            ->where(function ($q) {
                $q->whereNull('r.read_at')
                    ->orWhereRaw('r.read_at < c.last_sent_at');
            })
            ->count();
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 50);
        $userId = $request->user()->id;

        $paginator = $this->applicableCampaignsQuery($userId)
            ->select([
                'c.id',
                'c.title',
                'c.body',
                'c.type',
                'c.audience',
                'c.image_path',
                'c.sent_at',
                'c.last_sent_at',
                'r.read_at',
            ])
            ->selectRaw('(CASE WHEN r.read_at IS NULL OR r.read_at < c.last_sent_at THEN 1 ELSE 0 END) as is_unread')
            ->orderByDesc('c.last_sent_at')
            ->orderByDesc('c.id')
            ->paginate($perPage);

        $notifications = collect($paginator->items())->map(function ($n) {
            $imageUrl = null;
            if (!empty($n->image_path)) {
                $imageUrl = Storage::disk('public')->url($n->image_path);
            }

            return [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'type' => $n->type,
                'audience' => $n->audience,
                'image_url' => $imageUrl,
                'read_at' => $n->read_at ? \Illuminate\Support\Carbon::parse($n->read_at)->toISOString() : null,
                'sent_at' => $n->sent_at ? \Illuminate\Support\Carbon::parse($n->sent_at)->toISOString() : null,
                'last_sent_at' => $n->last_sent_at ? \Illuminate\Support\Carbon::parse($n->last_sent_at)->toISOString() : null,
                'is_unread' => (bool) ($n->is_unread ?? false),
                // Keep compatibility with existing frontend time field.
                'created_at' => $n->last_sent_at ? \Illuminate\Support\Carbon::parse($n->last_sent_at)->toISOString() : null,
            ];
        });

        return response()->json([
            'data' => $notifications->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
                'unread_count' => $this->unreadCountForUser($userId),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->unreadCountForUser($request->user()->id),
        ]);
    }

    public function markRead(Request $request, $id): JsonResponse
    {
        $userId = $request->user()->id;

        $exists = $this->applicableCampaignsQuery($userId)
            ->where('c.id', '=', (int) $id)
            ->exists();

        if (!$exists) {
            abort(404);
        }

        DB::table('notification_campaign_reads')->upsert([
            [
                'campaign_id' => (int) $id,
                'user_id' => $userId,
                'read_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['campaign_id', 'user_id'], ['read_at', 'updated_at']);

        return response()->json([
            'message' => 'Marked as read',
            'unread_count' => $this->unreadCountForUser($userId),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $now = now();

        $ids = $this->applicableCampaignsQuery($userId)
            ->where(function ($q) {
                $q->whereNull('r.read_at')
                    ->orWhereRaw('r.read_at < c.last_sent_at');
            })
            ->select('c.id')
            ->orderByDesc('c.last_sent_at')
            ->limit(5000)
            ->pluck('id')
            ->toArray();

        if (!empty($ids)) {
            $rows = array_map(fn($campaignId) => [
                'campaign_id' => (int) $campaignId,
                'user_id' => $userId,
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ], $ids);

            DB::table('notification_campaign_reads')->upsert(
                $rows,
                ['campaign_id', 'user_id'],
                ['read_at', 'updated_at']
            );
        }

        return response()->json(['message' => 'All notifications marked as read']);
    }
}
