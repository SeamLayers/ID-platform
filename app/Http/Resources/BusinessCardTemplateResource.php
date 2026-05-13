<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class BusinessCardTemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'company_id'    => $this->company_id,
            'name'          => $this->name,
            'design_json'   => $this->design_json,
            'is_default'    => (bool) $this->is_default,
            'created_at'    => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'    => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
