<?php

return [
    'regular' => 'Rental',
    'hourly' => 'Hourly rental',
    'regular_hint' => 'Regular (monthly) rental worker — shown in rental contracts and orders',
    'hourly_hint' => 'Hourly rental worker — hidden from regular rental contracts, orders and the website',
    'filter_label' => 'Rental type',
    'all' => 'All',

    'actions' => [
        'to_hourly' => 'Convert to hourly rental',
        'to_regular' => 'Convert to regular rental',
        'hourly_profile' => 'Hourly worker profile',
    ],
    'confirm' => [
        'to_hourly_title' => 'Convert the worker to hourly rental?',
        'to_hourly_text' => ':name will be hidden from regular rental contracts, orders and the website, and will appear under hourly workers. Then finish her team and services setup from her profile.',
        'to_regular_title' => 'Convert the worker to regular rental?',
        'to_regular_text' => ':name will leave her hourly teams and her app account will be closed. She becomes available for regular rental contracts and orders.',
        'yes' => 'Yes, convert',
        'cancel' => 'Cancel',
    ],
    'success' => [
        'to_hourly' => ':name was converted to hourly rental.',
        'to_regular' => ':name was converted to regular rental.',
    ],
    'errors' => [
        'hourly_disabled' => 'The hourly module is disabled.',
        'already_hourly' => 'The worker is already an hourly worker.',
        'already_regular' => 'The worker is already a regular rental worker.',
        'active_rental' => 'Cannot convert: the worker has an active rental contract.',
        'not_inside_shelter' => 'Cannot convert: the worker is not inside the shelter.',
        'not_ready' => 'Cannot convert: the worker is not ready for rental (work desire not confirmed or her status blocks rental).',
        'open_visits' => 'Cannot convert: the worker has :count open or upcoming hourly visit(s). Reassign them first.',
        'generic' => 'The conversion could not be completed.',
        'hourly_not_orderable' => 'This worker is dedicated to hourly rental and cannot be ordered or assigned as a regular rental.',
    ],

    'presence' => [
        'title' => 'Status now',
        'in_visit' => 'On a visit now',
        'available_in_shelter' => 'Available in shelter',
        'in_shelter_not_ready' => 'In shelter — not ready',
        'outside_shelter' => 'Outside shelter',
        'suspended' => 'Suspended',
        'visit_no' => 'Visit #:number',
        'order_no' => 'Order :number',
        'until' => 'until :time',
        'next_visit' => 'Next: :time',
        'upcoming' => '{0} No upcoming visits|{1} 1 upcoming visit|[2,*] :count upcoming visits',
        'filter_in_visit' => 'On a visit now',
        'filter_available' => 'Available in shelter',
    ],

    'shelter' => [
        'rental_split' => 'Rental workers split',
        'regular_count' => 'Regular rental',
        'hourly_count' => 'Hourly rental',
    ],
];
