<?php

return [

    'title' => 'Employee Leave',
    'dashboard' => 'Leave Dashboard',
    'dashboard_subtitle' => 'Overview of employee leave requests',
    'requests' => 'Leave Requests',
    'requests_subtitle' => 'Browse and manage leave applications',
    'total_records' => ':count records',
    'new_request' => 'New Leave Application',
    'new_request_subtitle' => 'Submit a new leave request',
    'request_detail' => 'Leave Request Details',
    'view_requests' => 'View all requests',
    'apply_leave' => 'Apply for leave',
    'application_submitted' => 'Your leave application has been submitted successfully.',
    'branch_processed' => 'Branch decision recorded successfully.',
    'hr_processed' => 'HR decision recorded successfully.',

    'stats' => [
        'total' => 'Total leave requests',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'columns' => [
        'request_no' => 'Request No',
        'employee' => 'Employee',
        'leave_type' => 'Leave Type',
        'from_date' => 'From Date',
        'to_date' => 'To Date',
        'days' => 'Days',
        'status' => 'Status',
        'actions' => 'Actions',
    ],

    'status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'fields' => [
        'leave_type' => 'Leave Type',
        'leave_type_other' => 'Specify leave type',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'reason' => 'Reason',
        'employee' => 'Employee',
        'email' => 'Email',
        'mobile' => 'Mobile',
        'job_title' => 'Job Title',
        'submitted_at' => 'Submitted At',
        'request_no' => 'Request No',
        'comment' => 'Comment',
    ],

    'sections' => [
        'employee_info' => 'Employee Information',
        'leave_details' => 'Leave Details',
        'status_history' => 'Status History',
        'branch_processing' => 'Branch Approval',
        'hr_processing' => 'HR Approval',
    ],

    'processing' => [
        'approve' => 'Approve',
        'reject' => 'Reject',
        'branch_hint' => 'Review this request and approve or reject at branch level.',
        'hr_hint' => 'Branch approval is complete. Record the final HR decision.',
        'comment_placeholder' => 'Optional comment for this decision…',
    ],

    'errors' => [
        'not_found' => 'Leave request not found.',
        'branch_not_pending' => 'This request is not awaiting branch approval.',
        'hr_not_pending' => 'This request is not awaiting HR approval.',
        'unauthorized' => 'You are not authorized to perform this action.',
        'permission' => [
            'employee_leave.view' => 'You do not have permission to view leave requests.',
            'employee_leave.apply' => 'You do not have permission to apply for leave.',
            'employee_leave.branch_process' => 'You do not have permission to process branch leave approvals.',
            'employee_leave.hr_process' => 'You do not have permission to process HR leave approvals.',
        ],
    ],

    'history' => [
        'branch' => 'Branch approval',
        'hr' => 'HR approval',
        'no_comment' => 'No comment',
    ],

    'filters' => [
        'search' => 'Search by request no or employee…',
        'status' => 'All statuses',
        'leave_type' => 'All leave types',
    ],

    'search' => 'Search',
    'reset' => 'Reset',
    'submit' => 'Submit application',
    'cancel' => 'Cancel',
    'view' => 'View',
    'no_results' => 'No leave requests match your filters.',
    'no_requests' => 'No leave requests found.',
    'no_history' => 'No status updates yet.',

    'select_leave_type' => 'Select leave type',
    'reason_placeholder' => 'Briefly describe the reason for your leave request…',
    'leave_type_other_placeholder' => 'Enter the leave type (e.g. study leave, compassionate leave…)',

];
