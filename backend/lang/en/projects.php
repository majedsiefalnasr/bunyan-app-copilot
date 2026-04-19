<?php

return [
    // Status labels
    'status' => [
        'draft' => 'Draft',
        'planning' => 'Planning',
        'in_progress' => 'In Progress',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
        'closed' => 'Closed',
    ],

    // Type labels
    'type' => [
        'residential' => 'Residential',
        'commercial' => 'Commercial',
        'infrastructure' => 'Infrastructure',
    ],

    // Phase status labels
    'phase_status' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
    ],

    // Validation messages
    'validation' => [
        'owner_required' => 'Owner ID is required',
        'owner_not_found' => 'The specified owner does not exist',
        'owner_must_be_customer' => 'Owner must be a Customer user',
        'name_ar_required' => 'Arabic project name is required',
        'name_ar_min' => 'Arabic project name must be at least 2 characters',
        'name_en_required' => 'English project name is required',
        'name_en_min' => 'English project name must be at least 2 characters',
        'city_required' => 'City is required',
        'type_required' => 'Project type is required',
        'budget_min' => 'Estimated budget must be greater than zero',
        'end_date_after_start' => 'End date must be after or equal to start date',
        'lat_range' => 'Latitude must be between -90 and 90',
        'lng_range' => 'Longitude must be between -180 and 180',
        'status_required' => 'New status is required',
        'expected_updated_at_required' => 'Expected updated_at timestamp is required',
        'expected_updated_at_date' => 'Expected updated_at must be a valid date',
        'closed_immutable' => 'Cannot modify a closed project',
        'phase_name_ar_required' => 'Arabic phase name is required',
        'phase_name_en_required' => 'English phase name is required',
        'sort_order_required' => 'Sort order is required',
        'sort_order_min' => 'Sort order must be zero or greater',
        'completion_range' => 'Completion percentage must be between 0 and 100',
        'phase_start_before_project' => 'Phase start date must be within the project date range',
        'phase_end_after_project' => 'Phase end date must be within the project date range',
    ],

    // Error messages
    'errors' => [
        'not_found' => 'Project not found',
        'closed_immutable' => 'Cannot modify a closed project',
        'invalid_transition' => 'Cannot transition to this status',
        'conflict' => 'Data conflict — the project may have been updated by another user',
    ],
];
