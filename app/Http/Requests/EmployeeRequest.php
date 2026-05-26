<?php

namespace App\Http\Requests;

use App\Contracts\ValidationTranslatorInterface;
use App\Http\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmployeeRequest extends FormRequest
{


    public function __construct(
        protected ValidationTranslatorInterface $translator
    )
    {
        parent::__construct();
    }
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Only enforce the fields the dashboard form actually collects today.
        // role/department/iqama/email/phone/logo are all useful but optional
        // — they can be filled in later from the employee detail view.
        $employeeId = $this->route('employee') ?? $this->id;

        return [
            'company_id'      => ['required', 'exists:companies,id'],
            'branch_id'       => ['required', 'exists:company_branches,id'],
            'role_id'         => ['nullable', 'exists:roles,id'],
            'department_id'   => ['nullable', 'exists:departments,id'],
            'user_id'         => ['required', 'exists:users,id'],

            'employee_number' => ['required', 'string', 'unique:employees,employee_number,' . $employeeId],
            'iqama_number'    => ['nullable', 'string', 'unique:employees,iqama_number,' . $employeeId],

            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['nullable', 'email', 'unique:employees,email,' . $employeeId],
            'phone'           => ['nullable', 'string', 'unique:employees,phone,' . $employeeId],
            'status'          => ['required', 'in:active,inactive'],

            'logo'            => ['nullable', 'file', 'mimes:jpg,jpeg,png'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::success(null,
                $this->translator->transform($validator),
                422
            )
        );
    }
}
