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
    ],

    'card_descriptions' => [
        'packages' => 'تعديل أسماء الخدمات والرموز والأسعار.',
        'doctors_directory' => 'إدارة التخصصات والأقسام والأطباء.',
    ],

];
