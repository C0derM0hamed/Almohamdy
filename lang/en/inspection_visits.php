<?php

return [

    'title' => 'Inspection Committee Visits',
    'subtitle' => 'Document government inspection visits and follow responses by department.',
    'list' => 'Inspection Visits',
    'list_subtitle' => 'Filter by date, visit type, authority, hospital, department, or status.',
    'create' => 'New Visit',
    'create_subtitle' => 'Register an inspection visit from a government entity.',
    'detail' => 'Visit details',
    'detail_subtitle' => 'Visit information, findings, and timeline.',
    'back_to_list' => 'Back to visits',
    'back_to_detail' => 'Back to visit',

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
        'empty' => 'No recipients have been notified for this visit yet.',
        'print' => 'Print receipt',
    ],

    'status_form' => [
        'title' => 'Update request status',
        'subtitle' => 'Move the visit to the next workflow status.',
        'status' => 'New status',
        'notice_file' => 'Official notice / reply file',
        'return_reasons' => 'Return reasons by finding',
        'reason' => 'Return reason',
        'submit' => 'Update status',
        'empty_statuses' => 'No further status changes are available for this visit.',
    ],

    'attachments' => [
        'title' => 'Attachments',
        'empty' => 'No attachments uploaded for this visit.',
        'upload' => 'Upload attachment',
        'name' => 'Attachment name',
        'file' => 'File',
        'open' => 'Open',
        'uploaded_at' => 'Uploaded at',
    ],

    'notices' => [
        'title' => 'Government entity notices',
        'empty' => 'No official notice files recorded yet.',
    ],

    'returns' => [
        'title' => 'Return history',
        'empty' => 'No return reasons recorded yet.',
        'finding' => 'Finding',
        'reason' => 'Reason',
    ],

    'counters' => [
        'title' => 'Status overview',
    ],

    'filters' => [
        'title' => 'Search filters',
        'from_date' => 'From date',
        'to_date' => 'To date',
        'visit_type' => 'Visit type',
        'visit_type_all' => 'All visit types',
        'authority' => 'Government entity',
        'authority_all' => 'All entities',
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
        'visit_number' => 'Visit number',
        'visit_date' => 'Visit date',
        'visit_type' => 'Visit type',
        'authority' => 'Government entity',
        'branch' => 'Hospital',
        'section' => 'Department',
        'subject' => 'Subject',
        'status' => 'Status',
        'actions' => 'Actions',
        'empty' => 'No inspection visits found.',
        'empty_filtered' => 'No visits match the current filters.',
    ],

    'fields' => [
        'visit_type' => 'Visit type',
        'branch' => 'Hospital',
        'visit_date' => 'Visit date',
        'subject' => 'Subject',
        'report' => 'Visit report',
        'authority' => 'Government entity',
        'representative_name' => 'Government visitor / representative',
        'section' => 'Concerned department',
        'abuses_status' => 'Violation status',
        'notes_status' => 'Observation / notes status',
        'reply_time' => 'Response deadline',
        'findings' => 'Violations and notes',
        'finding_type' => 'Type',
        'finding_title' => 'Title / description',
        'status' => 'Status',
        'attachments' => 'Attachments',
        'yes' => 'Yes',
        'no' => 'No',
        'violation' => 'Violation',
        'note' => 'Observation / note',
    ],

    'declarations' => [
        'confirm_details' => 'I confirm the visit details, department, and findings are accurate.',
    ],

    'actions' => [
        'menu' => 'Actions',
        'view' => 'View',
        'receipt' => 'Receipt report',
        'update_status' => 'Update status',
        'save' => 'Save visit',
        'cancel' => 'Cancel',
        'add_finding' => 'Add finding',
        'remove_finding' => 'Remove',
    ],

    'timeline' => [
        'title' => 'Timeline',
        'empty' => 'No timeline entries yet.',
    ],

    'findings_list' => [
        'title' => 'Findings',
        'empty' => 'No violations or notes recorded for this visit.',
    ],

    'flash' => [
        'created' => 'Inspection visit registered successfully.',
        'status_updated' => 'Visit status updated successfully.',
        'attachment_uploaded' => 'Attachment uploaded successfully.',
    ],

    'status_unknown' => 'Unknown',

    'department_reply' => [
        'title' => 'Department response',
        'returned_title' => 'Resubmit returned response',
        'subtitle' => 'Reply to each finding and attach supporting evidence before the deadline.',
        'finding' => 'Finding',
        'reply' => 'Department reply',
        'evidence' => 'Evidence file (optional)',
        'return_reason' => 'Return reason',
        'confirm' => 'I confirm the response is accurate and the department is responsible for its content.',
        'submit' => 'Submit response',
        'success' => 'Thank you. Your response was submitted successfully.',
        'already_replied' => 'This visit has already been replied to, or there are no pending items.',
        'invalid_link' => 'This reply link is invalid or expired.',
        'invalid_return_item' => 'One or more returned items are invalid.',
        'result_title' => 'Response result',
        'result_success' => 'Submitted',
        'result_error' => 'Unable to continue',
        'open_link' => 'Open department reply link',
        'open_returned_link' => 'Open returned reply link',
    ],

    'validation' => [
        'confirm_details' => 'Please confirm the visit details declaration.',
        'reply_time_required' => 'A response deadline is required when violations exist.',
        'violations_required' => 'Add at least one violation when violation status is Yes.',
        'notes_required' => 'Add at least one observation/note when notes status is Yes.',
        'findings_not_allowed' => 'Remove findings unless violation or notes status is Yes.',
        'notice_file_required' => 'Upload the official notice file when notifying the government entity.',
        'return_reasons_required' => 'Enter at least one return reason for the selected findings.',
        'invalid_finding' => 'One or more findings do not belong to this visit.',
        'status_unchanged' => 'Please choose a different status.',
    ],

];
