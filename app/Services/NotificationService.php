<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Creates in-app notification rows and (best-effort) pushes them to the user's
 * device via FCM. The in-app row is the source of truth; a failed/absent push
 * never breaks the calling request.
 */
class NotificationService
{
    private FirebaseService $firebase;

    public function __construct(?FirebaseService $firebase = null)
    {
        $this->firebase = $firebase ?: new FirebaseService();
    }

    /**
     * Store an in-app notification for $user and push it to their device when a
     * real FCM token is present ('web-dashboard' is the pre-permission
     * placeholder and is skipped).
     */
    public function notifyUser(?User $user, string $title, string $message, array $data = []): void
    {
        if (! $user) {
            return;
        }

        try {
            Notification::create([
                'user_id' => $user->id,
                'title'   => $title,
                'message' => $message,
                // Persisted now (they used to reach push only and be dropped
                // here), so GET /notifications can tell the client what the
                // notification refers to.
                'type'    => $data['type'] ?? null,
                'data'    => $data ?: null,
                'is_read' => false,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Notification create failed: ' . $e->getMessage());
        }

        // 'web-dashboard' is the pre-permission placeholder the dashboard sends;
        // 'flutter_dev_token' is the literal the mobile app sends at login
        // because auth/login requires the field. Neither is a real FCM token,
        // so pushing to them only fills the log with warnings.
        $token = $user->device_token;
        if ($token && ! in_array($token, ['web-dashboard', 'flutter_dev_token'], true)) {
            try {
                // FCM data values must be strings.
                $stringData = array_map(fn ($v) => (string) $v, $data);
                $this->firebase->sendToDevice($token, $title, $message, $stringData);
            } catch (\Throwable $e) {
                Log::warning('FCM push failed: ' . $e->getMessage());
            }
        }
    }
}
