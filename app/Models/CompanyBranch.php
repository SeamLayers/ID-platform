<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyBranch extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'company_branches';

    protected $fillable = [
        'company_id',
        'name',
        'address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeSearch($query, $value)
    {
        return $query->where(function ($q) use ($value) {
            $q->where('name', 'like', "%{$value}%")
                ->orWhere('address', 'like', "%{$value}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors (Optional)
    |--------------------------------------------------------------------------
    */

    public function getFullAddressAttribute()
    {
        return $this->address ?? 'N/A';
    }
}
