<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'company_id' => $this->company_id,
            'branch_id'  => $this->branch_id,
            'name'       => $this->name,
            'code'       => $this->code,

            'display_name' => $this->display_name,

            'company' => CompanyResource::make(
                $this->whenLoaded('company')
            ),

            'employees' => EmployeeResource::collection(
                $this->whenLoaded('employees')
            ),

            'employees_count' => $this->employees?->count(),

            'created_at' => $this->created_at,
        ];
    }
}
