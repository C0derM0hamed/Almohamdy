<?php

return [

    'title' => 'Outgoing Correspondence',
    'subtitle' => 'Draft and track outgoing corporate communication letters.',
    'list' => 'Outgoing Correspondence',
    'list_subtitle' => 'Filter by date, sector, authority, hospital, subject, or status.',
    'create' => 'Add Outgoing Letter',
    'create_subtitle' => 'Prepare an outgoing letter to an external authority or entity.',
    'detail' => 'Letter details',
    'detail_subtitle' => 'Letter content, attachments, and workflow timeline.',
    'back_to_list' => 'Back to outgoing letters',
    'back_to_detail' => 'Back to letter',

    'status_form' => [
        'title' => 'Update letter status',
        'subtitle' => 'Move the outgoing letter to the next workflow status.',
        'status' => 'New status',
        'reason' => 'Reason / notes',
        'confirm_approval' => 'I confirm I reviewed the prepared letter and it complies with the adopted standards.',
        'shipment_number' => 'Shipment number',
        'date_time_receipt' => 'Receipt / delivery date & time',
        'postal_employee_name' => 'Postal employee name',
        'return_date' => 'Return date',
        'registration_number' => 'Registration number',
        'delivered_by' => 'Delivered by',
        'delivery_date' => 'Delivery date',
        'status_file' => 'Supporting file',
        'reply_file' => 'Entity reply file',
        'supplementary_content' => 'Supplementary letter content',
        'submit' => 'Update status',
        'empty_statuses' => 'No further status changes are available for this letter.',
    ],

    'supplementary' => [
        'title' => 'Supplementary letters',
        'empty' => 'No supplementary letters recorded yet.',
        'serial' => 'Serial',
        'created_at' => 'Created',
        'content' => 'Content',
    ],

    'counters' => [
        'title' => 'Status overview',
    ],

    'filters' => [
        'from_date' => 'From date',
        'to_date' => 'To date',
        'sector' => 'Sector',
        'sector_all' => 'All sectors',
        'authority' => 'Destination authority',
        'authority_all' => 'All authorities',
        'branch' => 'Hospital',
        'branch_all' => 'All hospitals',
        'status' => 'Status',
        'status_all' => 'All statuses',
        'subject' => 'Subject',
        'subject_placeholder' => 'Search subject…',
        'search' => 'Search',
        'reset' => 'Reset',
    ],

    'table' => [
        'number' => 'Letter number',
        'date' => 'Issue date',
        'authority' => 'Authority',
        'branch' => 'Hospital',
        'subject' => 'Subject',
        'status' => 'Status',
        'actions' => 'Actions',
        'empty' => 'No outgoing correspondence found.',
        'empty_filtered' => 'No letters match the current filters.',
    ],

    'fields' => [
        'branch' => 'Hospital',
        'sector' => 'Sector',
        'authority' => 'Destination authority',
        'sender_title' => 'Recipient title',
        'issue_date' => 'Issue date',
        'recipient_name' => 'Recipient name',
        'sender_gender' => 'Recipient gender',
        'male' => 'Male',
        'female' => 'Female',
        'job_title' => 'Recipient job title',
        'receiving_mechanism' => 'Sending mechanism',
        'subject' => 'Subject',
        'letter_content' => 'Letter content',
        'response_deadline' => 'Expected response date',
        'template' => 'Template',
        'template_none' => 'No template',
        'attachments' => 'Attachments',
        'attachment_name' => 'Attachment name',
        'registration_number' => 'Registration number',
        'year' => 'Year',
        'status' => 'Status',
    ],

    'declarations' => [
        'confirm_recipient' => 'I confirm the recipient, authority, and sector are accurate.',
        'confirm_content' => 'I confirm the subject and letter content are accurate.',
    ],

    'timeline' => [
        'title' => 'Timeline',
        'empty' => 'No timeline entries yet.',
        'notice' => 'Notes',
    ],

    'actions' => [
        'menu' => 'Actions',
        'view' => 'View',
        'update_status' => 'Update status',
        'save' => 'Save letter',
        'cancel' => 'Cancel',
    ],

    'flash' => [
        'created' => 'Outgoing letter registered successfully.',
        'status_updated' => 'Letter status updated successfully.',
    ],

    'print' => [
        'title' => 'Outgoing letter',
        'button' => 'Print / PDF',
        'print' => 'Print',
    ],

    'department_revise' => [
        'title' => 'Revise returned letter',
        'subtitle' => 'Update the letter subject and content after Corporate Communication returned it.',
        'confirm' => 'I confirm the revised letter content is accurate.',
        'submit' => 'Submit revision',
        'success' => 'Thank you. The letter was revised successfully.',
        'not_returned' => 'This letter is not currently returned to the department.',
        'invalid_link' => 'This revise link is invalid or expired.',
        'result_title' => 'Revision result',
        'result_success' => 'Submitted',
        'result_error' => 'Unable to continue',
        'open_link' => 'Open revise link',
    ],

    'status_unknown' => 'Unknown',
    'open_attachment' => 'Open',
    'no_attachment' => 'No attachments uploaded.',

    'validation' => [
        'confirm_recipient' => 'Please confirm the recipient and authority declaration.',
        'confirm_content' => 'Please confirm the subject and content declaration.',
        'confirm_approval' => 'Please confirm approval before continuing.',
        'reason_required' => 'A reason is required for this status.',
        'shipment_number_required' => 'Shipment number is required.',
        'receipt_datetime_required' => 'Receipt date and time are required.',
        'postal_employee_required' => 'Postal employee name is required.',
        'return_date_required' => 'Return date is required.',
        'registration_number_required' => 'Registration number is required.',
        'delivered_by_required' => 'Delivered by is required.',
        'delivery_date_required' => 'Delivery date is required.',
        'status_file_required' => 'A supporting file is required for this status.',
        'reply_file_required' => 'Upload the entity reply file.',
        'supplementary_content_required' => 'Supplementary letter content is required.',
        'status_unchanged' => 'Please choose a different status.',
    ],

];
