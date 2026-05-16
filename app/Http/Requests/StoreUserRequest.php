<?php

namespace App\Http\Requests;

use App\Contracts\ValidationTranslatorInterface;
use App\Http\Helpers\ResponseHelper;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'email' => 'required|email|unique:users,email',

            'user_type' => [
                'required',
                'in:owner,employee,superadmin'
            ],

            'phone' => [
                'required',
                'string',
                'max:20'
            ],
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
