<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['company_id','name','start_date','end_date'];
    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }
    public function employees() {
        return $this->belongsToMany(Employee::class, 'employee_projects');
    }

    // A project belongs to a company (via company_id). Missing this relation
    // caused "Call to undefined method App\Models\Project::company()" whenever
    // the list endpoint / resource referenced $project->company.
    public function company() {
        return $this->belongsTo(Company::class);
    }
}
