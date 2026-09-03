<?php

return [

    'title' => 'System Administration',
    'dashboard' => 'System Administration',
    'dashboard_subtitle' => 'Manage editable system settings available to administrators only.',
    'dashboard_search_placeholder' => 'Search modules…',
    'dashboard_filter_subtitle' => 'Find the system module you want to manage.',
    'dashboard_section_title' => 'System modules',
    'dashboard_section_subtitle' => 'Choose a module to open and manage its settings.',
    'dashboard_count_label' => 'modules',
    'open_module' => 'Open module',
    'packages' => 'Hospital Services',
    'packages_subtitle' => 'Edit service names, codes, prices, and publish status.',
    'edit_package' => 'Edit service',
    'filters_title' => 'Filters',
    'search' => 'Search',
    'reset' => 'Reset',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'confirm_delete' => 'Delete this service permanently? This cannot be undone.',
    'save' => 'Save changes',
    'cancel' => 'Cancel',
    'publish' => 'Publish',
    'unpublish' => 'Unpublish',
    'view_public_catalog' => 'View public catalog',
    'manage_packages' => 'Manage hospital services',
    'manage_doctors_directory' => 'Manage doctors directory',
    'empty_title' => 'No services found',
    'no_packages' => 'No hospital services have been created yet.',
    'no_results' => 'No services match your filters.',
    'package_updated' => 'Service updated successfully.',
    'package_created' => 'Service created successfully.',
    'package_deleted' => 'Service deleted successfully.',
    'package_published' => 'Service published successfully.',
    'package_unpublished' => 'Service unpublished successfully.',
    'form_has_errors' => 'Please fix the highlighted fields and try again.',
    'form_basic_hint' => 'Update the service code, names, and price shown in the public catalog.',
    'form_publish_hint' => 'Show this service in the public hospital services catalog when enabled.',

    'errors' => [
        'unauthorized' => 'You do not have permission to access System Administration.',
    ],

    'filters' => [
        'search' => 'Search by code or name…',
        'section' => 'Service section',
        'publish' => 'Status',
        'all' => 'All',
        'all_sections' => 'All sections',
    ],

    'columns' => [
        'id' => 'ID',
        'code' => 'Code',
        'name_en' => 'Name (EN)',
        'name_ar' => 'Name (AR)',
        'section' => 'Section',
        'price' => 'Price',
        'status' => 'Status',
        'actions' => 'Actions',
    ],

    'fields' => [
        'id' => 'ID',
        'code' => 'Service code',
        'name_en' => 'Name (English)',
        'name_ar' => 'Name (Arabic)',
        'price' => 'Service price',
        'section' => 'Section',
        'published' => 'Published',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
    ],

    'sections' => [
        'basic_information' => 'Service details',
        'directory_settings' => 'Catalog settings',
    ],

    'status' => [
        'published' => 'Published',
        'unpublished' => 'Unpublished',
    ],

    'stats' => [
        'total' => 'Total services',
        'published' => 'Published',
        'unpublished' => 'Unpublished',
        'total_hint' => 'All services registered in the system.',
        'published_hint' => 'Services visible in the public catalogue.',
        'unpublished_hint' => 'Services hidden from the public catalogue.',
    ],

    'cards' => [
        'packages' => 'Hospital Services',
        'doctors_directory' => 'Doctors Directory',
        'groups' => 'User groups', 'job_titles' => 'Job titles', 'governmental_services' => 'Governmental services', 'companies' => 'Hospital Settings', 'branches' => 'Branches', 'departments' => 'Branch departments', 'needs' => 'Branch needs', 'work_areas' => 'Work areas', 'inquiry_types' => 'Inquiry types',
    ],

    'card_descriptions' => [
        'packages' => 'Edit service names, codes, and prices.',
        'doctors_directory' => 'Manage specialities, departments, and doctors.',
        'groups' => 'Manage published user groups.', 'job_titles' => 'Manage branch job titles.', 'governmental_services' => 'Manage governmental service types.', 'companies' => 'Manage hospital settings.', 'branches' => 'Manage branches.', 'departments' => 'Manage branch departments.', 'needs' => 'Manage branch needs.', 'work_areas' => 'Manage work areas by branch.', 'inquiry_types' => 'Manage inquiry types by branch.', 'service_types' => 'Manage branch service types.', 'complaint_closing_reasons' => 'Manage complaint closing reasons.', 'complaint_letter_receivers' => 'Manage complaint letter receivers.', 'complaint_statuses' => 'Manage complaint statuses.', 'post_types' => 'Manage publication types.', 'medical_terminology' => 'Manage medical terminology.', 'service_codes' => 'Manage service codes.',
    ],
    'reference' => ['scope' => 'Administrative reference data in the current management scope.', 'create' => 'Add', 'edit' => 'Edit', 'back' => 'Back', 'save' => 'Save', 'search' => 'Search by name or details', 'choose' => 'Choose', 'empty' => 'No records found.', 'name_ar' => 'Arabic name', 'name_en' => 'English name', 'status' => 'Status', 'published' => 'Published', 'unpublished' => 'Unpublished', 'toggle' => 'Toggle publish', 'delete' => 'Delete', 'saved' => 'Saved successfully.', 'status_changed' => 'Publish status updated.', 'deleted' => 'Deleted.', 'in_use' => 'This group is in use.', 'groups' => 'User groups', 'job_titles' => 'Job titles', 'governmental_services' => 'Governmental service types', 'companies' => 'Hospital Settings', 'branches' => 'Branches', 'departments' => 'Branch departments', 'notice_types' => 'Notice types', 'needs' => 'Branch needs', 'work_areas' => 'Work areas', 'inquiry_types' => 'Inquiry types', 'service_types' => 'Branch service types', 'complaint_closing_reasons' => 'Complaint closing reasons', 'complaint_letter_receivers' => 'Complaint letter receivers', 'complaint_statuses' => 'Complaint statuses', 'post_types' => 'Publication types', 'medical_terminology' => 'Medical terminology', 'service_codes' => 'Service codes', 'fields' => ['name_en' => 'English name', 'name_ar' => 'Arabic name', 'name_ch' => 'Short name', 'info' => 'Details', 'training_declarations_id' => 'Training declaration', 'platform_id' => 'Service platform', 'branch_id' => 'Branch', 'email' => 'Email', 'mobile' => 'Mobile', 'address' => 'Address', 'logo' => 'Logo', 'price' => 'Price', 'code' => 'Code']],

    'users' => [
        'title' => 'Users and permissions', 'create' => 'Create user', 'edit' => 'Edit user', 'details' => 'User details',
        'name' => 'Name', 'username' => 'Employee number', 'level' => 'Account type', 'scope' => 'Branch / department',
        'company' => 'Branch', 'branch' => 'Department', 'branch_help' => 'Select one or more departments from the dropdown.', 'branch_placeholder' => 'Select departments', 'branch_selected' => ':count department(s) selected',
        'mobile' => 'Mobile', 'email' => 'Email', 'department' => 'Department', 'permissions' => 'Permissions',
        'show_permissions' => 'Show permissions', 'close' => 'Close', 'unknown_branch' => 'Unknown branch',
        'group' => 'Group', 'status' => 'Status', 'active' => 'Active',
        'inactive' => 'Inactive', 'empty' => 'No users are available in your scope.', 'scope_notice' => 'Only accounts within your permitted administrative scope are shown.',
        'no_group' => 'No group', 'direct_permissions' => 'Direct permissions', 'inherited_permissions' => 'Inherited permissions',
        'effective_permissions' => 'Effective permission preview', 'none' => 'None', 'save' => 'Save', 'saved' => 'The user and permissions were saved.', 'cancel' => 'Cancel',
        'levels' => ['0' => 'Standard user', '1' => 'Branch user', '2' => 'Supervisor', '3' => 'Super Administrator', '4' => 'System user'],
        'fields' => ['hr_first_name' => 'First name', 'hr_last_name' => 'Last name', 'hr_email_address' => 'Email', 'hr_username' => 'Employee number', 'mobile' => 'Mobile', 'password' => 'Password', 'password_confirmation' => 'Confirm password'],
        'errors' => ['unauthorized' => 'You are not authorized to manage users.', 'out_of_scope' => 'The user or scope is outside your authority.', 'self_security_change' => 'You cannot change your own level, scope, group, or status.', 'final_super_admin' => 'The final active Super Administrator cannot be disabled or demoted.', 'invalid_permission' => 'An unknown permission was submitted.', 'privilege_escalation' => 'You cannot grant a permission you do not hold.'],
    ],

];
