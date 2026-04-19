<?php

return [
    // Status labels
    'status' => [
        'draft' => 'مسودة',
        'planning' => 'تخطيط',
        'in_progress' => 'قيد التنفيذ',
        'on_hold' => 'متوقف',
        'completed' => 'مكتمل',
        'closed' => 'مغلق',
    ],

    // Type labels
    'type' => [
        'residential' => 'سكني',
        'commercial' => 'تجاري',
        'infrastructure' => 'بنية تحتية',
    ],

    // Phase status labels
    'phase_status' => [
        'pending' => 'قيد الانتظار',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
    ],

    // Validation messages
    'validation' => [
        'owner_required' => 'معرف المالك مطلوب',
        'owner_not_found' => 'المالك المحدد غير موجود',
        'owner_must_be_customer' => 'المالك يجب أن يكون عميلاً',
        'name_ar_required' => 'اسم المشروع بالعربية مطلوب',
        'name_ar_min' => 'اسم المشروع بالعربية يجب أن يكون حرفين على الأقل',
        'name_en_required' => 'اسم المشروع بالإنجليزية مطلوب',
        'name_en_min' => 'اسم المشروع بالإنجليزية يجب أن يكون حرفين على الأقل',
        'city_required' => 'المدينة مطلوبة',
        'type_required' => 'نوع المشروع مطلوب',
        'budget_min' => 'الميزانية المقدرة يجب أن تكون أكبر من صفر',
        'end_date_after_start' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء أو مساوياً له',
        'lat_range' => 'خط العرض يجب أن يكون بين -90 و 90',
        'lng_range' => 'خط الطول يجب أن يكون بين -180 و 180',
        'status_required' => 'الحالة الجديدة مطلوبة',
        'expected_updated_at_required' => 'تاريخ التحديث المتوقع مطلوب',
        'expected_updated_at_date' => 'تاريخ التحديث المتوقع يجب أن يكون تاريخاً صحيحاً',
        'closed_immutable' => 'لا يمكن تعديل مشروع مغلق',
        'phase_name_ar_required' => 'اسم المرحلة بالعربية مطلوب',
        'phase_name_en_required' => 'اسم المرحلة بالإنجليزية مطلوب',
        'sort_order_required' => 'ترتيب المرحلة مطلوب',
        'sort_order_min' => 'ترتيب المرحلة يجب أن يكون صفراً أو أكبر',
        'completion_range' => 'نسبة الإكمال يجب أن تكون بين 0 و 100',
        'phase_start_before_project' => 'تاريخ بدء المرحلة يجب أن يكون ضمن نطاق تاريخ المشروع',
        'phase_end_after_project' => 'تاريخ انتهاء المرحلة يجب أن يكون ضمن نطاق تاريخ المشروع',
    ],

    // Error messages
    'errors' => [
        'not_found' => 'المشروع غير موجود',
        'closed_immutable' => 'لا يمكن تعديل مشروع مغلق',
        'invalid_transition' => 'لا يمكن الانتقال إلى هذه الحالة',
        'conflict' => 'تعارض في البيانات - قد يكون تم تحديث المشروع بواسطة مستخدم آخر',
    ],
];
