<?php

namespace App\Services;

use App\Contracts\ValidationTranslatorInterface;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Str;

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
        foreach ($this->failedRules($field) as $rule) {
            $message = trans("validation.$rule", [
                'attribute' => trans("validation.attributes.$field", [], $locale),
            ], $locale);

            // Grouped rules (max/min/size/between) resolve to an array of
            // type-specific messages — pick a safe string variant.
            if (is_array($message)) {
                $message = $message['string'] ?? reset($message);
            }

            if (is_string($message) && $message !== "validation.$rule") {
                return $message;
            }
        }

        // Fallback to Laravel's own resolved message so we never return empty.
        return $this->validator->errors()->first($field);
    }

    /**
     * The rule(s) that ACTUALLY failed for this field (snake_case), in order.
     *
     * Previously this returned every rule defined on the field and the caller
     * took the first, so any failure was mislabeled with the first rule — e.g.
     * a duplicate email (`unique`) was reported as "The Email field is
     * required." Using the validator's failed() map fixes the reported reason.
     */
    private function failedRules(string $field): array
    {
        $failed = $this->validator->failed()[$field] ?? [];

        if (! empty($failed)) {
            return array_map(fn ($rule) => Str::snake($rule), array_keys($failed));
        }

        // Fallback: the field's defined rules, parameters stripped.
        return array_values(array_filter(array_map(
            fn ($rule) => is_string($rule) ? Str::snake(explode(':', $rule)[0]) : null,
            $this->validator->getRules()[$field] ?? []
        )));
    }
}
