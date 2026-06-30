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

            'employees' => EmployeeResource::collection(
                $this->whenLoaded('employees')
            ),

            'employees_count' => $this->employees?->count(),

            'created_at'  => $this->created_at,
        ];
    }
}
