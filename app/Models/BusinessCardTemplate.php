<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCardTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'design_json',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'design_json' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function businessCards()
    {
        return $this->hasMany(BusinessCard::class, 'template_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function setAsDefault()
    {
        // ensure only ONE default per company
        self::where('company_id', $this->company_id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}
