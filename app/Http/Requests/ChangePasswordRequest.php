<?php

namespace App\Http\Requests;

use App\Http\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Authenticated password change (used by the forced first-login reset when the
 * temporary password must be replaced, and by the normal "change password"
 * action). Unlike /auth/reset-password this requires a valid session, so it
 * doesn't depend on the OTP flow.
 */
class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
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
