<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * In-app notification persisted in the `notifications` table.
 *
 * Distinct from Laravel's built-in notifications (which use a UUID primary
 * key and JSON `data` blob). Ours are simple title/message rows owned by a
 * user, surfaced to the dashboard bell icon and the mobile app's
 * `/notifications` list.
 */
class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        // What the notification is ABOUT (card_submitted, card_approved, …) and
        // its payload (card_id, …), so the app can deep-link from the list.
        'type',
        'data',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
