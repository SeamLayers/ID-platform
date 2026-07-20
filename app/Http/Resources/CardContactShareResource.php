<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CardContactShareResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'full_name'  => $this->full_name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'note'       => $this->note,
            'source'     => $this->source,
            // Collapsed to a boolean for the client: the app only cares whether
            // the address was proven, not which provider proved it.
            'verified'   => $this->verification !== 'none',
            'is_read'    => (bool) $this->is_read,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
