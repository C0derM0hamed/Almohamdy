<?php

return [

    'title' => 'المراسلات الصادرة',
    'subtitle' => 'إنشاء ومتابعة خطابات التواصل المؤسسي الصادرة.',
    'list' => 'المراسلات الصادرة',
    'list_subtitle' => 'تصفية حسب التاريخ أو القطاع أو الجهة أو المستشفى أو الموضوع أو الحالة.',
    'create' => 'إضافة خطاب صادر',
    'create_subtitle' => 'إعداد خطاب صادر إلى جهة خارجية.',
    'detail' => 'تفاصيل الخطاب',
    'detail_subtitle' => 'محتوى الخطاب والمرفقات وسجل الإجراءات.',
    'back_to_list' => 'العودة إلى الخطابات الصادرة',
    'back_to_detail' => 'العودة إلى الخطاب',

    'status_form' => [
        'title' => 'تحديث حالة الخطاب',
        'subtitle' => 'نقل الخطاب الصادر إلى الحالة التالية في سير العمل.',
        'status' => 'الحالة الجديدة',
        'reason' => 'السبب / الملاحظات',
        'confirm_approval' => 'أقر بأني قمت بمراجعة الخطاب المعد وهو صحيح ومتوافق مع الضوابط والمعايير المتبعة.',
        'shipment_number' => 'رقم الشحنة',
        'date_time_receipt' => 'تاريخ ووقت الاستلام / التسليم',
        'postal_employee_name' => 'اسم موظف البريد',
        'return_date' => 'تاريخ الإعادة',
        'registration_number' => 'رقم القيد',
        'delivered_by' => 'تم التسليم بواسطة',
        'delivery_date' => 'تاريخ التسليم',
        'status_file' => 'الملف الداعم',
        'reply_file' => 'ملف رد الجهة',
        'supplementary_content' => 'محتوى الخطاب الإلحاقي',
        'submit' => 'تحديث الحالة',
        'empty_statuses' => 'لا تتوفر تغييرات حالة إضافية لهذا الخطاب.',
    ],

    'supplementary' => [
        'title' => 'الخطابات الإلحاقية',
        'empty' => 'لا توجد خطابات إلحاقية مسجلة بعد.',
        'serial' => 'التسلسل',
        'created_at' => 'تاريخ الإنشاء',
        'content' => 'المحتوى',
    ],

    'counters' => [
        'title' => 'نظرة عامة على الحالات',
    ],

    'filters' => [
        'from_date' => 'من تاريخ',
        'to_date' => 'إلى تاريخ',
        'sector' => 'القطاع',
        'sector_all' => 'كل القطاعات',
        'authority' => 'الجهة المستلمة',
        'authority_all' => 'كل الجهات',
        'branch' => 'المستشفى',
        'branch_all' => 'كل المستشفيات',
        'status' => 'الحالة',
        'status_all' => 'كل الحالات',
        'subject' => 'الموضوع',
        'subject_placeholder' => 'بحث في الموضوع…',
        'search' => 'بحث',
        'reset' => 'إعادة تعيين',
    ],

    'table' => [
        'number' => 'رقم الخطاب',
        'date' => 'تاريخ الإصدار',
        'authority' => 'الجهة',
        'branch' => 'المستشفى',
        'subject' => 'الموضوع',
        'status' => 'الحالة',
        'actions' => 'إجراءات',
        'empty' => 'لا توجد مراسلات صادرة.',
        'empty_filtered' => 'لا توجد نتائج مطابقة لفلاتر البحث.',
    ],

    'fields' => [
        'branch' => 'المستشفى',
        'sector' => 'القطاع',
        'authority' => 'الجهة المستلمة',
        'sender_title' => 'لقب المستلم',
        'issue_date' => 'تاريخ الإصدار',
        'recipient_name' => 'اسم المستلم',
        'sender_gender' => 'جنس المستلم',
        'male' => 'ذكر',
        'female' => 'أنثى',
        'job_title' => 'المسمى الوظيفي للمستلم',
        'receiving_mechanism' => 'آلية الإرسال',
        'subject' => 'الموضوع',
        'letter_content' => 'محتوى الخطاب',
        'response_deadline' => 'تاريخ الرد المتوقع',
        'template' => 'نموذج جاهز',
        'template_none' => 'بدون نموذج',
        'attachments' => 'المرفقات',
        'attachment_name' => 'اسم المرفق',
        'registration_number' => 'رقم التسجيل',
        'year' => 'السنة',
        'status' => 'الحالة',
    ],

    'declarations' => [
        'confirm_recipient' => 'أؤكد صحة بيانات المستلم والجهة والقطاع.',
        'confirm_content' => 'أؤكد صحة الموضوع ومحتوى الخطاب.',
    ],

    'timeline' => [
        'title' => 'سجل الإجراءات',
        'empty' => 'لا توجد إجراءات مسجلة بعد.',
        'notice' => 'ملاحظات',
    ],

    'actions' => [
        'menu' => 'إجراءات',
        'view' => 'عرض',
        'update_status' => 'تحديث الحالة',
        'save' => 'حفظ الخطاب',
        'cancel' => 'إلغاء',
    ],

    'flash' => [
        'created' => 'تم تسجيل الخطاب الصادر بنجاح.',
        'status_updated' => 'تم تحديث حالة الخطاب بنجاح.',
    ],

    'print' => [
        'title' => 'خطاب صادر',
        'button' => 'طباعة / PDF',
        'print' => 'طباعة',
    ],

    'department_revise' => [
        'title' => 'تعديل الخطاب المُرجع',
        'subtitle' => 'حدّث موضوع ومحتوى الخطاب بعد إرجاعه من التواصل المؤسسي.',
        'confirm' => 'أقر بأن محتوى الخطاب المعدّل دقيق.',
        'submit' => 'إرسال التعديل',
        'success' => 'شكراً لك. تم تعديل الخطاب بنجاح.',
        'not_returned' => 'هذا الخطاب غير مُرجع للقسم حالياً.',
        'invalid_link' => 'رابط التعديل غير صالح أو منتهي.',
        'result_title' => 'نتيجة التعديل',
        'result_success' => 'تم الإرسال',
        'result_error' => 'تعذر المتابعة',
        'open_link' => 'فتح رابط التعديل',
    ],

    'status_unknown' => 'غير معروف',
    'open_attachment' => 'فتح',
    'no_attachment' => 'لا توجد مرفقات.',

    'validation' => [
        'confirm_recipient' => 'يرجى تأكيد إقرار بيانات المستلم والجهة.',
        'confirm_content' => 'يرجى تأكيد إقرار الموضوع والمحتوى.',
        'confirm_approval' => 'يرجى تأكيد الاعتماد قبل المتابعة.',
        'reason_required' => 'السبب مطلوب لهذه الحالة.',
        'shipment_number_required' => 'رقم الشحنة مطلوب.',
        'receipt_datetime_required' => 'تاريخ ووقت الاستلام مطلوبان.',
        'postal_employee_required' => 'اسم موظف البريد مطلوب.',
        'return_date_required' => 'تاريخ الإعادة مطلوب.',
        'registration_number_required' => 'رقم القيد مطلوب.',
        'delivered_by_required' => 'جهة التسليم مطلوبة.',
        'delivery_date_required' => 'تاريخ التسليم مطلوب.',
        'status_file_required' => 'الملف الداعم مطلوب لهذه الحالة.',
        'reply_file_required' => 'يرجى إرفاق ملف رد الجهة.',
        'supplementary_content_required' => 'محتوى الخطاب الإلحاقي مطلوب.',
        'status_unchanged' => 'يرجى اختيار حالة مختلفة.',
    ],

];
