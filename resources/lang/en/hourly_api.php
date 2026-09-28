<?php

// Error messages for the hourly app APIs (api/app/hourly/*). See App\Exceptions\HourlyApiErrors.
return [
    'validation' => 'The submitted data is invalid.',
    'unauthenticated' => 'Please log in first. Send the token in the Authorization: Bearer header.',
    'forbidden' => 'You are not allowed to perform this action.',
    'not_found' => 'The requested item does not exist or is not available.',
    'route_not_found' => 'Route not found. Check the URL and the request method.',
    'method_not_allowed' => 'The :method method is not supported for this route. Supported methods: :allowed.',
    'conflict' => 'The request conflicts with existing data.',
    'too_many_requests' => 'Too many requests. Try again in :seconds seconds.',
    'service_unavailable' => 'The service is currently unavailable. Try again later.',
    'server_error' => 'An unexpected server error occurred. Try again, and contact support if it persists.',
    'bad_request' => 'The request is invalid.',

    'models' => [
        'HourlyPackage' => 'The package does not exist or is not available for booking.',
        'HourlyRentalOrder' => 'The order does not exist or does not belong to you.',
        'HourlyVisit' => 'The visit does not exist in this order.',
        'HourlyOrderPayment' => 'There is no payment for this order.',
        'HourlyServiceCategory' => 'The service category does not exist or is inactive.',
        'UserAddress' => 'The address does not exist or does not belong to you.',
        'User' => 'The user does not exist.',
        'Maid' => 'The worker does not exist.',
    ],

    'package_unavailable' => 'The package is not available for booking right now.',
    'city_unavailable' => 'The service is not available in this city.',
    'booking_token_taken' => 'The booking_token is already used by another order. Generate a new UUID for every checkout.',
    'booking_token_mismatch' => 'The booking_token was reused with different data. Generate a new one when the order data changes.',

    // Visit tracking: `state` carries the machine-readable status, these are its display strings.
    'tracking' => [
        'off' => 'Live tracking is currently disabled.',
        'not_now' => 'Tracking is available while the team is on its way to you and during pickup only.',
        'no_subject' => 'Nobody is assigned to the trip yet. The location appears once someone is.',
        'no_signal' => 'No location update has arrived yet. Try again shortly.',
    ],
];
