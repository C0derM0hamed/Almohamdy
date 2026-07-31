<?php

return [

    'title' => 'Government Circulars',
    'subtitle' => 'Register, search, and distribute incoming government circulars.',
    'list' => 'Government Circulars',
    'list_subtitle' => 'Search by authority, department, or subject.',
    'create' => 'New Request',
    'create_subtitle' => 'Register an incoming government circular.',
    'detail' => 'Circular details',
    'detail_subtitle' => 'Formal circular information and attachments.',
    'back_to_list' => 'Back to circulars',
    'back_to_detail' => 'Back to circular',

    'filters' => [
        'title' => 'Search filters',
        'authority' => 'Issuing authority',
        'authority_all' => 'All authorities',
        'section' => 'Concerned department',
        'section_all' => 'All departments',
        'branch' => 'Hospital / Branch',
        'branch_all' => 'All hospitals',
        'from_date' => 'From date',
        'to_date' => 'To date',
        'subject' => 'Subject',
        'subject_placeholder' => 'Search by subject',
        'search' => 'Search',
        'reset' => 'Reset',
    ],

    'table' => [
        'sent_date' => 'Sent / Issue date',
        'authority' => 'Issuing entity',
        'classification' => 'Classification',
        'subject' => 'Subject',
        'department' => 'Department',
        'status' => 'Status',
        'recipients' => 'Recipients',
        'actions' => 'Actions',
        'empty' => 'No government circulars found.',
        'empty_filtered' => 'No circulars match the current filters.',
    ],

    'receipt' => [
        'title' => 'Receipt report',
        'subtitle' => 'Track delivery and viewing status for notified recipients.',
        'summary' => ':viewed of :total recipients viewed',
        'recipient' => 'Recipient',
        'department' => 'Department',
        'channels' => 'Channels',
        'viewing_status' => 'Viewing status',
        'sent_at' => 'Notified at',
        'viewed' => 'Viewed',
        'not_viewed' => 'Not viewed',
        'empty' => 'No recipients have been notified for this circular yet.',
        'print' => 'Print receipt',
    ],

    'departments' => [
        'title' => 'Concerned departments',
        'subtitle' => 'Departments and notified responsible persons for this circular.',
        'department' => 'Department',
        'recipients' => 'Notified persons',
        'empty' => 'No concerned departments found for this circular.',
    ],

    'fields' => [
        'authority' => 'Issuing authority',
        'classification' => 'Classification',
        'issue_date' => 'Issue date',
        'received_date' => 'Receipt date',
        'receiving_mechanism' => 'Receipt method',
        'subject' => 'Subject',
        'notification_type' => 'Notification type',
        'branch' => 'Hospital / Branch',
        'section' => 'Concerned department',
        'section_hint' => 'Hold Ctrl/Cmd to select more than one primary department.',
        'cc_section' => 'Department CC (copy)',
        'cc_section_hint' => 'Optional departments to copy on the same circular.',
        'file' => 'Circular file',
        'extra_files' => 'Additional attachments',
        'extra_files_hint' => 'Optional — add more files with the primary circular.',
        'status' => 'Status',
        'attachments' => 'Attachments',
    ],

    'declarations' => [
        'title' => 'Declarations',
        'confirm_entity' => 'I confirm the issuing entity, dates, and receipt method are accurate.',
        'confirm_subject' => 'I confirm the subject matches the official document wording.',
        'confirm_attachments' => 'I confirm the original circular and related attachments are included.',
    ],

    'actions' => [
        'menu' => 'Actions',
        'view' => 'View',
        'receipt' => 'Receipt report',
        'departments' => 'Concerned departments',
        'update_status' => 'Update status',
        'save' => 'Save circular',
        'cancel' => 'Cancel',
    ],

    'flash' => [
        'created' => 'Government circular registered successfully.',
        'status_updated' => 'Circular status updated successfully.',
    ],

    'status_form' => [
        'title' => 'Update status',
        'subtitle' => 'Change the workflow status of this circular.',
        'status' => 'New status',
        'details' => 'Notes (optional)',
        'attachment' => 'Attachment (optional)',
        'attachment_hint' => 'Required when setting status to New unless classification is exempt.',
        'submit' => 'Save status',
        'empty_statuses' => 'No further status options are available for this circular.',
    ],

    'formal' => [
        'title' => 'Official government circular',
        'subtitle' => 'Review the formal circular details and open related attachments.',
        'instruction' => 'The responsible department must review, implement, follow up, and comply with this circular as required.',
        'open_primary' => 'Open circular file',
        'open_attachment' => 'Open attachment :n',
        'invalid_link' => 'This circular link is invalid or expired.',
        'result_title' => 'Circular link',
        'result_success' => 'Opened',
        'result_error' => 'Unable to continue',
        'open_link' => 'Open formal circular page',
    ],

    'status_unknown' => 'Unknown',
    'no_attachment' => 'No attachment uploaded.',
    'open_attachment' => 'Open attachment',

    'validation' => [
        'confirm_entity' => 'Please confirm the entity and date details.',
        'confirm_subject' => 'Please confirm the subject wording.',
        'confirm_attachments' => 'Please confirm the attachments declaration.',
        'status_invalid' => 'Selected status is not valid.',
        'attachment_required_for_status' => 'Please upload the attached file before setting this status.',
        'section_required' => 'Please select at least one concerned department.',
    ],

];
