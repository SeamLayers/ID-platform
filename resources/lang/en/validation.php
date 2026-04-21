<?php

return [
    'required' => 'The :attribute field is required.',
    'numeric'  => 'The :attribute must be a number.',
    'exists'   => 'The selected :attribute is invalid.',
    'in'       => 'The selected :attribute is invalid.',
    'array'    => 'The :attribute must be an array.',
    'image'    => 'The :attribute must be an image.',
    'file'     => 'The :attribute must be a file.',
    'max' => [
        'file' => 'The :attribute may not be greater than :max kilobytes.',
    ],

    'attributes' => [
        'user_type' => 'user type',
        'category_id' => 'category',
        'price' => 'price',
        'images' => 'images',
        'days_availability' => 'days availability',
        'ar_title' => 'Arabic title',
        'en_title' => 'English title',
        'ar_description' => 'Arabic description',
        'en_description' => 'English description',
        'is_active' => 'Active status',
        'email' => 'email',
        'password' => 'password',
        'device_token' => 'device token',
    ],
    'string'   => 'The :attribute must be a string.',
    'boolean'  => 'The :attribute field must be true or false.',

];
