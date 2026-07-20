<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BusinessCard extends Model implements HasMedia
{
    use InteractsWithMedia;

    /** The employee's own photo, uploaded from the mobile app. */
    public const PHOTO_COLLECTION = 'card_photo';

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    | draft             — created with the employee, not yet sent for review
    | submitted         — employee sent their personalisation to the owner
    | changes_requested — owner sent it back with review_comment
    | approved          — owner accepted it; ready to publish
    | rejected          — legacy (employee-reviews-owner flow)
    | published         — live at the public URL
    */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_CHANGES_REQUESTED = 'changes_requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'employee_id',
        'template_id',
        'card_data_json',
        'qr_code',
        'nfc_code',
        'public_url',
        'expiry_public_url',
        'is_active',

        // 🔥 NEW (workflow)
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',

        // Employee self-service personalisation + the owner's review note.
        'bio',
        'secondary_phone',
        'theme_json',
        'review_comment',
        'customized_at',

        // Frozen copy of what actually went live, so the employee can keep
        // editing without the public page changing under them.
        'published_snapshot',
        'published_at',
    ];

    protected $casts = [
        'card_data_json' => 'array',
        'is_active' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'customized_at' => 'datetime',
        'theme_json' => 'array',
        'published_snapshot' => 'array',
        'published_at' => 'datetime',
        // Cast so callers can compare via Carbon (e.g. `expiry_public_url->isPast()`)
        // and the resource serialises consistently.
        'expiry_public_url' => 'datetime',
    ];

    /**
     * Photo URL taken from the published snapshot, when one has been applied.
     *
     * Not a database column — set only by applyPublishedSnapshot() so the
     * public surfaces keep showing the approved photo while the employee is
     * already working on a replacement.
     */
    protected ?string $snapshotPhotoUrl = null;

    /**
     * Whether applyPublishedSnapshot() has run on this instance.
     *
     * Separate from $snapshotPhotoUrl because null is a MEANINGFUL snapshot
     * value: a card published with no photo freezes 'photo' => null. Keying
     * off the URL alone made photoUrl() fall through to the live media
     * collection in exactly that case, publishing the employee's unreviewed
     * photo the moment they reopened the card.
     */
    protected bool $snapshotApplied = false;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTO_COLLECTION)->singleFile();
    }

    /** Absolute URL of the employee's photo, or null when none is set. */
    public function photoUrl(): ?string
    {
        // Once a snapshot is applied it is the whole truth, including when it
        // says there was no photo. Falling through to the live collection here
        // would publish whatever the employee has uploaded since reopening.
        if ($this->snapshotApplied) {
            return $this->snapshotPhotoUrl;
        }

        $url = $this->getFirstMediaUrl(self::PHOTO_COLLECTION);

        return $url !== '' ? $url : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Publishing
    |--------------------------------------------------------------------------
    */

    /**
     * Freeze everything the public page renders.
     *
     * Called at publish time. The photo is copied out of the media library to
     * its own file: the collection is singleFile(), so the next photo the
     * employee uploads would otherwise delete the one the owner approved and
     * leave the live card with a broken image.
     */
    public function capturePublishedSnapshot(): array
    {
        return [
            'card_data_json'  => $this->card_data_json,
            'bio'             => $this->bio,
            'secondary_phone' => $this->secondary_phone,
            'theme'           => $this->effectiveTheme(),
            'photo'           => $this->copyPhotoForPublishing(),
            'captured_at'     => now()->toIso8601String(),
        ];
    }

    /**
     * Overlay the frozen snapshot onto this in-memory instance.
     *
     * Nothing is saved — it exists so the public page and the public JSON
     * endpoint can render the approved version through the ordinary resource
     * while the live row sits in draft for another round of edits.
     */
    public function applyPublishedSnapshot(): static
    {
        $snapshot = $this->published_snapshot;

        if (! is_array($snapshot)) {
            return $this;
        }

        $this->card_data_json  = $snapshot['card_data_json'] ?? $this->card_data_json;
        $this->bio             = $snapshot['bio'] ?? null;
        $this->secondary_phone = $snapshot['secondary_phone'] ?? null;
        $this->theme_json      = is_array($snapshot['theme'] ?? null) ? $snapshot['theme'] : $this->theme_json;
        $this->snapshotPhotoUrl = $snapshot['photo'] ?? null;
        $this->snapshotApplied  = true;

        return $this;
    }

    /**
     * Visible on the public URL?
     *
     * A card that was published once stays reachable while its employee edits
     * the next version — the snapshot is what gets served. Only deactivating
     * it, or letting the link expire, takes it down.
     */
    public function isPubliclyVisible(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expiry_public_url && $this->expiry_public_url->isPast()) {
            return false;
        }

        return $this->status === self::STATUS_PUBLISHED
            || is_array($this->published_snapshot);
    }

    /** Has been live at least once, so re-editing it needs the snapshot path. */
    public function hasBeenPublished(): bool
    {
        return $this->published_at !== null || is_array($this->published_snapshot);
    }

    private function copyPhotoForPublishing(): ?string
    {
        $media = $this->getFirstMedia(self::PHOTO_COLLECTION);

        if (! $media) {
            return null;
        }

        try {
            $source = $media->getPath();

            if (! is_file($source)) {
                return $this->photoUrl();
            }

            $extension = pathinfo($source, PATHINFO_EXTENSION) ?: 'jpg';
            $target    = 'published-cards/' . $this->id . '-' . substr(md5_file($source) ?: uniqid(), 0, 8) . '.' . $extension;

            \Illuminate\Support\Facades\Storage::disk('public')
                ->put($target, file_get_contents($source));

            return \Illuminate\Support\Facades\Storage::disk('public')->url($target);
        } catch (\Throwable $e) {
            // A card going live matters more than the frozen copy of its photo;
            // fall back to the live URL rather than failing the publish.
            \Illuminate\Support\Facades\Log::warning('Card photo snapshot failed: ' . $e->getMessage());

            return $this->photoUrl();
        }
    }

    /**
     * The template's theme with the employee's overrides applied.
     *
     * The template owns the base look (shared by the whole company); the
     * employee may override individual colours. Computed here so the mobile
     * app, the dashboard and the public page can't drift apart.
     */
    public function effectiveTheme(): array
    {
        $base = [
            'background' => '#0B1220',
            'text'       => '#FFFFFF',
            'primary'    => '#0EA5E9',
            'accent'     => '#22D3EE',
        ];

        $templateTheme = data_get($this->template?->design_json, 'theme', []);
        if (! is_array($templateTheme)) {
            $templateTheme = [];
        }

        $own = is_array($this->theme_json) ? $this->theme_json : [];

        return array_merge($base, array_filter($templateTheme), array_filter($own));
    }

    /** True while the employee is allowed to edit their own card. */
    public function isEmployeeEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_CHANGES_REQUESTED,
            self::STATUS_REJECTED,
        ], true);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function template()
    {
        return $this->belongsTo(BusinessCardTemplate::class, 'template_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(Employee::class, 'reviewed_by');
    }

    public function interactions()
    {
        return $this->hasMany(CardInteraction::class);
    }

    public function contactShares()
    {
        return $this->hasMany(CardContactShare::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (very useful for admin panels)
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
