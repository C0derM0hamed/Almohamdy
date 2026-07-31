<?php

return [

    'title' => 'System Administration',
    'dashboard' => 'System Administration',
    'dashboard_subtitle' => 'Manage editable system settings available to administrators only.',
    'dashboard_search_placeholder' => 'Search modules…',
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
    ],

    'cards' => [
        'packages' => 'Hospital Services',
        'doctors_directory' => 'Doctors Directory',
    ],

    'card_descriptions' => [
        'packages' => 'Edit service names, codes, and prices.',
        'doctors_directory' => 'Manage specialities, departments, and doctors.',
    ],

];
