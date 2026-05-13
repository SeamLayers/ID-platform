<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessCardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->id,

            'employee_id'           => $this->employee_id,

            'template'              => new BusinessCardTemplateResource(
                $this->whenLoaded('template')
            ),

            'card_data_json'        => $this->card_data_json,

            'qr_code'               => $this->qr_code,
            'nfc_code'              => $this->nfc_code,

            'public_url'            => $this->public_url,
            'expiry_public_url'     => $this->expiry_public_url,

            'is_active'             => (bool) $this->is_active,

            'status'                => $this->status,

            'submitted_at'          => $this->submitted_at,
            'reviewed_at'           => $this->reviewed_at,

            'reviewed_by'           => $this->reviewed_by,

            'rejection_reason'      => $this->rejection_reason,

            'created_at'            => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'            => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
