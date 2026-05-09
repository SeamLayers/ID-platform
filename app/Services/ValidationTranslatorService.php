<?php

namespace App\Services;

use App\Contracts\ValidationTranslatorInterface;
use Illuminate\Contracts\Validation\Validator;

class ValidationTranslatorService implements ValidationTranslatorInterface
{
    protected Validator $validator;

    public function transform(Validator $validator): array
    {
        $this->validator = $validator;

        return collect($validator->errors()->toArray())
            ->mapWithKeys(function ($messages, $field) {

                return [
                    $field => [
                        'en' => $this->translate($field, 'en'),
                        'ar' => $this->translate($field, 'ar'),
                    ],
                ];
            })
            ->toArray();
    }

    private function translate(string $field, string $locale): string
    {
        $rules = $this->validatorRules($field);

        return collect($rules)
            ->map(function ($rule) use ($field, $locale) {

                return trans("validation.$rule", [
                    'attribute' => trans("validation.attributes.$field", [], $locale),
                ], $locale);

            })->first();
    }

    private function validatorRules(string $field): array
    {
        return $this->validator->getRules()[$field] ?? [];
    }
}
