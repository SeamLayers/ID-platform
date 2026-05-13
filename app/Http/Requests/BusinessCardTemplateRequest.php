<?php

namespace App\Http\Requests;

use App\Contracts\ValidationTranslatorInterface;
use App\Http\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BusinessCardTemplateRequest extends FormRequest
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
        $templateId = $this->route('id');

        return [

            'company_id' => [
                'required',
                'exists:companies,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                'unique:business_card_templates,name,' . $templateId,
            ],

            'design_json' => [
                'nullable',
                'array',
            ],

            'is_default' => [
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
