<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeProjectResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'employee_id' => $this->employee_id,
            'project_id'  => $this->project_id,
            'assigned_at' => $this->assigned_at,

            'employee'    => $this->whenLoaded('employee'),
            'project'     => $this->whenLoaded('project'),
        ];
    }
}
