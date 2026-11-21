<?php

/*
|--------------------------------------------------------------------------
| Admin panel permissions
|--------------------------------------------------------------------------
| Frontend er src/assets/Backend/permissions.js er key er sathe HUBUHU mil
| thakte hobe. Notun menu banale duijaygay key add korte hobe.
*/

return [

    // Ei role gula shob permission pay (role e permission list lagbe na)
    'full_access_roles' => ['super_admin', 'admin'],

    'keys' => [
        // Main
        'dashboard',

        // Website Pages
        'home',
        'medical_services',
        'non_medical_services',
        'hospitals',
        'doctors',
        'packages',
        'plan_setup',
        'company',
        'media',
        'career',
        'contact_page',

        // Requests
        'queries',
        'treatment_plans',
        'appointments',
        'consultations',
        'medicine_requests',
        'bookings',
        'visa',
        'job_applications',
        'messages',

        // System
        'reports',
        'users',
        'settings',
    ],
];
