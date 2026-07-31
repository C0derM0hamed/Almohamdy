<?php

return [

    'title' => 'إجازات الموظفين',
    'dashboard' => 'لوحة الإجازات',
    'dashboard_subtitle' => 'نظرة عامة على طلبات إجازات الموظفين',
    'requests' => 'طلبات الإجازة',
    'requests_subtitle' => 'استعراض وإدارة طلبات الإجازة',
    'total_records' => ':count سجل',
    'new_request' => 'طلب إجازة جديد',
    'new_request_subtitle' => 'تقديم طلب إجازة جديد',
    'request_detail' => 'تفاصيل طلب الإجازة',
    'view_requests' => 'عرض جميع الطلبات',
    'apply_leave' => 'تقديم طلب إجازة',
    'application_submitted' => 'تم تقديم طلب الإجازة بنجاح.',
    'branch_processed' => 'تم تسجيل قرار الفرع بنجاح.',
    'hr_processed' => 'تم تسجيل قرار الموارد البشرية بنجاح.',

    'stats' => [
        'total' => 'إجمالي طلبات الإجازة',
        'pending' => 'قيد الانتظار',
        'approved' => 'موافق عليها',
        'rejected' => 'مرفوضة',
    ],

    'columns' => [
        'request_no' => 'رقم الطلب',
        'employee' => 'الموظف',
        'leave_type' => 'نوع الإجازة',
        'from_date' => 'من تاريخ',
        'to_date' => 'إلى تاريخ',
        'days' => 'الأيام',
        'status' => 'الحالة',
        'actions' => 'إجراءات',
    ],

    'status' => [
        'pending' => 'قيد الانتظار',
        'approved' => 'موافق عليها',
        'rejected' => 'مرفوضة',
    ],

    'fields' => [
        'leave_type' => 'نوع الإجازة',
        'leave_type_other' => 'حدد نوع الإجازة',
        'start_date' => 'تاريخ البداية',
        'end_date' => 'تاريخ النهاية',
        'reason' => 'السبب',
        'employee' => 'الموظف',
        'email' => 'البريد الإلكتروني',
        'mobile' => 'الجوال',
        'job_title' => 'المسمى الوظيفي',
        'submitted_at' => 'تاريخ التقديم',
        'request_no' => 'رقم الطلب',
        'comment' => 'ملاحظة',
    ],

    'sections' => [
        'employee_info' => 'بيانات الموظف',
        'leave_details' => 'تفاصيل الإجازة',
        'status_history' => 'سجل الحالة',
        'branch_processing' => 'موافقة الفرع',
        'hr_processing' => 'موافقة الموارد البشرية',
    ],

    'processing' => [
        'approve' => 'موافقة',
        'reject' => 'رفض',
        'branch_hint' => 'راجع الطلب ووافق أو ارفض على مستوى الفرع.',
        'hr_hint' => 'اكتملت موافقة الفرع. سجّل القرار النهائي للموارد البشرية.',
        'comment_placeholder' => 'ملاحظة اختيارية لهذا القرار…',
    ],

    'errors' => [
        'not_found' => 'طلب الإجازة غير موجود.',
        'branch_not_pending' => 'هذا الطلب لا ينتظر موافقة الفرع.',
        'hr_not_pending' => 'هذا الطلب لا ينتظر موافقة الموارد البشرية.',
        'unauthorized' => 'غير مصرح لك بتنفيذ هذا الإجراء.',
        'permission' => [
            'employee_leave.view' => 'ليس لديك صلاحية عرض طلبات الإجازة.',
            'employee_leave.apply' => 'ليس لديك صلاحية تقديم طلب إجازة.',
            'employee_leave.branch_process' => 'ليس لديك صلاحية معالجة موافقات الفرع.',
            'employee_leave.hr_process' => 'ليس لديك صلاحية معالجة موافقات الموارد البشرية.',
        ],
    ],

    'history' => [
        'branch' => 'موافقة الفرع',
        'hr' => 'موافقة الموارد البشرية',
        'no_comment' => 'بدون تعليق',
    ],

    'filters' => [
        'search' => 'بحث برقم الطلب أو اسم الموظف…',
        'status' => 'جميع الحالات',
        'leave_type' => 'جميع أنواع الإجازة',
    ],

    'search' => 'بحث',
    'reset' => 'إعادة تعيين',
    'submit' => 'تقديم الطلب',
    'cancel' => 'إلغاء',
    'view' => 'عرض',
    'no_results' => 'لا توجد طلبات مطابقة للبحث.',
    'no_requests' => 'لا توجد طلبات إجازة.',
    'no_history' => 'لا توجد تحديثات للحالة بعد.',

    'select_leave_type' => 'اختر نوع الإجازة',
    'reason_placeholder' => 'صف بإيجاز سبب طلب الإجازة…',
    'leave_type_other_placeholder' => 'أدخل نوع الإجازة (مثل إجازة دراسية، إجازة خاصة…)',

];
