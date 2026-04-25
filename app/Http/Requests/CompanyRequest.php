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
        return [
            'user_id' => 'required|exists:users,id,user_type,owner',
            'name' => 'required|string|max:255',
            'commercial_register' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('companies', 'email')
                    ->whereNull('deleted_at'),
            ],
            'logo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => __('messages.user_required'),
            'user_id.exists' => __('messages.user_not_found'),

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
