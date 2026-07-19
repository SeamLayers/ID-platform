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
    ];

    protected $casts = [
        'card_data_json' => 'array',
        'is_active' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'customized_at' => 'datetime',
        'theme_json' => 'array',
        // Cast so callers can compare via Carbon (e.g. `expiry_public_url->isPast()`)
        // and the resource serialises consistently.
        'expiry_public_url' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTO_COLLECTION)->singleFile();
    }

    /** Absolute URL of the employee's photo, or null when none is set. */
    public function photoUrl(): ?string
    {
        $url = $this->getFirstMediaUrl(self::PHOTO_COLLECTION);

        return $url !== '' ? $url : null;
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
