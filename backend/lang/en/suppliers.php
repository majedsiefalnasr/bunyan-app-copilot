<?php

return [
    'not_found' => 'Supplier profile not found.',
    'already_exists' => 'A supplier profile already exists for this user.',
    'commercial_reg_taken' => 'A supplier with this commercial registration number already exists.',
    'role_required' => 'A valid contractor must be specified to create a profile.',
    'unauthorized_update' => 'You are not authorized to update this profile.',
    'created_successfully' => 'Supplier profile created successfully.',
    'updated_successfully' => 'Supplier profile updated successfully.',
    'verified_successfully' => 'Supplier verified successfully.',
    'suspended_successfully' => 'Supplier suspended successfully.',
    'list_title' => 'Suppliers',
    'list_subtitle' => 'Browse available suppliers and materials.',
    'admin_list_title' => 'Manage Suppliers',
    'view_profile' => 'View Profile',
    'my_profile' => 'My Supplier Profile',
    'profile_title' => 'Supplier Profile',
    'create' => 'Create Supplier Profile',
    'verify' => 'Verify',
    'suspend' => 'Suspend',
    'no_results' => 'No suppliers found.',

    'status' => [
        'pending' => 'Pending Review',
        'verified' => 'Verified',
        'suspended' => 'Suspended',
    ],

    'fields' => [
        'company_name_ar' => 'Company Name (Arabic)',
        'company_name_en' => 'Company Name (English)',
        'commercial_reg' => 'Commercial Registration',
        'tax_number' => 'Tax Number',
        'phone' => 'Phone',
        'city' => 'City',
        'district' => 'District',
        'address' => 'Address',
        'description_ar' => 'Description (Arabic)',
        'description_en' => 'Description (English)',
        'logo' => 'Logo (URL)',
        'website' => 'Website',
        'verification_status' => 'Verification Status',
    ],

    'placeholders' => [
        'company_name_ar' => 'Arabic trade name',
        'company_name_en' => 'Company name in English',
    ],

    'validation' => [
        'phone_format' => 'Phone must start with 05 and be exactly 10 digits.',
        'user_id_required_for_admin' => 'A user_id (contractor) must be specified when creating as admin.',
    ],
];
