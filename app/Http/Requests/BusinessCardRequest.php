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

            'employee_ids.*' => array_merge(
                [
                    'required',
                    'exists:employees,id',
                ],
                // The DB has a GLOBAL unique index on employee_id
                // (business_cards_employee_id_unique) — one card per employee.
                // The old template-scoped rule let duplicates through validation
                // and the insert blew up with a raw SQL 500. Enforce the real
                // constraint on create only (updates don't insert rows, and the
                // old rule wrongly matched the card's own row on update).
                $this->isMethod('POST')
                    ? [Rule::unique('business_cards', 'employee_id')]
                    : []
            ),

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

            // Was previously unvalidated, so validated() silently dropped it and
            // every card fell back to the 2-day default regardless of input.
            'expiry_days' => [
                'nullable',
                'integer',
                'min:1',
                'max:3650',
            ],

            // Presentation fields shared with the employee's mobile editor, so
            // an owner can author the card up front and the employee sees it.
            'bio'             => ['sometimes', 'nullable', 'string', 'max:500'],
            'secondary_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'theme'            => ['sometimes', 'nullable', 'array'],
            'theme.background' => ['sometimes', 'nullable', 'string', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'theme.text'       => ['sometimes', 'nullable', 'string', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'theme.primary'    => ['sometimes', 'nullable', 'string', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'theme.accent'     => ['sometimes', 'nullable', 'string', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'photo'            => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'remove_photo'     => ['sometimes', 'boolean'],
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
