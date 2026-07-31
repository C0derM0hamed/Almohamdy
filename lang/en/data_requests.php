<?php

return [

    'title' => 'Government Data Requests',
    'subtitle' => 'Register government data requests and follow department responses.',
    'list' => 'Data Requests',
    'list_subtitle' => 'Filter by date, entity, hospital, department, or status.',
    'create' => 'Add Data Request',
    'create_subtitle' => 'Register a data request received from a government entity.',
    'detail' => 'Request details',
    'detail_subtitle' => 'Request information, attachments, and recipients.',
    'back_to_list' => 'Back to data requests',
    'back_to_detail' => 'Back to request',

    'receipt' => [
        'title' => 'Receipt report',
        'subtitle' => 'Track delivery and viewing status for notified recipients.',
        'summary' => ':viewed of :total recipients viewed',
        'recipient' => 'Recipient',
        'department' => 'Department',
        'channels' => 'Channels',
        'viewing_status' => 'Viewing status',
        'viewed' => 'Viewed',
        'not_viewed' => 'Not viewed',
        'empty' => 'No recipients have been notified for this request yet.',
        'print' => 'Print receipt',
    ],

    'department_reply' => [
        'title' => 'Department data reply',
        'subtitle' => 'Provide the requested data and attach supporting files.',
        'answer' => 'Reply details',
        'files' => 'Attachments',
        'return_reason' => 'Return reason from Corporate Communication',
        'confirm' => 'I confirm the reply is accurate and complete.',
        'submit' => 'Submit reply',
        'success' => 'Thank you. Your reply was submitted successfully.',
        'already_replied' => 'This request is not awaiting a department reply.',
        'invalid_link' => 'This reply link is invalid or expired.',
        'result_title' => 'Reply result',
        'result_success' => 'Submitted',
        'result_error' => 'Unable to continue',
        'open_link' => 'Open department reply link',
    ],

    'status_form' => [
        'title' => 'Update request status',
        'subtitle' => 'Move the request to the next workflow status.',
        'status' => 'New status',
        'reason' => 'Reason / notes',
        'notice_file' => 'Official notice file',
        'notice_name' => 'Notice file name',
        'submit' => 'Update status',
        'empty_statuses' => 'No further status changes are available for this request.',
    ],

    'timeline' => [
        'title' => 'Timeline',
        'empty' => 'No timeline entries yet.',
    ],

    'notices' => [
        'title' => 'Government entity notices',
        'empty' => 'No official notice files recorded yet.',
    ],

    'counters' => [
        'title' => 'Status overview',
    ],

    'filters' => [
        'title' => 'Search filters',
        'from_date' => 'From date',
        'to_date' => 'To date',
        'entity' => 'Government entity',
        'entity_all' => 'All entities',
        'branch' => 'Hospital',
        'branch_all' => 'All hospitals',
        'section' => 'Concerned department',
        'section_all' => 'All departments',
        'status' => 'Status',
        'status_all' => 'All statuses',
        'search' => 'Search',
        'reset' => 'Reset',
    ],

    'table' => [
        'request_number' => 'Request number',
        'date' => 'Date',
        'entity' => 'Entity',
        'section_branch' => 'Department / Hospital',
        'subject' => 'Subject',
        'status' => 'Status',
        'actions' => 'Actions',
        'empty' => 'No data requests found.',
        'empty_filtered' => 'No data requests match the current filters.',
    ],

    'fields' => [
        'branch' => 'Hospital',
        'entity' => 'Government entity',
        'request_date' => 'Data request date',
        'receipt_date' => 'Receipt date',
        'receiving_method' => 'Request receiving method',
        'subject' => 'Subject',
        'data_type' => 'Data type',
        'section' => 'Concerned department',
        'section_hint' => 'Hold Ctrl/Cmd to select multiple departments (creates one request per department).',
        'deadline' => 'Data submission deadline',
        'reminder_at' => 'Reminder date',
        'attachments' => 'Attachments',
        'attachment_name' => 'Attachment name',
        'status' => 'Status',
        'reason' => 'Reason / notes',
    ],

    'declarations' => [
        'confirm_entity' => 'I confirm the requesting entity, request date, and receipt date are accurate.',
        'confirm_subject' => 'I confirm the receipt method, data type, and subject are accurate.',
        'confirm_department' => 'I confirm the correct department and required data submission deadline.',
    ],

    'recipients' => [
        'title' => 'Notified recipients',
        'empty' => 'No recipients have been notified for this request yet.',
        'name' => 'Recipient',
        'department' => 'Department',
        'viewing_status' => 'Viewing status',
        'viewed' => 'Viewed',
        'not_viewed' => 'Not viewed',
    ],

    'actions' => [
        'menu' => 'Actions',
        'view' => 'View',
        'receipt' => 'Receipt report',
        'update_status' => 'Update status',
        'save' => 'Save request',
        'cancel' => 'Cancel',
    ],

    'flash' => [
        'created' => 'Government data request registered successfully.',
        'created_count' => ':count government data request(s) registered successfully.',
        'status_updated' => 'Request status updated successfully.',
    ],

    'status_unknown' => 'Unknown',
    'open_attachment' => 'Open',
    'no_attachment' => 'No attachments uploaded.',

    'validation' => [
        'confirm_entity' => 'Please confirm the entity and date declaration.',
        'confirm_subject' => 'Please confirm the subject and method declaration.',
        'confirm_department' => 'Please confirm the department and deadline declaration.',
        'section_required' => 'Please select at least one concerned department.',
        'reason_required' => 'A reason is required for escalation or return.',
        'notice_file_required' => 'Upload the official notice file when notifying the government entity.',
        'status_unchanged' => 'Please choose a different status.',
    ],

];
