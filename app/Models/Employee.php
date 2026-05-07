<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Employee extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'company_id','branch_id','role_id','department_id','user_id',
        'employee_number','iqama_number',
        'name','email','phone','status'
    ];

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }
    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('employee_logo')
            ->singleFile(); // only one logo per company
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function branch() {
        return $this->belongsTo(CompanyBranch::class, 'branch_id');
    }

    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function projects() {
        return $this->belongsToMany(Project::class, 'employee_projects');
    }

    public function businessCard() {
        return $this->hasOne(BusinessCard::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
