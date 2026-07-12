<?php

namespace App\Http\Requests;

use App\Http\Helpers\ResponseHelper;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        // On update the apiResource binds the company id as the {company} route
        // param; on store there is none. We use it to (a) let the record keep
        // its own email, (b) keep the existing logo when none is re-uploaded,
        // and (c) not force the owner (user_id) to be re-sent on every edit.
        $companyId = $this->route('company');
        $isUpdate  = ! is_null($companyId);

        return [
            // On create the owner LOGIN account is provisioned here (name/email/
            // phone → a new `owner` user + temp password), so the superadmin no
            // longer types an existing user id. On update the owner is already
            // linked and left untouched.
            'owner_name'  => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'owner_email' => [
                $isUpdate ? 'sometimes' : 'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'owner_phone' => ['nullable', 'string', 'max:20'],
            'name' => 'required|string|max:255',
            'commercial_register' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('companies', 'email')
                    ->whereNull('deleted_at')
                    ->ignore($companyId),
            ],
            // Required when creating; on edit the existing logo is preserved
            // unless a replacement file is uploaded.
            'logo' => [
                $isUpdate ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'owner_name.required'  => __('messages.owner_name_required'),
            'owner_email.required' => __('messages.owner_email_required'),
            'owner_email.unique'   => __('messages.owner_email_taken'),

            'email.unique' => __('messages.email_already_exists'),

            'logo.required' => __('messages.logo_required'),
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
