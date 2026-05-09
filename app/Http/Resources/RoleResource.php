<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    protected $employees;

    public function __construct($resource, $employees = null)
    {
        parent::__construct($resource);
        $this->employees = $employees;
    }

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,

            // Permissions
            'permissions' => $this->permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ];
            }),

            // Employees assigned to role
            'employees' => $this->employees?->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
