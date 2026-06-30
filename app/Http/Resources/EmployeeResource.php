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
            'position'          => $this->position,

            'company' => CompanyResource::make(
                $this->whenLoaded('company')
            ),
            'branch' => CompanyBranchResource::make(
                $this->whenLoaded('branch')
            ),

            'role' => RoleResource::make(
                $this->whenLoaded('role')
            ),

            'department' => DepartmentResource::make(
                $this->whenLoaded('department')
            ),

            'user' => UserResource::make(
                $this->whenLoaded('user')
            ),

            'projects' => ProjectResource::collection(
                $this->whenLoaded('projects')
            ),

            'business_card' => BusinessCardResource::make(
                $this->whenLoaded('businessCard')
            ),

            'logo'            => $this->getFirstMediaUrl('employee_logo'),

            'created_at'      => $this->created_at,
        ];
    }
}
