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
        // `$this->permissions` is the Spatie BelongsToMany relation — when
        // eager-loaded it's always a Collection. Guard against the rare case
        // where an upstream caller injects a scalar (e.g., a withCount
        // shadowing the relation), observed in production as
        // "Call to a member function map() on int".
        $permissionsRaw = $this->permissions;
        $permissions = is_iterable($permissionsRaw)
            ? collect($permissionsRaw)
                ->map(fn ($permission) => [
                    'id'   => $permission->id ?? null,
                    'name' => $permission->name ?? null,
                ])
                ->values()
            : [];

        // `$employees` is only populated for show() via the custom
        // constructor — index()/store()/update() leave it null, and reading
        // `$this->employees` then falls through to the underlying Role
        // model. Some Role rows expose `employees` as an auto-injected
        // counter (int) which broke `?->map()`. is_iterable() filters that.
        $employeesRaw = $this->employees;
        $employees = is_iterable($employeesRaw)
            ? collect($employeesRaw)
                ->map(fn ($user) => [
                    'id'    => $user->id ?? null,
                    'name'  => $user->name ?? null,
                    'email' => $user->email ?? null,
                ])
                ->values()
            : null;

        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'guard_name'  => $this->guard_name,
            'permissions' => $permissions,
            'employees'   => $employees,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
