<?php

namespace App\Http\Requests;

use App\Contracts\ValidationTranslatorInterface;
use App\Http\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProjectRequest extends FormRequest
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
        // start/end dates and employee assignment are optional at creation
        // time — owners typically scaffold a project and assign members
        // afterwards via /employee-project.
        return [
            'company_id'     => ['required', 'exists:companies,id'],
            'name'           => ['required', 'string', 'max:255'],
            'start_date'     => ['nullable', 'date'],
            'end_date'       => ['nullable', 'date', 'after_or_equal:start_date'],

            'employee_ids'   => ['nullable', 'array'],
            'employee_ids.*' => ['exists:employees,id'],
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
