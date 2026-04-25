<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;


class CompanyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'commercial_register' => $this->commercial_register,
            'phone' => $this->phone,
            'email' => $this->email,

            // Owner relation
            'owner' => $this->whenLoaded('owner', function () {
                return [
                    'id' => $this->owner->id,
                    'name' => $this->owner->name,
                    'email' => $this->owner->email,
                ];
            }),

            // Employees
            'employees' => $this->whenLoaded('employees'),

            // Branches
            'branches' => $this->whenLoaded('branches'),

            // Media (Spatie)
            'logo' => $this->getFirstMediaUrl('company_logo'),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
