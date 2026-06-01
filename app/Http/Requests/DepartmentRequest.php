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
        // `branch_id` is NOT NULL in the DB (see
        // 2026_04_21_195624_add_branch_id_to_departments_table) so it must be
        // supplied — the dashboard form now includes a branch picker. `code`
        // is genuinely optional (nullable column + "optional" UX hint).
        //
        // Note: the original rule had a comma-split bug
        // (`'branch_id' => 'required', 'exists:company_branches,id'`) which
        // dropped the exists check; it's now a proper single rule.
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id'  => ['required', 'exists:company_branches,id'],
            'name'       => ['required', 'string', 'max:255'],
            'code'       => ['nullable', 'string', 'max:50'],
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
