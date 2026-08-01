<?php

return [

    'title' => 'إدارة النظام',
    'dashboard' => 'إدارة النظام',
    'dashboard_subtitle' => 'إدارة إعدادات النظام القابلة للتعديل والمتاحة للمسؤولين فقط.',
    'dashboard_search_placeholder' => 'بحث في الوحدات…',
    'packages' => 'خدمات المستشفى',
    'packages_subtitle' => 'تعديل أسماء الخدمات والرموز والأسعار وحالة النشر.',
    'edit_package' => 'تعديل الخدمة',
    'filters_title' => 'عوامل التصفية',
    'search' => 'بحث',
    'reset' => 'إعادة تعيين',
    'edit' => 'تعديل',
    'delete' => 'حذف',
    'confirm_delete' => 'هل تريد حذف هذه الخدمة نهائياً؟ لا يمكن التراجع عن هذا الإجراء.',
    'save' => 'حفظ التغييرات',
    'cancel' => 'إلغاء',
    'publish' => 'نشر',
    'unpublish' => 'إلغاء النشر',
    'view_public_catalog' => 'عرض الكتالوج العام',
    'manage_packages' => 'إدارة خدمات المستشفى',
    'manage_doctors_directory' => 'إدارة دليل الأطباء',
    'empty_title' => 'لا توجد خدمات',
    'no_packages' => 'لم يتم إنشاء أي خدمات مستشفى بعد.',
    'no_results' => 'لا توجد خدمات مطابقة لعوامل التصفية.',
    'package_updated' => 'تم تحديث الخدمة بنجاح.',
    'package_deleted' => 'تم حذف الخدمة بنجاح.',
    'package_published' => 'تم نشر الخدمة بنجاح.',
    'package_unpublished' => 'تم إلغاء نشر الخدمة بنجاح.',
    'form_has_errors' => 'يرجى تصحيح الحقول المميزة ثم المحاولة مرة أخرى.',
    'form_basic_hint' => 'حدّث رمز الخدمة والأسماء والسعر المعروض في الكتالوج العام.',
    'form_publish_hint' => 'إظهار هذه الخدمة في كتالوج خدمات المستشفى العام عند التفعيل.',

    'errors' => [
        'unauthorized' => 'ليس لديك صلاحية للوصول إلى إدارة النظام.',
    ],

    'filters' => [
        'search' => 'البحث بالرمز أو الاسم…',
        'section' => 'قسم الخدمة',
        'publish' => 'الحالة',
        'all' => 'الكل',
        'all_sections' => 'كل الأقسام',
    ],

    'columns' => [
        'id' => 'المعرف',
        'code' => 'الرمز',
        'name_en' => 'الاسم (إنجليزي)',
        'name_ar' => 'الاسم (عربي)',
        'section' => 'القسم',
        'price' => 'السعر',
        'status' => 'الحالة',
        'actions' => 'الإجراءات',
    ],

    'fields' => [
        'id' => 'المعرف',
        'code' => 'رمز الخدمة',
        'name_en' => 'الاسم (إنجليزي)',
        'name_ar' => 'الاسم (عربي)',
        'price' => 'سعر الخدمة',
        'section' => 'القسم',
        'published' => 'منشور',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
    ],

    'sections' => [
        'basic_information' => 'تفاصيل الخدمة',
        'directory_settings' => 'إعدادات الكتالوج',
    ],

    'status' => [
        'published' => 'منشور',
        'unpublished' => 'غير منشور',
    ],

    'stats' => [
        'total' => 'إجمالي الخدمات',
        'published' => 'منشور',
        'unpublished' => 'غير منشور',
    ],

    'cards' => [
        'packages' => 'خدمات المستشفى',
        'doctors_directory' => 'دليل الأطباء',
        'groups' => 'مجموعات المستخدمين', 'job_titles' => 'المسميات الوظيفية', 'governmental_services' => 'الخدمات الحكومية', 'companies' => 'مجموعات الشركات', 'branches' => 'الفروع', 'departments' => 'أقسام الفروع', 'needs' => 'احتياجات الفروع',
    ],

    'card_descriptions' => [
        'packages' => 'تعديل أسماء الخدمات والرموز والأسعار.',
        'doctors_directory' => 'إدارة التخصصات والأقسام والأطباء.',
        'groups' => 'إدارة مجموعات المستخدمين المنشورة.', 'job_titles' => 'إدارة المسميات الوظيفية حسب الفرع.', 'governmental_services' => 'إدارة أنواع الخدمات الحكومية.', 'companies' => 'إدارة مجموعات الشركات.', 'branches' => 'إدارة فروع الشركات.', 'departments' => 'إدارة أقسام الفروع.', 'needs' => 'إدارة احتياجات الفروع.', 'service_types' => 'إدارة أنواع خدمات الفروع.', 'complaint_closing_reasons' => 'إدارة أسباب إغلاق الشكاوى.', 'complaint_letter_receivers' => 'إدارة جهات خطابات الشكاوى.', 'complaint_statuses' => 'إدارة حالات الشكاوى.', 'post_types' => 'إدارة أنواع المنشورات.',
    ],
    'reference' => ['scope' => 'بيانات مرجعية وإدارية ضمن نطاق الإدارة الحالي.', 'create' => 'إضافة', 'edit' => 'تعديل', 'back' => 'عودة', 'save' => 'حفظ', 'search' => 'بحث بالاسم أو التفاصيل', 'choose' => 'اختر', 'empty' => 'لا توجد بيانات.', 'name_ar' => 'الاسم بالعربية', 'name_en' => 'الاسم بالإنجليزية', 'status' => 'الحالة', 'published' => 'منشور', 'unpublished' => 'غير منشور', 'toggle' => 'تغيير النشر', 'delete' => 'حذف', 'saved' => 'تم الحفظ بنجاح.', 'status_changed' => 'تم تحديث حالة النشر.', 'deleted' => 'تم الحذف.', 'in_use' => 'لا يمكن حذف المجموعة لأنها مستخدمة.', 'groups' => 'مجموعات المستخدمين', 'job_titles' => 'المسميات الوظيفية', 'governmental_services' => 'أنواع الخدمات الحكومية', 'companies' => 'مجموعات الشركات', 'branches' => 'الفروع', 'departments' => 'أقسام الفروع', 'needs' => 'احتياجات الفروع', 'service_types' => 'أنواع خدمات الفروع', 'complaint_closing_reasons' => 'أسباب إغلاق الشكاوى', 'complaint_letter_receivers' => 'جهات خطابات الشكاوى', 'complaint_statuses' => 'حالات الشكاوى', 'post_types' => 'أنواع المنشورات', 'fields' => ['name_en' => 'الاسم بالإنجليزية', 'name_ar' => 'الاسم بالعربية', 'name_ch' => 'الاسم المختصر', 'info' => 'التفاصيل', 'training_declarations_id' => 'إقرار التدريب', 'platform_id' => 'منصة الخدمة', 'branch_id' => 'الفرع', 'email' => 'البريد الإلكتروني', 'mobile' => 'الجوال', 'address' => 'العنوان', 'logo' => 'الشعار']],

    'users' => [
        'title' => 'إدارة المستخدمين والصلاحيات', 'create' => 'إنشاء مستخدم', 'edit' => 'تعديل المستخدم',
        'details' => 'تفاصيل المستخدم', 'name' => 'الاسم', 'username' => 'اسم المستخدم', 'level' => 'نوع الحساب',
        'scope' => 'الشركة / الفرع', 'company' => 'الشركة', 'branch' => 'الفرع', 'group' => 'المجموعة',
        'status' => 'الحالة', 'active' => 'نشط', 'inactive' => 'معطل', 'empty' => 'لا يوجد مستخدمون ضمن نطاقك.',
        'scope_notice' => 'تظهر الحسابات الواقعة ضمن نطاق الإدارة المسموح فقط.', 'no_group' => 'بدون مجموعة',
        'direct_permissions' => 'الصلاحيات المباشرة', 'inherited_permissions' => 'الصلاحيات الموروثة',
        'effective_permissions' => 'معاينة الصلاحيات الفعلية', 'none' => 'لا توجد', 'save' => 'حفظ', 'saved' => 'تم حفظ المستخدم والصلاحيات.', 'cancel' => 'إلغاء',
        'levels' => ['0' => 'مستخدم قياسي', '1' => 'مستخدم فرع', '2' => 'مشرف', '3' => 'مدير أعلى', '4' => 'مستخدم نظام'],
        'fields' => ['hr_first_name' => 'الاسم الأول', 'hr_last_name' => 'اسم العائلة', 'hr_email_address' => 'البريد الإلكتروني', 'hr_username' => 'اسم المستخدم', 'mobile' => 'الجوال', 'password' => 'كلمة المرور', 'password_confirmation' => 'تأكيد كلمة المرور'],
        'errors' => ['unauthorized' => 'غير مصرح بإدارة المستخدمين.', 'out_of_scope' => 'المستخدم أو النطاق خارج صلاحيتك.', 'self_security_change' => 'لا يمكنك تغيير مستوى حسابك أو نطاقه أو مجموعته أو حالته.', 'final_super_admin' => 'لا يمكن تعطيل أو خفض صلاحية آخر مدير أعلى نشط.', 'invalid_permission' => 'تتضمن القائمة صلاحية غير معروفة.', 'privilege_escalation' => 'لا يمكنك منح صلاحية لا تملكها.'],
    ],

];
