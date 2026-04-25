<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id'   => 'required|exists:companies,id',
            'name'         => 'required|string|max:255',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',

            'employee_ids'   => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
        ];
    }
}
