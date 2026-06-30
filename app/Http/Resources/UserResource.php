<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;


class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_type' => $this->user_type,
            'device_token' => $this->device_token,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'must_reset_password' => (bool) $this->must_reset_password,
            'token' => $this->token,

            'employee' => EmployeeResource::make(
                $this->whenLoaded('employee')
            ),
        ];
    }
}
