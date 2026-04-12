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
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
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
        ], [
            'image.max' => 'Image size must be 5MB or smaller.',
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

        $pushResult = $this->sendPushForCampaign($campaign);

        $recipientCount = $validated['audience'] === 'all_students'
            ? User::where('role', 'student')->count()
            : $campaign->recipients()->count();

        $toastType = ($pushResult['success'] ?? false) ? 'success' : 'warning';
        $toastMessage = ($pushResult['success'] ?? false)
            ? 'Notification sent to ' . $recipientCount . ' student(s).'
            : 'Notification saved, but push delivery failed: ' . ($pushResult['error'] ?? 'Unknown FCM error');

        return redirect()->route('admin.notifications.index')
            ->with('toastr', [['type' => $toastType, 'message' => $toastMessage]]);
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
        ], [
            'image.max' => 'Image size must be 5MB or smaller.',
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

        $now = now();
        $campaign->update([
            'last_sent_at' => $now,
            'resend_count' => (int) $campaign->resend_count + 1,
        ]);

        $pushResult = $this->sendPushForCampaign($campaign);

        $toastType = ($pushResult['success'] ?? false) ? 'success' : 'warning';
        $toastMessage = ($pushResult['success'] ?? false)
            ? 'Notification saved and sent successfully.'
            : 'Notification saved, but push delivery failed: ' . ($pushResult['error'] ?? 'Unknown FCM error');

        return redirect()->route('admin.notifications.index')
            ->with('toastr', [['type' => $toastType, 'message' => $toastMessage]]);
    }

    public function resend(NotificationCampaign $campaign)
    {
        $now = now();
        $campaign->update([
            'last_sent_at' => $now,
            'resend_count' => (int) $campaign->resend_count + 1,
        ]);

        $pushResult = $this->sendPushForCampaign($campaign);

        $toastType = ($pushResult['success'] ?? false) ? 'success' : 'warning';
        $toastMessage = ($pushResult['success'] ?? false)
            ? 'Notification resent. Unread reset for students.'
            : 'Notification resent, but push delivery failed: ' . ($pushResult['error'] ?? 'Unknown FCM error');

        return redirect()->route('admin.notifications.index')
            ->with('toastr', [['type' => $toastType, 'message' => $toastMessage]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sendPushForCampaign(NotificationCampaign $campaign): array
    {
        $data = [
            'type' => $campaign->type,
            'campaign_id' => (string) $campaign->id,
            'target_route' => 'map',
        ];

        $imageUrl = $campaign->image_path
            ? Storage::disk('public')->url($campaign->image_path)
            : null;

        try {
            $fcmService = app(FcmService::class);

            if ($campaign->audience === 'all_students') {
                return $fcmService->sendToTopic('all_students', $campaign->title, $campaign->body, $data, $imageUrl);
            }

            $tokens = $campaign->recipients()
                ->whereNotNull('users.fcm_token')
                ->pluck('users.fcm_token')
                ->filter()
                ->values()
                ->toArray();

            if (empty($tokens)) {
                $result = [
                    'success' => false,
                    'target' => 'tokens',
                    'token_count' => 0,
                    'error' => 'No registered student devices found for the selected audience. Ask students to open the app and allow notifications first.',
                ];

                logger()->warning('Admin notification skipped: no recipient tokens', [
                    'campaign_id' => $campaign->id,
                    'audience' => $campaign->audience,
                ]);

                return $result;
            }

            return $fcmService->sendToTokens($tokens, $campaign->title, $campaign->body, $data, $imageUrl);
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ];

            logger()->error('Admin notification FCM failed', [
                'campaign_id' => $campaign->id,
                'audience' => $campaign->audience,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return $result;
        }
    }
}
