<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // Get notifications for the logged-in user, latest first
        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'is_read' => (bool) $notification->is_read,
                    'created_at' => $notification->created_at,
                ];
            });

        return ResponseHelper::success($notifications, 'Notifications retrieved successfully');
    }


    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        $notification->is_read = 1;
        $notification->save();

        return ResponseHelper::success(true, 'Notification marked as read');
    }

}
