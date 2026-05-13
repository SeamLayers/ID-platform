<?php

namespace App\Http\Requests;

use App\Contracts\ValidationTranslatorInterface;
use App\Http\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BusinessCardRequest extends FormRequest
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

            'employee_ids' => [
                'required',
                'array'
            ],

            'employee_ids.*' => [
                'required',
                'exists:employees,id',
                Rule::unique('business_cards', 'employee_id')
                    ->where(fn ($q) => $q->where('template_id', request('template_id'))),
            ],

            'template_id' => [
                'required',
                'exists:business_card_templates,id',
            ],

            'nfc_code' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_active' => [
                'nullable',
                'boolean',
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
