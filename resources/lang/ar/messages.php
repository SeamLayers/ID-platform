<?php

return [
    'validation_failed' => 'فشل التحقق من البيانات',
    'unauthenticated' => 'المستخدم غير مصرح له',
    'current_password_incorrect' => 'كلمة المرور الحالية غير صحيحة',
    'password_changed' => 'تم تغيير كلمة المرور بنجاح',
    'owner_name_required' => 'اسم المالك مطلوب',
    'owner_email_required' => 'بريد المالك الإلكتروني مطلوب',
    'owner_email_taken' => 'بريد المالك الإلكتروني مستخدم بالفعل',





    'retrived_transaction_success' => 'تم استرجاع العمليات بنجاح',
    'update_transaction_success' => 'تم تحديث العملية بنجاح',
    'submit_transaction_success' => 'تم إرسال العملية بنجاح',



    'unauthorized_admin' => 'غير مصرح لك، صلاحيات مدير فقط',


    // Auth
    'login_success' => 'تم تسجيل الدخول بنجاح',
    'logout_success' => 'تم تسجيل الخروج بنجاح',
    'invalid_credentials' => 'بيانات تسجيل الدخول غير صحيحة',

    'email_already_verified' => 'تم التحقق من بريدك الإلكتروني مسبقًا.',
    'verification_link_sent' => 'تم إرسال رابط التحقق إلى بريدك الإلكتروني.',

    'expired_otp'           => 'انتهت صلاحية رمز التحقق.',
    'password_reset_success' => 'تم إعادة تعيين كلمة المرور بنجاح.',
    'register_success' => 'تم تسجيل المستخدم بنجاح.',
    'otp_sent' => 'تم إرسال رمز التحقق بنجاح.',
    'otp_wait' => 'تم إرسال رمز التحقق مسبقاً. يرجى الانتظار :seconds ثانية قبل طلب آخر.',
    'invalid_otp' => 'رمز التحقق غير صالح.',
    'otp_expired' => 'انتهت صلاحية رمز التحقق.',
    'otp_verified' => 'تم التحقق من رمز التحقق بنجاح.',
    'profile_retrieved' => 'تم استرجاع بيانات المستخدم بنجاح.',

    'profile_updated' => 'تم تحديث ملف المستخدم بنجاح.',
    'data_retrieved'=>'تم استرجاع بيانات  بنجاح',
    'data_saved' => 'تم حفظ البيانات بنجاح',
    'data_deleted' => 'تم حذف البيانات بنجاح',
    'data_updated' => 'تم تحديث البيانات بنجاح',

    'user_required' => 'المستخدم مطلوب',
    'user_not_found' => 'المستخدم المختار يجب أن يكون مالكًا',
    'company_not_found' => 'لا توجد شركة مرتبطة بحسابك بعد.',

    'email_already_exists' => 'البريد الإلكتروني مستخدم بالفعل',

    'logo_required' => 'الشعار مطلوب',
    'logo_must_be_image' => 'يجب أن يكون الشعار صورة',


    'employee_not_found' => 'الموظف غير موجود',
    'template_not_found' => 'القالب غير موجود',
    'department_not_found' => 'القسم غير موجود',
    'employee_company_forbidden' => 'يمكنك إدارة موظفي شركتك فقط.',
    'company_scope_forbidden' => 'يمكنك إدارة السجلات التابعة لشركتك فقط.',

    // إشعارات دورة حياة البطاقة
    'notif_card_submitted_title' => 'بطاقة بانتظار موافقتك',
    'notif_card_submitted_body'  => 'تم إنشاء بطاقة تعريف رقمية لك. افتح التطبيق لمراجعتها والموافقة عليها.',
    'notif_card_approved_title'  => 'تمت الموافقة على البطاقة',
    'notif_card_approved_body'   => 'وافق :name على بطاقته التعريفية.',
    'notif_card_rejected_title'  => 'تم رفض البطاقة',
    'notif_card_rejected_body'   => 'رفض :name بطاقته التعريفية: :reason',
    'notif_card_published_title' => 'بطاقتك أصبحت متاحة',
    'notif_card_published_body'  => 'تم نشر بطاقتك التعريفية الرقمية ويمكن الآن مشاركتها.',
    'employee_user_invalid' => 'لا يمكن ربط هذا المستخدم بموظف.',

    'employee_required' => 'الموظف مطلوب',
    'template_required' => 'القالب مطلوب',

    'business_card_submitted' => 'تم تقديم بطاقة العمل بنجاح',
    'business_card_approved'  => 'تمت الموافقة على بطاقة العمل',
    'business_card_rejected'  => 'تم رفض بطاقة العمل',
    'business_card_published' => 'تم نشر بطاقة العمل',
    'business_card_deactivated' => 'تم إيقاف بطاقة العمل',

    'business_card_must_be_approved' => 'يجب اعتماد البطاقة قبل النشر',

    'employee_business_card_exists' => 'الموظف لديه بطاقة عمل بالفعل',

    'business_card_unavailable' => 'هذه البطاقة لم تعد متاحة.',

    // --- تخصيص بطاقة الموظف ومراجعة المالك ---------------------------------
    'no_card_yet' => 'لا توجد لديك بطاقة تعريف بعد. ستقوم شركتك بإنشائها لك.',
    'card_locked_for_editing' => 'هذه البطاقة قيد المراجعة أو منشورة بالفعل، ولا يمكن تعديلها الآن.',
    'card_already_submitted' => 'تم إرسال هذه البطاقة للمراجعة بالفعل.',
    'card_not_awaiting_review' => 'هذه البطاقة ليست قيد انتظار المراجعة.',
    'color_must_be_hex' => 'يجب أن تكون الألوان بصيغة ست عشرية مثل ‎#22D3EE.',
    'business_card_changes_requested' => 'تم طلب التعديلات بنجاح',

    'notif_card_review_title' => 'بطاقة بانتظار مراجعتك',
    'notif_card_review_body'  => 'قام :name بتخصيص بطاقته التعريفية وأرسلها لمراجعتك.',
    'notif_card_changes_title' => 'طلب تعديل على بطاقتك',
    'notif_card_changes_body'  => 'طلبت شركتك إجراء تعديل: :comment',
    'notif_card_owner_approved_title' => 'تمت الموافقة على بطاقتك',
    'notif_card_owner_approved_body'  => 'وافقت شركتك على بطاقتك التعريفية، وسيتم نشرها قريباً.',

    'cannot_approve_own_card' => 'لا يمكنك الموافقة على بطاقتك بنفسك. تقوم شركتك بمراجعتها.',

    'password_expired' => 'انتهت صلاحية كلمة المرور الخاصة بك. يرجى إعادة تعيين كلمة المرور للمتابعة.',
];


