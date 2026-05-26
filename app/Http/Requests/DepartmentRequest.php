<?php

namespace App\Http\Requests;

use App\Contracts\ValidationTranslatorInterface;
use App\Http\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DepartmentRequest extends FormRequest
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
        // Departments often span multiple branches (Engineering, HR, etc.),
        // so branch_id is captured when relevant but not required. `code`
        // also matches the dashboard UX (placeholder + hint text both
        // signal "optional").
        //
        // Note: the previous version had a comma-split bug
        // (`'branch_id' => 'required', 'exists:company_branches,id'`) which
        // dropped the exists rule and silently forced branch_id required.
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'name'       => ['required', 'string', 'max:255'],
            'code'       => ['nullable', 'string', 'max:50'],
            'branch_id'  => ['nullable', 'exists:company_branches,id'],
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
