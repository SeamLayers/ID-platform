<?php

namespace App\Http\Requests;

use App\Contracts\ValidationTranslatorInterface;
use App\Http\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

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
        // The apiResource route parameter is {business_cards_template}, not
        // {id}, so the old $this->route('id') was always null and the unique
        // rule never excluded the row being edited (a no-op name change 422'd
        // "already taken"). Resolve the real id and ignore it on update.
        $templateId = $this->route('business_cards_template') ?? $this->route('id');

        return [

            'company_id' => [
                'required',
                'exists:companies,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('business_card_templates', 'name')->ignore($templateId),
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
