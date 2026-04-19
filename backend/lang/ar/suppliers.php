<?php

return [
    'not_found' => 'المورد غير موجود.',
    'already_exists' => 'يوجد ملف تعريف مورد لهذا المستخدم مسبقاً.',
    'commercial_reg_taken' => 'يوجد مورد بهذا الرقم التجاري مسبقاً.',
    'role_required' => 'يجب تحديد مقاول صالح لإنشاء الملف.',
    'unauthorized_update' => 'غير مصرح لك بتعديل هذا الملف.',
    'created_successfully' => 'تم إنشاء ملف المورد بنجاح.',
    'updated_successfully' => 'تم تحديث ملف المورد بنجاح.',
    'verified_successfully' => 'تم التحقق من المورد بنجاح.',
    'suspended_successfully' => 'تم تعليق المورد بنجاح.',
    'list_title' => 'الموردون',
    'list_subtitle' => 'استعرض الموردين والمواد المتاحة.',
    'admin_list_title' => 'إدارة الموردين',
    'view_profile' => 'عرض الملف',
    'my_profile' => 'ملف المورد',
    'profile_title' => 'ملف المورد',
    'create' => 'إنشاء ملف المورد',
    'verify' => 'توثيق',
    'suspend' => 'تعليق',
    'no_results' => 'لا يوجد موردون مطابقون.',

    'status' => [
        'pending' => 'قيد المراجعة',
        'verified' => 'موثق',
        'suspended' => 'معلق',
    ],

    'fields' => [
        'company_name_ar' => 'اسم الشركة (عربي)',
        'company_name_en' => 'اسم الشركة (إنجليزي)',
        'commercial_reg' => 'السجل التجاري',
        'tax_number' => 'الرقم الضريبي',
        'phone' => 'رقم الهاتف',
        'city' => 'المدينة',
        'district' => 'الحي',
        'address' => 'العنوان',
        'description_ar' => 'وصف (عربي)',
        'description_en' => 'وصف (إنجليزي)',
        'logo' => 'شعار (رابط)',
        'website' => 'الموقع الإلكتروني',
        'verification_status' => 'حالة التوثيق',
    ],

    'placeholders' => [
        'company_name_ar' => 'الاسم التجاري بالعربية',
        'company_name_en' => 'Company name in English',
    ],

    'validation' => [
        'phone_format' => 'يجب أن يبدأ رقم الهاتف بـ 05 ويتكون من 10 أرقام.',
        'user_id_required_for_admin' => 'يجب تحديد معرف المستخدم (مقاول) عند الإنشاء من قِبَل الإدارة.',
    ],
];
