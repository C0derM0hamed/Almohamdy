<?php

return [
    'title' => 'تقارير الأقسام', 'filters' => 'تصفية التقرير', 'from' => 'من', 'to' => 'إلى', 'employee' => 'الموظف', 'period' => 'الفترة', 'all' => 'الكل', 'show' => 'عرض التقرير', 'pdf' => 'PDF',
    'date' => 'التاريخ', 'period_label' => 'الفترة', 'creator' => 'المدخل', 'attachment' => 'مرفق', 'report_count' => 'عدد التقارير', 'detail_count' => 'عدد التفاصيل',
    'no_records' => 'لا توجد سجلات ضمن الفترة أو المرشحات المحددة.', 'period_summary' => 'الفترة من :from إلى :to',
    'departments' => [
        'collection' => ['title' => 'تقرير قسم التحصيل', 'subtitle' => 'مؤشرات التحصيل والمطالبات المالية والتفاصيل المسجلة خلال الفترة المحددة.'],
        'legal' => ['title' => 'تقرير قسم الشؤون القانونية', 'subtitle' => 'مؤشرات الدعاوى والتفاصيل القانونية المسجلة خلال الفترة المحددة.'],
    ],
    'summary' => [
        'patients_remaining_count' => 'مرضى عليهم مبالغ في التنويم', 'outpatient_staff_deficit_count' => 'موظفو العيادات لديهم عجز', 'emergency_staff_deficit_count' => 'موظفو الطوارئ لديهم عجز',
        'inpatient_office_staff_deficit_count' => 'موظفو مكتب التنويم لديهم عجز', 'directors_deficit_count' => 'المدراء المناوبون لديهم عجز', 'inpatient_remaining_total' => 'إجمالي مبالغ مرضى التنويم',
        'outpatient_revenue_deficit_total' => 'إجمالي عجز إيراد العيادات', 'emergency_revenue_deficit_total' => 'إجمالي عجز إيراد الطوارئ', 'inpatient_office_revenue_deficit_total' => 'إجمالي عجز مكتب التنويم',
        'emergency_managers_deficit_total' => 'إجمالي عجز مدراء الطوارئ', 'cases_filed_in_court' => 'دعاوى مقدمة في محكمة التنفيذ', 'najiz_requests' => 'طلبات تنفيذ في ناجز', 'pending_cases' => 'دعاوى معلقة',
    ],
    'sections' => [
        'revenue_deficit' => 'عجز الإيرادات', 'owed_amount' => 'مبالغ مستحقة على مرضى التنويم', 'no_pledge' => 'مرضى لم يوقعوا تعهد السداد', 'bond_signed' => 'مرضى تم توقيع سند أمر لهم', 'previous_paid' => 'مطالبات مالية سابقة تم سدادها',
        'general_court' => 'الدعاوى المقدمة في المحكمة العامة', 'pending_claims' => 'الدعاوى المعلقة', 'government_cases' => 'دعاوى أو اعتراضات على قرارات حكومية أو أحكام',
    ],
    'columns' => [
        'employee' => 'الموظف', 'department' => 'القسم', 'financial_deficit' => 'العجز المالي', 'action' => 'الإجراء', 'notes' => 'ملاحظات', 'file_number' => 'رقم الملف', 'room' => 'رقم الغرفة', 'amount' => 'المبلغ', 'reason' => 'السبب', 'other_reason' => 'سبب آخر', 'action_notes' => 'تفاصيل الإجراء', 'admission_date' => 'تاريخ ووقت التنويم', 'hospitalization_location' => 'موقع التنويم', 'patient_name' => 'اسم المريض', 'patient_id' => 'هوية المريض', 'payer_name' => 'اسم المتعهد', 'payer_id' => 'هوية المتعهد', 'bond_amount' => 'مبلغ السند', 'invoice_total' => 'إجمالي الفاتورة', 'paid_amount' => 'المبلغ المدفوع', 'remaining_amount' => 'المبلغ المتبقي', 'invoice_number' => 'رقم الفاتورة', 'defendant_type' => 'نوع المدعى عليه', 'judicial_department' => 'الدائرة القضائية', 'request_type' => 'نوع الطلب', 'request_number' => 'رقم الطلب', 'case_number' => 'رقم القضية', 'session_date' => 'تاريخ الجلسة', 'respondent' => 'اسم المدعى عليه', 'respondent_id' => 'هوية المدعى عليه', 'orders' => 'الطلبات', 'objection_authority' => 'جهة الاعتراض',
    ],
];
