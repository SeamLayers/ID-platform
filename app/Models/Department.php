<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'branch_id'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Free-text search used by the departments list.
     *
     * Grouped in its own closure so the OR-chain can never leak out and
     * widen the tenancy/soft-delete predicates the caller already applied.
     * Matches the department itself plus the two names shown in the same
     * row of the table, so searching what you can see finds what you see.
     */
    public function scopeSearch($query, $value)
    {
        $term = trim((string) $value);

        if ($term === '') {
            return $query;
        }

        $like = '%' . $term . '%';

        return $query->where(function ($q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhereHas('company', fn ($c) => $c->where('name', 'like', $like))
                ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', $like));
        });
    }
    /**
     * =========================
     * Relationships
     * =========================
     */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id');
    }
    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, 'branch_id');
    }
}
