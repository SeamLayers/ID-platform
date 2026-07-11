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
        'name','email','phone','status','position'
    ];

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Next auto-generated employee number for a company: EMP-{companyId}-{NNNN}.
     *
     * Locks the company row first so two concurrent creates for the same
     * company serialize instead of racing to the same sequence value, and
     * checks collisions withTrashed() because employee_number has a GLOBAL
     * unique index that soft-deleted rows still occupy.
     */
    public static function nextNumberForCompany(int $companyId): string
    {
        // Serialize concurrent creates per company (no-op outside a
        // transaction, callers wrap in DB::transaction).
        Company::whereKey($companyId)->lockForUpdate()->first();

        $sequence = static::withTrashed()
                ->where('company_id', $companyId)
                ->count() + 1;

        do {
            $number = sprintf('EMP-%d-%04d', $companyId, $sequence);

            $taken = static::withTrashed()
                ->where('employee_number', $number)
                ->exists();

            $sequence++;
        } while ($taken);

        return $number;
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
