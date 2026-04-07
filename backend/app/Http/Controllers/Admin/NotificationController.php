<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationCampaign;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NotificationController extends Controller
{
    public function index()
    {
        $studentCount = User::where('role', 'student')->count();

        $campaigns = NotificationCampaign::query()
            ->withCount('recipients')
            ->orderByDesc('last_sent_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.notifications.index', compact('campaigns', 'studentCount'));
    }

    public function students(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = User::query()
            ->where('role', 'student')
            ->orderBy('name')
            ->select(['id', 'name', 'email']);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%');
            });
        }

        return response()->json([
            'data' => $query->limit(25)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'audience' => 'required|in:all_students,selected_students',
            'user_ids' => 'required_if:audience,selected_students|nullable|array',
            'user_ids.*' => 'exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'type' => 'nullable|string|in:info,warning,alert',
            'image' => 'nullable|image|max:5120',
        ]);

        $type = $validated['type'] ?? 'info';
        $now = now();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('notification-campaigns', 'public');
        }

        $campaign = NotificationCampaign::create([
            'audience' => $validated['audience'],
            'title' => $validated['title'],
            'body' => $validated['body'],
            'type' => $type,
            'image_path' => $imagePath,
            'created_by' => $request->user()?->id,
            'sent_at' => $now,
            'last_sent_at' => $now,
            'resend_count' => 0,
        ]);

        if ($validated['audience'] === 'selected_students') {
            $campaign->recipients()->sync($validated['user_ids'] ?? []);
        }

        $this->sendPushForCampaign($campaign);

        $recipientCount = $validated['audience'] === 'all_students'
            ? User::where('role', 'student')->count()
            : $campaign->recipients()->count();

        return redirect()->route('admin.notifications.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Notification sent to ' . $recipientCount . ' student(s).']]);
    }

    public function edit(NotificationCampaign $campaign)
    {
        $campaign->loadCount('recipients');

        $selectedRecipients = [];
        if ($campaign->audience === 'selected_students') {
            $selectedRecipients = $campaign->recipients()
                ->orderBy('name')
                ->get(['users.id', 'users.name', 'users.email'])
                ->toArray();
        }

        return view('admin.notifications.edit', compact('campaign', 'selectedRecipients'));
    }

    public function update(Request $request, NotificationCampaign $campaign)
    {
        $validated = $request->validate([
            'audience' => 'required|in:all_students,selected_students',
            'user_ids' => 'required_if:audience,selected_students|nullable|array',
            'user_ids.*' => 'exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'type' => 'nullable|string|in:info,warning,alert',
            'image' => 'nullable|image|max:5120',
            'remove_image' => 'nullable|in:1',
        ]);

        $imagePath = $campaign->image_path;
        if ($request->boolean('remove_image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = null;
        }
        if ($request->hasFile('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('notification-campaigns', 'public');
        }

        $campaign->update([
            'audience' => $validated['audience'],
            'title' => $validated['title'],
            'body' => $validated['body'],
            'type' => $validated['type'] ?? 'info',
            'image_path' => $imagePath,
        ]);

        if ($validated['audience'] === 'selected_students') {
            $campaign->recipients()->sync($validated['user_ids'] ?? []);
        } else {
            $campaign->recipients()->sync([]);
        }

        return redirect()->route('admin.notifications.edit', $campaign)
            ->with('toastr', [['type' => 'success', 'message' => 'Notification saved.']]);
    }

    public function resend(NotificationCampaign $campaign)
    {
        $now = now();
        $campaign->update([
            'last_sent_at' => $now,
            'resend_count' => (int) $campaign->resend_count + 1,
        ]);

        $this->sendPushForCampaign($campaign);

        return redirect()->route('admin.notifications.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Notification resent. Unread reset for students.']]);
    }

    private function sendPushForCampaign(NotificationCampaign $campaign): void
    {
        $data = [
            'type' => $campaign->type,
            'campaign_id' => (string) $campaign->id,
        ];

        try {
            $fcmService = app(FcmService::class);

            if ($campaign->audience === 'all_students') {
                $fcmService->sendToTopic('all_students', $campaign->title, $campaign->body, $data);
                return;
            }

            $tokens = $campaign->recipients()
                ->whereNotNull('users.fcm_token')
                ->pluck('users.fcm_token')
                ->filter()
                ->values()
                ->toArray();

            if (!empty($tokens)) {
                $fcmService->sendToTokens($tokens, $campaign->title, $campaign->body, $data);
            }
        } catch (\Throwable $e) {
            logger()->error('Admin notification FCM failed: ' . $e->getMessage());
        }
    }
}
