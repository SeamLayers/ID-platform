<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class Company extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'name',
        'commercial_register',
        'phone',
        'email',
        'user_id'
    ];
    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('company_logo')
            ->singleFile(); // only one logo per company
    }
    /**
     * Casts (optional but useful)
     */
    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * =========================
     * Relations
     * =========================
     */

    // Example: Company has many employees
    public function employees()
    {
        return $this->hasMany(Employee::class, 'company_id');
    }
    // Example: Company has many branches (if applicable)
    public function branches()
    {
        return $this->hasMany(CompanyBranch::class, 'company_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
