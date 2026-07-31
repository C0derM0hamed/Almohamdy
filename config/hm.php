<?php

return [

    // Application release version. Bump on each deployment so the login
    // screen shows the currently uploaded build (used to diff future upgrades).
    'version' => env('HM_APP_VERSION', '1.0.5'),

    'otp' => [
        'length' => max(4, min(8, (int) env('HM_OTP_LENGTH', 6))),
        'expiry_seconds' => (int) env('HM_OTP_EXPIRY_SECONDS', 120),
        'max_attempts' => (int) env('HM_OTP_MAX_ATTEMPTS', 5),
        'demo_code' => env('HM_OTP_DEMO_CODE', '111111'),
        'demo_mode' => (bool) env('HM_OTP_DEMO_MODE', true),
    ],

    'permissions' => [
        'bypass' => (bool) env('HM_PERMISSIONS_BYPASS', false),
        'admin_levels' => [3],
    ],

    'dashboard' => [
        'widgets' => [
            [
                'label_key' => 'modules',
                'source' => 'module_count',
                'icon' => 'bi-grid',
                'variant' => 'primary',
            ],
            [
                'label_key' => 'navigation',
                'source' => 'nav_count',
                'icon' => 'bi-list-ul',
                'variant' => 'success',
            ],
            [
                'label_key' => 'role',
                'source' => 'user_level',
                'icon' => 'bi-shield-check',
                'variant' => 'info',
            ],
        ],
        'cards' => [
            [
                'label_key' => 'doctors',
                'route' => 'modules.doctors',
                'icon' => 'bi-person-vcard',
            ],
            [
                'label_key' => 'system_administration',
                'route' => 'modules.system-admin.dashboard',
                'icon' => 'bi-shield-lock',
                'admin_only' => true,
            ],
            [
                'label_key' => 'clinics',
                'route' => 'modules.service-locations.index',
                'icon' => 'bi-building',
            ],
            [
                'label_key' => 'hospital_services',
                'route' => 'modules.hospital-services',
                'icon' => 'bi-hospital',
            ],
            [
                'label_key' => 'complaints',
                'route' => 'modules.complaints',
                'icon' => 'bi-chat-square-text',
            ],
            [
                'label_key' => 'employee_services',
                'route' => 'modules.employee-services',
                'icon' => 'bi-person-workspace',
            ],
        ],
    ],

    'navigation' => [
        'sidebar' => [
            ['label_key' => 'doctors', 'route' => 'modules.doctors', 'icon' => 'bi-person-vcard', 'active_prefix' => 'modules.doctors', 'subtitle_key' => 'doctors_directory.subtitle'],
            ['label_key' => 'clinics', 'route' => 'modules.service-locations.index', 'icon' => 'bi-building', 'active_prefix' => 'modules.service-locations.', 'subtitle_key' => 'service_locations.subtitle'],
            [
                'type' => 'group',
                'label_key' => 'inquiries',
                'icon' => 'bi-chat-left-text',
                'children' => [
                    [
                        'label_key' => 'inquiries_outgoing',
                        'route' => 'modules.inquiries.outgoing.index',
                        'icon' => 'bi-send',
                        'active_prefix' => 'modules.inquiries.outgoing',
                        'active_params' => ['direction' => 'outgoing'],
                        'subtitle_key' => 'inquiries.subtitle',
                    ],
                    [
                        'label_key' => 'inquiries_incoming',
                        'route' => 'modules.inquiries.incoming.index',
                        'icon' => 'bi-inbox',
                        'active_prefix' => 'modules.inquiries.incoming',
                        'active_params' => ['direction' => 'incoming'],
                        'subtitle_key' => 'inquiries.subtitle',
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label_key' => 'services',
                'icon' => 'bi-hospital',
                'children' => [
                    [
                        'label_key' => 'services_overview',
                        'route' => 'modules.hospital-services',
                        'icon' => 'bi-grid-3x3-gap',
                        'active_prefix' => 'modules.hospital-services',
                        'subtitle_key' => 'hospital_services.dashboard_subtitle',
                    ],
                    [
                        'label_key' => 'services_outpatient',
                        'route' => 'modules.services.sections.show',
                        'route_params' => ['section' => 1],
                        'icon' => 'bi-clipboard2-pulse',
                        'subtitle_key' => 'hospital_services.section_page_subtitle',
                    ],
                    [
                        'label_key' => 'services_radiology',
                        'route' => 'modules.services.sections.show',
                        'route_params' => ['section' => 2],
                        'icon' => 'bi-radioactive',
                        'subtitle_key' => 'hospital_services.section_page_subtitle',
                    ],
                    [
                        'label_key' => 'services_lab',
                        'route' => 'modules.services.sections.show',
                        'route_params' => ['section' => 3],
                        'icon' => 'bi-eyedropper',
                        'subtitle_key' => 'hospital_services.section_page_subtitle',
                    ],
                    [
                        'label_key' => 'services_physiotherapy',
                        'route' => 'modules.services.sections.show',
                        'route_params' => ['section' => 4],
                        'icon' => 'bi-heart-pulse',
                        'subtitle_key' => 'hospital_services.section_page_subtitle',
                    ],
                    [
                        'label_key' => 'services_inpatient',
                        'route' => 'modules.services.sections.show',
                        'route_params' => ['section' => 6],
                        'icon' => 'bi-hospital',
                        'subtitle_key' => 'hospital_services.section_page_subtitle',
                    ],
                    [
                        'label_key' => 'services_partnerships',
                        'route' => 'modules.services.sections.show',
                        'route_params' => ['section' => 11],
                        'icon' => 'bi-percent',
                        'subtitle_key' => 'hospital_services.section_page_subtitle',
                    ],
                    [
                        'label_key' => 'services_contracted_centers',
                        'route' => 'modules.services.sections.show',
                        'route_params' => ['section' => 12],
                        'icon' => 'bi-building',
                        'subtitle_key' => 'hospital_services.section_page_subtitle',
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label_key' => 'corporate_communication',
                'icon' => 'bi-journal-bookmark',
                'children' => [
                    [
                        'label_key' => 'government_circulars',
                        'route' => 'modules.government-circulars.index',
                        'icon' => 'bi-file-earmark-text',
                        'active_prefix' => 'modules.government-circulars.',
                        'subtitle_key' => 'government_circulars.subtitle',
                    ],
                    [
                        'label_key' => 'inspection_visits',
                        'route' => 'modules.inspection-visits.index',
                        'icon' => 'bi-clipboard2-check',
                        'active_prefix' => 'modules.inspection-visits.',
                        'subtitle_key' => 'inspection_visits.subtitle',
                    ],
                    [
                        'label_key' => 'data_requests',
                        'route' => 'modules.data-requests.index',
                        'icon' => 'bi-database',
                        'active_prefix' => 'modules.data-requests.',
                        'subtitle_key' => 'data_requests.subtitle',
                    ],
                    [
                        'label_key' => 'correspondence',
                        'route' => 'modules.correspondence.index',
                        'icon' => 'bi-envelope-open',
                        'active_prefix' => 'modules.correspondence.',
                        'subtitle_key' => 'correspondence.subtitle',
                    ],
                    [
                        'label_key' => 'outgoing_correspondence',
                        'route' => 'modules.outgoing-correspondence.index',
                        'icon' => 'bi-envelope-paper',
                        'active_prefix' => 'modules.outgoing-correspondence.',
                        'subtitle_key' => 'outgoing_correspondence.subtitle',
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label_key' => 'employee_services',
                'icon' => 'bi-person-workspace',
                'children' => [
                    [
                        'label_key' => 'employee_services_notice_of_absence',
                        'route' => 'modules.work-absence.notifications.index',
                        'icon' => 'bi-bell',
                        'active_prefix' => 'modules.work-absence.notifications.',
                        'subtitle_key' => 'employee_services.card_descriptions.notice_of_absence',
                    ],
                    [
                        'label_key' => 'employee_services_managing_absence',
                        'route' => 'modules.work-absence.dashboard',
                        'icon' => 'bi-clipboard-check',
                        'active_prefix' => 'modules.work-absence.dashboard',
                        'subtitle_key' => 'employee_services.card_descriptions.managing_absence_notices',
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label_key' => 'system_administration',
                'icon' => 'bi-shield-lock',
                'admin_only' => true,
                'children' => [
                    [
                        'label_key' => 'system_admin_overview',
                        'route' => 'modules.system-admin.dashboard',
                        'icon' => 'bi-speedometer2',
                        'active_prefix' => 'modules.system-admin.dashboard',
                        'subtitle_key' => 'system_administration.dashboard_subtitle',
                        'admin_only' => true,
                    ],
                    [
                        'label_key' => 'system_admin_services',
                        'route' => 'modules.system-admin.packages.index',
                        'icon' => 'bi-hospital',
                        'active_prefix' => 'modules.system-admin.packages.',
                        'subtitle_key' => 'system_administration.packages_subtitle',
                        'admin_only' => true,
                    ],
                    [
                        'label_key' => 'system_admin_doctors',
                        'route' => 'modules.doctors-admin.dashboard',
                        'icon' => 'bi-gear-wide-connected',
                        'active_prefix' => 'modules.doctors-admin.',
                        'subtitle_key' => 'doctors_directory_admin.dashboard_subtitle',
                        'admin_only' => true,
                    ],
                ],
            ],
            [
                'label_key' => 'system_admin_users',
                'route' => 'modules.system-admin.users.index',
                'icon' => 'bi-people',
                'active_prefix' => 'modules.system-admin.users.',
                'subtitle_key' => 'system_administration.users.scope_notice',
                'permission_admin_only' => true,
            ],
        ],
    ],

    'notifications' => [
        'enabled' => false,
        'items' => [],
    ],

    'cc_notifications' => [
        'enabled' => (bool) env('HM_CC_NOTIFICATIONS_ENABLED', false),
        'mail' => (bool) env('HM_CC_NOTIFICATIONS_MAIL', true),
        'sms' => (bool) env('HM_CC_NOTIFICATIONS_SMS', false),
        // Daily department reminders (inspection visits + data requests).
        'reminders' => (bool) env('HM_CC_REMINDERS_ENABLED', true),
        'reminders_at' => env('HM_CC_REMINDERS_AT', '07:00'),
        'timezone' => env('HM_CC_REMINDERS_TIMEZONE', 'Asia/Riyadh'),
    ],

    /*
    | SMS gateway used by Corporate Communication (and reusable elsewhere).
    | provider: log | mshastra | synapse
    | Use "log" locally; switch to mshastra/synapse with credentials in production.
    */
    'sms' => [
        'provider' => env('HM_SMS_PROVIDER', 'log'),
        'sender' => env('HM_SMS_SENDER', 'ALHAMMADI'),
        'mshastra' => [
            'user' => env('HM_SMS_MSHASTRA_USER', ''),
            'password' => env('HM_SMS_MSHASTRA_PASSWORD', ''),
        ],
        'synapse' => [
            'url' => env('HM_SMS_SYNAPSE_URL', 'https://api.synapse4sa.com/v1/multichannel/messages/sendsms'),
            'user' => env('HM_SMS_SYNAPSE_USER', ''),
            'password' => env('HM_SMS_SYNAPSE_PASSWORD', ''),
        ],
    ],

    'doctors_directory' => [
        'per_page' => (int) env('HM_DOCTORS_DIRECTORY_PER_PAGE', 12),
        'photos_path' => env('HM_CLINICIANS_PHOTOS_PATH', '/files'),
        // Main outpatient hospital branches shown on the speciality overview
        // (matches legacy clinic_details.php — Nuzha + Suwaidi).
        'outpatient_branch_ids' => [1, 3],
        'branch_images' => [
            1 => 'images/branches/nuzha.jpg',
            3 => 'images/branches/suwaidi.jpg',
        ],
        // Doctor codes assigned primarily to Suwaidi — strip erroneous Nuzha rows after cleanup.
        'suwaidi_primary_doctor_codes' => [
            '292', '19226', '18754', '17609',
            '90321', '90322', '90323',
        ],
    ],

    'system_administration' => [
        'per_page' => (int) env('HM_SYSTEM_ADMIN_PER_PAGE', 15),
        'cards' => [
            [
                'label_key' => 'packages',
                'route' => 'modules.system-admin.packages.index',
                'icon' => 'bi-hospital',
            ],
            [
                'label_key' => 'doctors_directory',
                'route' => 'modules.doctors-admin.dashboard',
                'icon' => 'bi-gear-wide-connected',
            ],
        ],
    ],

    'doctors_directory_admin' => [
        'per_page' => (int) env('HM_DOCTORS_DIRECTORY_ADMIN_PER_PAGE', 15),
        'cards' => [
            [
                'label_key' => 'specialities',
                'route' => 'modules.doctors-admin.specialities.index',
                'icon' => 'bi-diagram-3',
            ],
            [
                'label_key' => 'departments',
                'route' => 'modules.doctors-admin.departments.index',
                'icon' => 'bi-building',
            ],
            [
                'label_key' => 'doctors',
                'route' => 'modules.doctors-admin.doctors.index',
                'icon' => 'bi-person-vcard',
            ],
        ],
    ],

    'employee_leave' => [
        'per_page' => (int) env('HM_EMPLOYEE_LEAVE_PER_PAGE', 15),
        // Reserved ID for "Other" — custom text stored in emp_vacations.other_contacts
        'other_leave_type_id' => 99,
        'approval_status_ids' => [
            'approved' => 1,
            'rejected' => 2,
            'partial' => 3,
            'pending' => 4,
        ],
        'leave_types' => [
            1 => ['en' => 'Annual leave', 'ar' => 'إجازة سنوية'],
            2 => ['en' => 'Sick leave', 'ar' => 'إجازة مرضية'],
            3 => ['en' => 'Emergency leave', 'ar' => 'إجازة طارئة'],
            4 => ['en' => 'Unpaid leave', 'ar' => 'إجازة بدون راتب'],
            6 => ['en' => 'Hajj leave', 'ar' => 'إجازة حج'],
            8 => ['en' => 'Marriage leave', 'ar' => 'إجازة زواج'],
            12 => ['en' => 'Maternity leave', 'ar' => 'إجازة أمومة'],
            13 => ['en' => 'Bereavement leave', 'ar' => 'إجازة وفاة'],
            99 => ['en' => 'Other', 'ar' => 'أخرى'],
        ],
    ],

    'complaints' => [
        'per_page' => (int) env('HM_COMPLAINTS_PER_PAGE', 15),
        'dashboard_stats' => [
            'active' => [0, 1, 2, 3, 4],
            'processed' => 5,
            'closed' => 6,
        ],
        'types' => [
            1 => ['en' => 'Internal complaint', 'ar' => 'شكوى داخلية'],
            2 => ['en' => 'External complaint', 'ar' => 'شكوى خارجية'],
        ],
        'priorities' => [
            0 => ['en' => 'Low', 'ar' => 'منخفضة'],
            1 => ['en' => 'High', 'ar' => 'عالية'],
        ],
    ],

    'corporate_communication' => [
        'cards' => [
            [
                'label_key' => 'government_circulars',
                'description_key' => 'government_circulars.subtitle',
                'route' => 'modules.government-circulars.index',
                'icon' => 'bi-file-earmark-text',
            ],
            [
                'label_key' => 'inspection_visits',
                'description_key' => 'inspection_visits.subtitle',
                'route' => 'modules.inspection-visits.index',
                'icon' => 'bi-clipboard2-check',
            ],
            [
                'label_key' => 'data_requests',
                'description_key' => 'data_requests.subtitle',
                'route' => 'modules.data-requests.index',
                'icon' => 'bi-database',
            ],
            [
                'label_key' => 'correspondence',
                'description_key' => 'correspondence.subtitle',
                'route' => 'modules.correspondence.index',
                'icon' => 'bi-envelope-open',
            ],
            [
                'label_key' => 'outgoing_correspondence',
                'description_key' => 'outgoing_correspondence.subtitle',
                'route' => 'modules.outgoing-correspondence.index',
                'icon' => 'bi-envelope-paper',
            ],
        ],
    ],

    'government_circulars' => [
        'per_page' => (int) env('HM_GOVERNMENT_CIRCULARS_PER_PAGE', 15),
    ],

    'inspection_visits' => [
        'per_page' => (int) env('HM_INSPECTION_VISITS_PER_PAGE', 15),
    ],

    'data_requests' => [
        'per_page' => (int) env('HM_DATA_REQUESTS_PER_PAGE', 15),
    ],

    'correspondence' => [
        'per_page' => (int) env('HM_CORRESPONDENCE_PER_PAGE', 15),
    ],

    'outgoing_correspondence' => [
        'per_page' => (int) env('HM_OUTGOING_CORRESPONDENCE_PER_PAGE', 15),
    ],

    'inquiries' => [
        'per_page' => (int) env('HM_INQUIRIES_PER_PAGE', 15),
        'new_status_ids' => [999999, 1, 0],
        'forward_status_id' => 999999,
        'update_status_ids' => [3, 4, 5, 999999],
        'stat_statuses' => [
            'new' => [999999, 1, 0],
            'in_progress' => [3],
            'contacted' => [4],
            'contacted_not_booked' => [5],
        ],
        // Branch id => destination department (branch) ids for incoming list when scoped.
        'branch_incoming_sections' => [
            5 => [1],
            6 => [1],
        ],
    ],

    'employee_services' => [
        'cards' => [
            [
                'label_key' => 'notice_of_absence',
                'route' => 'modules.work-absence.notifications.index',
                'icon' => 'bi-bell',
            ],
            [
                'label_key' => 'managing_absence_notices',
                'route' => 'modules.work-absence.dashboard',
                'icon' => 'bi-clipboard-check',
            ],
        ],
    ],

    'work_absence_notification' => [
        'per_page' => (int) env('HM_WORK_ABSENCE_NOTIFICATION_PER_PAGE', 15),
        'permissions' => [
            'view' => 'work_absence_notification.view',
            'process' => 'work_absence_notification.process',
            'activate' => 'work_absence_notification.activate',
            'export' => 'work_absence_notification.export',
        ],
    ],

    'hospital_services' => [
        'per_page' => (int) env('HM_HOSPITAL_SERVICES_PER_PAGE', 15),
        'attachments_path' => env('HM_SERVICE_ATTACHMENTS_PATH', '/files'),
        'room_sections' => [7],
        // Legacy top-nav "الخدمات" dropdown order and labels.
        'section_nav_order' => [1, 2, 3, 4, 6, 11, 12],
        'section_nav_labels' => [
            1 => ['ar' => 'العيادات الخارجية', 'en' => 'Outpatient Clinics'],
            2 => ['ar' => 'الأشعة', 'en' => 'Radiology'],
            3 => ['ar' => 'المختبر', 'en' => 'Laboratory'],
            4 => ['ar' => 'العلاج الطبيعي', 'en' => 'Physiotherapy'],
            6 => ['ar' => 'التنويم', 'en' => 'Inpatient'],
            7 => ['ar' => 'الغرف و الأجنحة', 'en' => 'Rooms and Suites'],
            8 => ['ar' => 'وحدة العقم و الإنجاب', 'en' => 'Infertility and Reproduction Unit'],
            9 => ['ar' => 'وحدة المناظير', 'en' => 'Endoscopy Unit'],
            10 => ['ar' => 'وحدة غسيل الكلى', 'en' => 'Dialysis Unit'],
            11 => ['ar' => 'الشراكات و الخصومات', 'en' => 'Partnerships and Discounts'],
            12 => ['ar' => 'المراكز المتعاقدة الطبية', 'en' => 'Contracted Medical Centers'],
        ],
        'section_nav_children' => [
            6 => [7, 8, 9, 10],
        ],
        // Legacy service_details.php?id=6 (التنويم) — parent only; sub-sections use their own ids.
        'section_content_aliases' => [
            6 => 7,
        ],
        'sections' => [
            'outpatient' => 1,
            'lab' => 3,
            'radiology' => 2,
            'agreements' => 11,
        ],
        'cards' => [
            [
                'label_key' => 'outpatient',
                'route' => 'modules.services.sections.show',
                'section_id' => 1,
                'icon' => 'bi-clipboard2-pulse',
            ],
            [
                'label_key' => 'lab',
                'route' => 'modules.services.sections.show',
                'section_id' => 3,
                'icon' => 'bi-droplet',
            ],
            [
                'label_key' => 'radiology',
                'route' => 'modules.services.sections.show',
                'section_id' => 2,
                'icon' => 'bi-radioactive',
            ],
            [
                'label_key' => 'private_rooms',
                'route' => 'modules.services.rooms.index',
                'icon' => 'bi-door-closed',
            ],
            [
                'label_key' => 'agreements',
                'route' => 'modules.services.sections.show',
                'section_id' => 11,
                'icon' => 'bi-percent',
            ],
        ],
    ],

    'service_locations' => [
        // Ordered department lists matching the legacy outpatient_clinics_sections.php pages.
        // Used when the local DB does not link every speciality directly to outpatient_clinics_id.
        'opd_departments' => [
            1 => [
                ['speciality_id' => 7],
                ['speciality_id' => 1],
                ['speciality_id' => 20],
                ['speciality_id' => 31],
                ['speciality_id' => 39],
                ['speciality_id' => 16],
                ['speciality_id' => 27],
                ['speciality_id' => 12],
                ['label_key' => 'breast_feeding_department'],
                ['speciality_id' => 11],
                ['speciality_id' => 8],
                ['speciality_id' => 19],
                ['speciality_id' => 60],
                ['speciality_id' => 58],
                ['speciality_id' => 35],
            ],
            2 => [
                ['speciality_id' => 7],
                ['speciality_id' => 11],
                ['speciality_id' => 60],
                ['speciality_id' => 9],
                ['speciality_id' => 16],
                ['speciality_id' => 65],
                ['speciality_id' => 39],
            ],
            3 => [
                ['speciality_id' => 1],
                ['speciality_id' => 4],
                ['speciality_id' => 45],
                ['speciality_id' => 6],
                ['speciality_id' => 8],
                ['speciality_id' => 40],
            ],
            4 => [
                ['speciality_id' => 14],
                ['speciality_id' => 15],
                ['speciality_id' => 17],
            ],
            5 => [
                ['speciality_id' => 20],
                ['speciality_id' => 25],
                ['speciality_id' => 23],
                ['speciality_id' => 42],
                ['speciality_id' => 22],
                ['speciality_id' => 30],
                ['speciality_id' => 24],
                ['speciality_id' => 36],
                ['speciality_id' => 21],
                ['speciality_id' => 31],
            ],
            6 => [
                ['speciality_id' => 12],
                ['speciality_id' => 9],
                ['speciality_id' => 28],
                ['speciality_id' => 8],
                ['speciality_id' => 27],
                ['speciality_id' => 13],
                ['speciality_id' => 26],
                ['speciality_id' => 11],
                ['speciality_id' => 1],
                ['speciality_id' => 2],
                ['speciality_id' => 3],
                ['speciality_id' => 4],
                ['speciality_id' => 7],
            ],
        ],
    ],

];
