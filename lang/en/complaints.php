<?php

return [

    'title' => 'Complaints',
    'subtitle' => 'Overview of complaints and follow-up workflows.',
    'dashboard' => 'Complaints dashboard',
    'dashboard_subtitle' => 'Overview of complaint status and quick access to the list.',
    'filters_title' => 'Search and filter',
    'list' => 'Complaints list',
    'list_subtitle' => 'Search and filter complaints by file number or status.',
    'detail' => 'Complaint details',
    'detail_subtitle' => 'Complaint details and investigation timeline.',
    'timeline' => 'Complaint timeline',
    'timeline_subtitle' => 'Status history from complaint replies.',
    'timeline_modal_subtitle' => 'Complaint no. :number — :department',
    'timeline_last_update' => 'Last update',
    'close' => 'Close',
    'view_list' => 'View complaints',
    'back_to_list' => 'Back to list',
    'print' => 'Print',
    'view_detail' => 'View details',
    'view_timeline' => 'View timeline',
    'search' => 'Search',
    'reset' => 'Reset',
    'sort' => 'Sort',
    'refresh' => 'Refresh',
    'export' => 'Export',
    'columns_settings' => 'Column settings',
    'table_tools' => 'Complaint table tools',
    'complaints_count' => ':count complaints',
    'results_summary' => 'Showing :shown of :total complaints',
    'no_complaints' => 'No complaints found.',
    'no_results' => 'No complaints match your filters.',
    'no_timeline' => 'No timeline events recorded for this complaint.',
    'create_title' => 'Create complaint',
    'create_subtitle' => 'Register a new complaint using the verified fields.',
    'create_success' => 'Complaint created successfully.',
    'reply_title' => 'Add reply and status',
    'reply_success' => 'Reply saved and complaint status updated.',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'workflow' => ['terminal' => 'Completed and closed complaints cannot be changed.', 'sequential' => 'Complaint statuses must be recorded sequentially.', 'repeated' => 'This status has already been recorded.'],

    'stats' => [
        'total' => 'Total complaints',
        'active' => 'Active complaints',
        'processed' => 'Processed',
        'closed' => 'Closed',
    ],

    'summary' => [
        'aria_label' => 'Complaints summary',
    ],

    'insights' => [
        'aria_label' => 'Complaints insights',
        'processing_rate' => 'Processing rate',
        'most_active_department' => 'Most active department',
        'latest_update' => 'Latest update',
        'today_at' => 'Today :time',
    ],

    'filters' => [
        'search' => 'File or complaint number',
        'search_placeholder' => 'Enter file or complaint number',
        'status' => 'Status',
        'all_statuses' => 'All statuses',
    ],

    'columns' => [
        'complaint_no' => 'Complaint no.',
        'file_no' => 'File no.',
        'complainant' => 'Complainant',
        'department' => 'Department',
        'date' => 'Date',
        'status' => 'Status',
        'priority' => 'Priority',
        'actions' => 'Actions',
    ],

    'sections' => [
        'complaint_info' => 'Complaint information',
        'complainant_info' => 'Complainant information',
        'investigation' => 'Investigation',
    ],

    'fields' => [
        'complaint_no' => 'Complaint no.',
        'file_no' => 'File no.',
        'complainant' => 'Complainant',
        'patient' => 'Patient',
        'department' => 'Department',
        'mobile' => 'Mobile',
        'type' => 'Type',
        'date' => 'Complaint date',
        'created_at' => 'Created at',
        'defendant' => 'Defendant',
        'details' => 'Details',
        'result' => 'Result',
        'employee_investigation' => 'Employee investigation',
        'status' => 'Current status',
        'id_no' => 'National ID',
        'event_date' => 'Event date',
        'attachment' => 'Attachment',
        'reply' => 'Reply',
    ],

    'status' => [
        'new' => 'New',
    ],

];
