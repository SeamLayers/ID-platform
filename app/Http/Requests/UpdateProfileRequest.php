<?php

namespace App\Http\Requests;

use App\Contracts\ValidationTranslatorInterface;
use App\Http\Helpers\ResponseHelper;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateProfileRequest   extends FormRequest
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

            'sex' => [
                'required',
                'in:male,female'
            ],

            'material_status' => [
                'required',
                'in:married,single'
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
