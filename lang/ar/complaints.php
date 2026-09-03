<?php

return [

    'title' => 'الشكاوى',
    'subtitle' => 'نظرة عامة على الشكاوى ومسارات المتابعة.',
    'dashboard' => 'لوحة الشكاوى',
    'dashboard_subtitle' => 'نظرة عامة على حالة الشكاوى والوصول السريع إلى القائمة.',
    'filters_title' => 'البحث والتصفية',
    'list' => 'قائمة الشكاوى',
    'list_subtitle' => 'البحث وتصفية الشكاوى برقم الملف أو الحالة.',
    'detail' => 'تفاصيل الشكوى',
    'detail_subtitle' => 'تفاصيل الشكوى والخط الزمني للتحقيق.',
    'timeline' => 'الخط الزمني للشكوى',
    'timeline_subtitle' => 'سجل الحالات من ردود الشكوى.',
    'timeline_modal_subtitle' => 'رقم الشكوى :number — :department',
    'timeline_last_update' => 'آخر تحديث',
    'close' => 'إغلاق',
    'view_list' => 'عرض الشكاوى',
    'back_to_list' => 'العودة إلى القائمة',
    'print' => 'طباعة',
    'view_detail' => 'عرض التفاصيل',
    'view_timeline' => 'عرض الخط الزمني',
    'search' => 'بحث',
    'reset' => 'إعادة تعيين',
    'sort' => 'ترتيب',
    'refresh' => 'تحديث',
    'export' => 'تصدير',
    'columns_settings' => 'إعدادات الأعمدة',
    'table_tools' => 'أدوات جدول الشكاوى',
    'complaints_count' => ':count شكوى',
    'results_summary' => 'عرض :shown من أصل :total شكوى',
    'no_complaints' => 'لا توجد شكاوى.',
    'no_results' => 'لا توجد شكاوى تطابق عوامل التصفية.',
    'no_timeline' => 'لا توجد أحداث في الخط الزمني لهذه الشكوى.',
    'create_title' => 'إضافة شكوى',
    'create_subtitle' => 'تسجيل شكوى جديدة بالبيانات المعتمدة.',
    'create_success' => 'تم إنشاء الشكوى بنجاح.',
    'reply_title' => 'إضافة رد وحالة',
    'reply_success' => 'تم حفظ الرد وتحديث حالة الشكوى.',
    'save' => 'حفظ',
    'cancel' => 'إلغاء',
    'workflow' => ['terminal' => 'لا يمكن تعديل شكوى مكتملة أو مغلقة.', 'sequential' => 'يجب تسجيل الحالات بالتسلسل.', 'repeated' => 'تم تسجيل هذه الحالة من قبل.'],

    'stats' => [
        'total' => 'إجمالي الشكاوى',
        'active' => 'الشكاوى النشطة',
        'processed' => 'تمت المعالجة',
        'closed' => 'مغلقة',
    ],

    'summary' => [
        'aria_label' => 'ملخص الشكاوى',
    ],

    'insights' => [
        'aria_label' => 'مؤشرات الشكاوى',
        'processing_rate' => 'معدل المعالجة',
        'most_active_department' => 'القسم الأكثر نشاطاً',
        'latest_update' => 'آخر تحديث',
        'today_at' => 'اليوم :time',
    ],

    'filters' => [
        'search' => 'رقم الملف أو الشكوى',
        'search_placeholder' => 'أدخل رقم الملف أو الشكوى',
        'status' => 'الحالة',
        'all_statuses' => 'كل الحالات',
    ],

    'columns' => [
        'complaint_no' => 'رقم الشكوى',
        'file_no' => 'رقم الملف',
        'complainant' => 'مقدم الشكوى',
        'department' => 'القسم',
        'date' => 'التاريخ',
        'status' => 'الحالة',
        'priority' => 'الأولوية',
        'actions' => 'إجراءات',
    ],

    'sections' => [
        'complaint_info' => 'معلومات الشكوى',
        'complainant_info' => 'معلومات مقدم الشكوى',
        'investigation' => 'التحقيق',
    ],

    'fields' => [
        'complaint_no' => 'رقم الشكوى',
        'file_no' => 'رقم الملف',
        'complainant' => 'مقدم الشكوى',
        'patient' => 'المريض',
        'department' => 'القسم',
        'mobile' => 'الجوال',
        'type' => 'النوع',
        'date' => 'تاريخ الشكوى',
        'created_at' => 'تاريخ الإضافة',
        'defendant' => 'المدعى عليه',
        'details' => 'التفاصيل',
        'result' => 'النتيجة',
        'employee_investigation' => 'تحقيق الموظف',
        'status' => 'الحالة الحالية',
        'id_no' => 'رقم الهوية',
        'event_date' => 'تاريخ الواقعة',
        'attachment' => 'مرفق',
        'reply' => 'الرد',
    ],

    'status' => [
        'new' => 'جديد',
    ],

];
