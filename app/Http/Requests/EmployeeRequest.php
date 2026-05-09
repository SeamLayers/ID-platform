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
        return [
            'company_id'     => 'required|exists:companies,id',
            'branch_id'      => 'required|exists:company_branches,id',
            'role_id'        => 'required|exists:roles,id',
            'department_id'   => 'required|exists:departments,id',
            'user_id'        => 'required|exists:users,id',

            'employee_number' => 'required|string|unique:employees,employee_number,' . $this->id,
            'iqama_number'    => 'required|unique:employees,iqama_number',

            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:employees,email',
            'phone'           => 'required|unique:employees,phone',
            'status'          => 'required|in:active,inactive',

            'logo'            => 'required|file|mimes:jpg,jpeg,png'
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
