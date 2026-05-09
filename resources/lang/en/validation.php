<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Validation Messages
    |--------------------------------------------------------------------------
    */

    'required' => 'The :attribute field is required.',
    'numeric'  => 'The :attribute must be a number.',
    'exists'   => 'The selected :attribute is invalid.',
    'in'       => 'The selected :attribute is invalid.',
    'array'    => 'The :attribute must be an array.',
    'image'    => 'The :attribute must be an image.',
    'file'     => 'The :attribute must be a file.',
    'string'   => 'The :attribute must be a valid string.',
    'boolean'  => 'The :attribute field must be true or false.',
    'unique'  => 'The :attribute field must be unique.',

    'max' => [
        'string' => 'The :attribute may not be greater than :max characters.',
        'file'   => 'The :attribute may not be greater than :max kilobytes.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribute Names
    |--------------------------------------------------------------------------
    */

    'attributes' => [

        'address'           => 'address',
        'user_type'         => 'user type',
        'category_id'       => 'category',
        'price'             => 'price',
        'password'          => 'password',
        'device_token'      => 'device token',

        'company_id'       => 'company',
        'branch_id'        => 'branch',
        'role_id'          => 'role',
        'department_id'    => 'department',
        'user_id'          => 'user',
        'employee_number'  => 'employee number',
        'iqama_number'     => 'iqama number',
        'name'             => 'name',
        'email'            => 'email',
        'phone'            => 'phone',
        'status'           => 'status',
        'logo'             => 'logo',
        'code'             => 'code',
        'employee_ids'             => 'employee_ids',
        'permissions'             => 'permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Rules (ONLY when needed)
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'company_id' => [
            'exists' => 'Selected company not found.',
        ],
    ],
];
