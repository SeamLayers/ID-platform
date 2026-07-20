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
        // Deliberately still a FLAT ARRAY, not a paginator: both shipped
        // clients map `data` straight to a list, and switching to a paginated
        // envelope would break them until every one is redeployed. Capped
        // instead, so it is no longer an unbounded full-history dump.
        $limit = (int) $request->input('limit', 50);
        $limit = $limit > 0 ? min($limit, 200) : 50;

        $notifications = Notification::where('user_id', $request->user()->id)
            // Cursor rather than ?page=, for the same reason the envelope stays
            // flat: it is purely additive, so an older client that never sends
            // it keeps getting exactly what it got before. Ids descend with
            // created_at, so "older than this one" is a single indexed compare.
            ->when($request->filled('before_id'), function ($q) use ($request) {
                $q->where('id', '<', (int) $request->input('before_id'));
            })
            ->when($request->boolean('unread_only'), function ($q) {
                $q->where('is_read', false);
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    // What it's about + its payload, so the client can deep-link
                    // straight to the card instead of just showing text.
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'is_read' => (bool) $notification->is_read,
                    'created_at' => $notification->created_at,
                ];
            });

        return ResponseHelper::success($notifications, __('messages.data_retrieved'));
    }

    /** Unread count for the app's bell badge — one cheap request. */
    public function unreadCount(Request $request)
    {
        return ResponseHelper::success(
            ['count' => Notification::where('user_id', $request->user()->id)
                ->where('is_read', false)
                ->count()],
            __('messages.data_retrieved')
        );
    }

    /** Clear the badge in one call instead of one request per row. */
    public function markAllAsRead(Request $request)
    {
        $updated = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return ResponseHelper::success(['updated' => $updated], __('messages.data_updated'));
    }


    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        $notification->is_read = 1;
        $notification->save();

        return ResponseHelper::success(true, __('messages.data_updated'));
    }

    /** Dismiss a single row. Scoped, so one user can't delete another's. */
    public function destroy($id)
    {
        Notification::where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail()
            ->delete();

        return ResponseHelper::success(true, __('messages.data_deleted'));
    }
}
