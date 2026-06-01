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
            // phone + user_type are consumed by the mobile profile screen and
            // the dashboard's auth persistence layer. They were missing before,
            // so the app's profile showed "—" for phone and the dashboard
            // stored an undefined user_type.
            'phone' => $this->phone,
            'user_type' => $this->user_type,
            'device_token' => $this->device_token,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            // Null on the /profile ("me") endpoint — only the login flow mints
            // and attaches a fresh token; clients keep their stored one.
            'token' => $this->token,
        ];
    }
}
