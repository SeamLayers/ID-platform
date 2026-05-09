<?php

return [

    'required' => 'حقل :attribute مطلوب.',
    'numeric'  => 'حقل :attribute يجب أن يكون رقمًا.',
    'exists'   => 'القيمة المحددة في :attribute غير موجودة.',
    'in'       => 'القيمة المختارة في :attribute غير صحيحة.',
    'array'    => 'حقل :attribute يجب أن يكون مصفوفة.',
    'image'    => 'الملف في :attribute يجب أن يكون صورة.',
    'file'     => 'حقل :attribute يجب أن يكون ملفًا.',
    'string'   => 'حقل :attribute يجب أن يكون نصًا صحيحًا.',
    'boolean'  => 'حقل :attribute يجب أن يكون صحيح أو خطأ.',
    'unique'  => 'حقل :attribute يجب أن يكون فريد.',

    'max' => [
        'string' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
        'file'   => 'حجم الملف في :attribute يجب ألا يتجاوز :max كيلوبايت.',
    ],

    'attributes' => [

        'address'           => 'العنوان',
        'user_type'         => 'نوع المستخدم',
        'category_id'       => 'التصنيف',
        'price'             => 'السعر',
        'password'          => 'كلمة المرور',
        'device_token'      => 'رمز الجهاز',
        'company_id'       => 'الشركة',
        'branch_id'        => 'الفرع',
        'role_id'          => 'الدور',
        'department_id'    => 'القسم',
        'user_id'          => 'المستخدم',
        'employee_number'  => 'الرقم الوظيفي',
        'iqama_number'     => 'رقم الإقامة',
        'name'             => 'الاسم',
        'email'            => 'البريد الإلكتروني',
        'phone'            => 'رقم الهاتف',
        'status'           => 'الحالة',
        'logo'             => 'الشعار',
        'code'             => 'الرقم التعريفى',
        'employee_ids'     => 'الموظفين',
        'permissions'      => 'الصلاحيات'
    ],

    'custom' => [
        'company_id' => [
            'exists' => 'الشركة المحددة غير موجودة.',
        ],
    ],
];
