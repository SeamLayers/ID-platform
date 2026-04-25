<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id'     => 'required|exists:companies,id',
            'branch_id'      => 'required|exists:company_branches,id',
            'role_id'        => 'nullable|exists:roles,id',
            'department_id'   => 'nullable|exists:departments,id',
            'user_id'        => 'required|exists:users,id',

            'employee_number' => 'required|string|unique:employees,employee_number,' . $this->id,
            'iqama_number'    => 'nullable|string',

            'name'            => 'required|string|max:255',
            'email'           => 'nullable|email',
            'phone'           => 'nullable|string',
            'status'          => 'nullable|in:active,inactive',

            'logo'            => 'nullable|file|mimes:jpg,jpeg,png'
        ];
    }
}
