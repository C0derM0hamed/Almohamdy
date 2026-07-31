<?php

return [

    'title' => 'Inquiries',
    'subtitle' => 'Browse and manage incoming and outgoing inquiries.',
    'outgoing' => 'Outgoing inquiries',
    'incoming' => 'Incoming inquiries',
    'outgoing_page_title' => 'Inquiries and Services — Sent from the Department',
    'incoming_page_title' => 'Inquiries and Services — Received by the Department',
    'search' => 'Search',
    'reset' => 'Reset',
    'new_inquiry' => 'New inquiry',
    'total_records' => ':count records',
    'form' => 'Form',
    'view_pdf' => 'PDF',
    'download_pdf' => 'Download PDF report',
    'timeline' => 'Inquiry timeline',
    'timeline_subtitle' => 'Status history for this inquiry',
    'timeline_loading' => 'Loading timeline…',
    'timeline_load_error' => 'Unable to load the inquiry timeline. Please try again.',
    'close' => 'Close',
    'no_results' => 'No inquiries match your filters.',
    'no_inquiries' => 'No inquiries found.',
    'no_timeline' => 'No timeline entries yet.',
    'view_timeline' => 'View timeline',
    'view_list' => 'Back to list',
    'print' => 'Print',
    'add_status' => 'Add status',

    'nav' => [
        'outgoing' => 'Outgoing inquiries',
        'incoming' => 'Incoming inquiries',
    ],

    'stats' => [
        'new' => 'New',
        'in_progress' => 'Under Review',
        'contacted' => 'Contacted',
        'contacted_not_booked' => 'Contacted — no response from patient',
        'completed' => 'Completed',
    ],

    'filters' => [
        'date_from' => 'From date',
        'date_to' => 'To date',
        'status' => 'Status',
        'status_all' => 'All',
        'department' => 'Department sent to',
        'department_all' => 'All',
        'mobile' => 'Contact number',
    ],

    'columns' => [
        'date' => 'Date',
        'enquirer' => 'Patient name',
        'mobile' => 'Contact number',
        'department' => 'Department sent to',
        'sender_section' => 'Sending section',
        'status' => 'Status',
        'timeline' => 'Timeline',
        'form' => 'Form',
    ],

    'status' => [
        'new' => 'New',
    ],

    'update_statuses' => [
        '3' => 'Under Review',
        '4' => 'Contacted',
        '5' => 'Contacted – No Response from Patient',
        '999999' => 'Forwarded to Another Department',
        '6' => 'Completed',
    ],

    'status_form' => [
        'title' => 'Add status',
        'subtitle' => 'Update the inquiry status and record notes.',
        'sender' => 'Sender',
        'status' => 'Inquiry status',
        'status_placeholder' => 'Choose a status',
        'department' => 'Department',
        'department_placeholder' => 'Select a department',
        'department_required' => 'Please select a department.',
        'assignment' => 'Assignment',
        'assign_department' => 'Assign to Entire Department',
        'assign_employee' => 'Assign to Specific Employee',
        'employee' => 'Employee',
        'employee_placeholder' => 'Select an employee',
        'employee_required' => 'Please select an employee.',
        'employee_invalid' => 'Selected employee is not active in that department.',
        'employee_loading' => 'Loading employees…',
        'employee_empty' => 'No active employees found for this department.',
        'notes' => 'Notes',
        'notes_placeholder' => 'Optional notes before saving',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'success' => 'Inquiry status updated successfully.',
        'error' => 'Unable to update the inquiry status. Please try again.',
        'locked' => 'This status can no longer be changed. Only New or Under Review inquiries can be updated.',
    ],

    'form_fields' => [
        'enquirer' => 'Patient name',
        'mobile' => 'Contact number',
        'department' => 'Department sent to',
        'inquiry_type' => 'Inquiry type',
        'details' => 'Details',
        'date' => 'Date',
        'status' => 'Status',
    ],

    'timeline_actions' => [
        'created' => 'The inquiry request has been added.',
        '3' => 'Inquiry is under review.',
        '4' => 'Contact was made.',
        '5' => 'Contact was made and no response was received from the patient.',
        '6' => 'Inquiry completed.',
        '999999' => 'Inquiry forwarded to another department.',
    ],

    'timeline_audit' => [
        'status' => 'Status: :from → :to',
        'department' => 'Department: :from → :to',
        'assignee' => 'Assignee: :from → :to',
        'assignee_department' => 'Assigned to: Entire department (shared inbox)',
        'assignee_employee' => 'Assigned to: :name',
        'entire_department' => 'Entire department',
        'notes' => 'Notes: :notes',
    ],

    'pdf' => [
        'title' => 'Inquiry report #:id',
        'heading' => 'Inquiry and Services Report',
        'reference' => 'Inquiry #:id — generated :date',
        'inquiry_details' => 'Inquiry details',
        'created_by' => 'Created by',
        'sender_branch' => 'Sender department',
        'timeline_columns' => [
            'date' => 'Date',
            'time' => 'Time',
            'user' => 'User',
            'department' => 'Department',
            'action' => 'Action',
        ],
    ],

];
