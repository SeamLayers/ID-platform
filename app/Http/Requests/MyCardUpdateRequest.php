<?php

namespace App\Http\Requests;

use App\Contracts\ValidationTranslatorInterface;
use App\Http\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * What an employee may change on their own card from the mobile app.
 *
 * Deliberately narrow: the employee personalises presentation only. Anything
 * that identifies them or governs the card's lifecycle — name, job title,
 * company, template, status, expiry, the public URL — stays owner-authored on
 * the dashboard.
 */
class MyCardUpdateRequest extends FormRequest
{
    public function __construct(
        protected ValidationTranslatorInterface $translator
    ) {
        parent::__construct();
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bio'             => ['sometimes', 'nullable', 'string', 'max:500'],
            // Second contact number. Saudi/E.164-ish; kept permissive because
            // the mobile client already normalises before sending.
            'secondary_phone' => ['sometimes', 'nullable', 'string', 'max:30'],

            // Colour overrides layered on the template's theme. Each key is
            // optional; anything absent falls back to the template.
            'theme'            => ['sometimes', 'nullable', 'array'],
            'theme.background' => ['sometimes', 'nullable', 'string', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'theme.text'       => ['sometimes', 'nullable', 'string', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'theme.primary'    => ['sometimes', 'nullable', 'string', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'theme.accent'     => ['sometimes', 'nullable', 'string', 'regex:/^#([0-9a-fA-F]{6})$/'],

            'photo'  => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            // Explicit opt-out so the employee can clear a photo they uploaded.
            'remove_photo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'theme.*.regex' => __('messages.color_must_be_hex'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::success(
                null,
                $this->translator->transform($validator),
                422
            )
        );
    }
}
