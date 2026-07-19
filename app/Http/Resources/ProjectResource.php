<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'company_id'  => $this->company_id,
            'name'        => $this->name,
            'start_date'  => $this->start_date,
            'end_date'    => $this->end_date,

            // Nested company so the dashboard can show the company name instead
            // of a raw #id. Light inline shape (avoids eager-loading the full
            // CompanyResource graph); only present when the relation is loaded.
            'company' => $this->whenLoaded('company', fn () => [
                'id'   => $this->company->id,
                'name' => $this->company->name,
            ]),

            'employees' => EmployeeResource::collection(
                $this->whenLoaded('employees')
            ),

            'employees_count' => $this->employees?->count(),

            'created_at'  => $this->created_at,
        ];
    }
}
