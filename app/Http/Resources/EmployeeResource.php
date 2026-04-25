<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'employee_number' => $this->employee_number,
            'iqama_number'    => $this->iqama_number,
            'name'            => $this->name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'status'          => $this->status,

            'company'         => $this->whenLoaded('company'),
            'branch'          => $this->whenLoaded('branch'),
            'role'            => $this->whenLoaded('role'),
            'department'      => $this->whenLoaded('department'),
            'user'            => $this->whenLoaded('user'),
            'projects'        => $this->whenLoaded('projects'),
            'business_card'   => $this->whenLoaded('businessCard'),

            'logo'            => $this->getFirstMediaUrl('employee_logo'),

            'created_at'      => $this->created_at,
        ];
    }
}
