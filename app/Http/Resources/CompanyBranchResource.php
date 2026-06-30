<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyBranchResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'company_id' => $this->company_id,
            'name'       => $this->name,
            'address'    => $this->address,
            'company' => CompanyResource::make(
                $this->whenLoaded('company')
            ),
            'created_at' => $this->created_at,
        ];
    }
}
