<?php

namespace App\Contracts;

use Illuminate\Contracts\Validation\Validator;

interface ValidationTranslatorInterface
{
    public function transform(Validator $validator): array;
}
