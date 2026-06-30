<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Validation Messages
    |--------------------------------------------------------------------------
    */

    'numeric'  => 'The :attribute must be a number.',
    'exists'   => 'The selected :attribute is invalid.',
    'in'       => 'The selected :attribute is invalid.',
    'array'    => 'The :attribute must be an array.',
    'image'    => 'The :attribute must be an image.',
    'file'     => 'The :attribute must be a file.',
    'string'   => 'The :attribute must be a valid string.',
    'boolean'  => 'The :attribute field must be true or false.',
    'required' => 'The :attribute field is required.',

    'unique' => 'The :attribute has already been taken.',

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

        'address'           => 'Address',
        'user_type'         => 'User Type',
        'category_id'       => 'Category',
        'price'             => 'Price',
        'password'          => 'Password',
        'device_token'      => 'Device Token',

        'company_id'        => 'Company',
        'branch_id'         => 'Branch',
        'role_id'           => 'Role',
        'department_id'     => 'Department',
        'user_id'           => 'User',

        'employee_number'   => 'Employee Number',
        'employee_id'       => 'Employee',
        'employee_ids'      => 'Employees',

        'iqama_number'      => 'Iqama Number',
        'name'              => 'Name',
        'email'             => 'Email',
        'phone'             => 'Phone',
        'status'            => 'Status',

        'logo'              => 'Logo',
        'code'              => 'Code',

        'template_id'       => 'Template',
        'nfc_code'          => 'NFC Code',
        'public_url'        => 'Public URL',
        'expiry_public_url' => 'Public URL Expiry Date',
        'card_data_json'    => 'Business Card Data',

        'permissions'       => 'Permissions',
        'position'            => 'position',

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
