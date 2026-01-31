<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Role-Based Access Control (RBAC) Permissions
    |--------------------------------------------------------------------------
    |
    | Define all application permissions grouped by module.
    | These permissions are used in authorization middleware and policies.
    |
    */

    'roles' => [
        'owner' => 'Owner',
        'manager' => 'Manager',
        'advisor' => 'Service Advisor',
        'mechanic' => 'Mechanic',
        'accountant' => 'Accountant',
    ],

    'permissions' => [
        // Job Cards
        'job_cards' => [
            'view-job-cards',
            'create-job-cards',
            'edit-job-cards',
            'delete-job-cards',
            'approve-job-cards',
            'complete-job-cards',
        ],

        // Customers
        'customers' => [
            'view-customers',
            'create-customers',
            'edit-customers',
            'delete-customers',
        ],

        // Vehicles
        'vehicles' => [
            'view-vehicles',
            'create-vehicles',
            'edit-vehicles',
            'delete-vehicles',
        ],

        // Invoices
        'invoices' => [
            'view-invoices',
            'create-invoices',
            'edit-invoices',
            'delete-invoices',
            'approve-invoices',
        ],

        // Payments
        'payments' => [
            'view-payments',
            'create-payments',
            'edit-payments',
            'delete-payments',
        ],

        // Inventory
        'inventory' => [
            'view-inventory',
            'create-inventory',
            'edit-inventory',
            'delete-inventory',
            'adjust-inventory',
        ],

        // Appointments
        'appointments' => [
            'view-appointments',
            'create-appointments',
            'edit-appointments',
            'delete-appointments',
            'confirm-appointments',
        ],

        // Reports
        'reports' => [
            'view-reports',
            'export-reports',
        ],

        // Settings
        'settings' => [
            'view-settings',
            'edit-settings',
        ],

        // Users
        'users' => [
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
        ],

        // Expenses
        'expenses' => [
            'view-expenses',
            'create-expenses',
            'edit-expenses',
            'delete-expenses',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Permissions Mapping
    |--------------------------------------------------------------------------
    |
    | Define which permissions each role has by default.
    |
    */
    'role_permissions' => [
        'owner' => ['*'], // All permissions
        'manager' => [
            'view-job-cards', 'create-job-cards', 'edit-job-cards', 'approve-job-cards',
            'view-customers', 'create-customers', 'edit-customers',
            'view-vehicles', 'create-vehicles', 'edit-vehicles',
            'view-invoices', 'create-invoices', 'edit-invoices', 'approve-invoices',
            'view-payments', 'create-payments',
            'view-inventory', 'edit-inventory',
            'view-appointments', 'create-appointments', 'edit-appointments', 'confirm-appointments',
            'view-reports', 'export-reports',
            'view-users',
            'view-expenses', 'create-expenses', 'edit-expenses',
        ],
        'advisor' => [
            'view-job-cards', 'create-job-cards', 'edit-job-cards',
            'view-customers', 'create-customers', 'edit-customers',
            'view-vehicles', 'create-vehicles', 'edit-vehicles',
            'view-invoices', 'create-invoices',
            'view-appointments', 'create-appointments', 'edit-appointments', 'confirm-appointments',
        ],
        'mechanic' => [
            'view-job-cards', 'edit-job-cards',
            'view-customers',
            'view-vehicles',
            'view-inventory',
        ],
        'accountant' => [
            'view-invoices', 'edit-invoices',
            'view-payments', 'create-payments', 'edit-payments',
            'view-expenses', 'create-expenses', 'edit-expenses',
            'view-reports', 'export-reports',
        ],
    ],
];
