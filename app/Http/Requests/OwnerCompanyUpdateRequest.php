<?php

namespace App\Http\Requests;

use App\Http\Helpers\ResponseHelper;
use App\Models\Company;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Validation for an OWNER editing their OWN company from the dashboard's
 * "My Company" screen (POST /dashboard/owner/company).
 *
 * Unlike the superadmin CompanyRequest this never touches the owner login
 * account — an owner may only maintain their company's public details. The
 * company is resolved from the authenticated user (user_id) rather than a
 * route id, so email uniqueness ignores the owner's own company.
 */
class OwnerCompanyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = optional(
            Company::where('user_id', $this->user()?->id)->first()
        )->id;

        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'commercial_register' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('companies', 'email')
                    ->whereNull('deleted_at')
                    ->ignore($companyId),
            ],
            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => __('messages.email_already_exists'),
            'logo.image' => __('messages.logo_must_be_image'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ResponseHelper::error(
                __('messages.validation_failed'),
                $validator->errors(),
                422
            )
        );
    }
}
