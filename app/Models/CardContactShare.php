<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The reverse of a card share: details a visitor sent back to the card's owner.
 *
 * Rows are created by anonymous, unauthenticated visitors, so nothing here is
 * trusted identity — `verification` records how (if at all) the address was
 * proven, and is 'none' for everything the plain public form accepts.
 */
class CardContactShare extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_card_id',
        'employee_id',
        'company_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'note',
        'source',
        'verification',
        'google_sub',
        'consent_at',
        'notified_at',
        'is_read',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'consent_at'  => 'datetime',
        'notified_at' => 'datetime',
        'is_read'     => 'boolean',
    ];

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * The only scope the mobile inbox is ever allowed to read through: an
     * employee sees contacts sent to their own card and nothing else.
     */
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function businessCard()
    {
        return $this->belongsTo(BusinessCard::class, 'business_card_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
