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
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
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
}
