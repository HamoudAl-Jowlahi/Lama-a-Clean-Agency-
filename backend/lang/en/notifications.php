<?php

return [
    'view' => 'View',
    'booking' => [
        'created' => [
            'admins' => ['title' => 'New visit :number', 'body' => 'On :date at :time — awaiting review.'],
        ],
        'confirmed' => [
            'customer' => ['title' => 'Your booking is confirmed', 'body' => 'Visit :number on :date at :time is confirmed. We will notify you when a team is assigned.'],
        ],
        'rejected' => [
            'customer' => ['title' => 'We could not accept your booking', 'body' => 'Sorry, booking :number was not accepted. :reason'],
        ],
        'assigned' => [
            'customer' => ['title' => 'A team has been assigned', 'body' => ':team will handle your visit on :date at :time.'],
            'staff' => ['title' => 'New visit for your team', 'body' => 'Booking :number on :date at :time — waiting for the leader to accept.'],
        ],
        'unassigned' => [
            'staff' => ['title' => 'Visit unassigned', 'body' => 'Booking :number is no longer assigned to your team.'],
        ],
        'declined' => [
            'admins' => ['title' => 'A team declined a visit', 'body' => ':team declined booking :number — needs reassignment.'],
        ],
        'on_the_way' => [
            'customer' => ['title' => 'The team is on the way', 'body' => 'The cleaning team is heading to booking :number.'],
        ],
        'in_progress' => [
            'customer' => ['title' => 'Cleaning has started', 'body' => 'The team started working on booking :number.'],
        ],
        'completed' => [
            'customer' => ['title' => 'Visit completed', 'body' => 'Amount due in cash: :amount. We would love your rating.'],
        ],
        'cancelled' => [
            'customer' => ['title' => 'Your booking was cancelled', 'body' => 'Booking :number was cancelled. :reason'],
            'staff' => ['title' => 'Visit cancelled', 'body' => 'Booking :number on :date at :time was cancelled.'],
            'admins' => ['title' => 'A customer cancelled', 'body' => 'Booking :number on :date at :time.'],
        ],
    ],
    'contract' => [
        'created' => [
            'admins' => ['title' => 'New contract request :number', 'body' => 'Starts :date — awaiting review.'],
        ],
        'confirmed' => [
            'customer' => ['title' => 'Your contract is confirmed', 'body' => 'Contract :number is confirmed. We will notify you when a housekeeper is assigned.'],
        ],
        'rejected' => [
            'customer' => ['title' => 'We could not accept your contract', 'body' => 'Sorry, contract :number was not accepted. :reason'],
        ],
        'assigned' => [
            'customer' => ['title' => 'Housekeeper assigned', 'body' => ':worker will start on :date.'],
            'staff' => ['title' => 'New contract', 'body' => 'You are assigned to contract :number starting :date.'],
        ],
        'active' => [
            'customer' => ['title' => 'Your contract has started', 'body' => 'Contract :number is active until :end_date.'],
            'staff' => ['title' => 'Contract started', 'body' => 'Contract :number starts today. Details are in the app.'],
        ],
        'completed' => [
            'customer' => ['title' => 'Your contract has ended', 'body' => 'Contract :number has ended. We would love your rating.'],
            'staff' => ['title' => 'Contract ended', 'body' => 'Your period in contract :number has ended.'],
        ],
        'terminated' => [
            'customer' => ['title' => 'Contract terminated', 'body' => 'Contract :number was terminated. The amount is based on actual days.'],
            'staff' => ['title' => 'Contract terminated', 'body' => 'Contract :number was terminated early.'],
        ],
        'cancelled' => [
            'customer' => ['title' => 'Contract cancelled', 'body' => 'Contract :number was cancelled. :reason'],
            'staff' => ['title' => 'Contract cancelled', 'body' => 'Contract :number was cancelled before it started.'],
            'admins' => ['title' => 'A customer cancelled a contract', 'body' => 'Contract :number was due to start :date.'],
        ],
        'ending_soon' => [
            'customer' => ['title' => 'Your contract ends soon', 'body' => 'Contract :number ends on :end_date. You can request a new one in the app.'],
            'admins' => ['title' => 'Contract ending soon', 'body' => 'Contract :number ends :end_date.'],
        ],
        'worker_changed' => [
            'customer' => ['title' => 'New housekeeper assigned', 'body' => ':worker will start on :start_on.'],
            'staff' => ['title' => 'New assignment', 'body' => 'You are assigned to contract :number starting :start_on.'],
        ],
        'worker_released' => [
            'staff' => ['title' => 'Your period has ended', 'body' => 'You are no longer assigned to contract :number. Thank you.'],
        ],
    ],
    'change_request' => [
        'submitted' => [
            'admins' => ['title' => ':request_type request — :request', 'body' => 'Contract :number · Reason: :reason'],
        ],
        'rejected' => [
            'customer' => ['title' => 'Your request was reviewed', 'body' => 'Your :request_type request (:request) was not approved. :response'],
        ],
    ],
    'complaint' => [
        'created' => [
            'admins' => ['title' => 'New complaint :number', 'body' => 'Type: :type'],
        ],
        'customer_message' => [
            'admins' => ['title' => 'New customer reply', 'body' => 'On complaint :number'],
        ],
        'replied' => [
            'customer' => ['title' => 'New reply to your complaint', 'body' => 'Customer care replied to complaint :number.'],
        ],
        'status_changed' => [
            'customer' => ['title' => 'Complaint update', 'body' => 'Complaint :number is now: :status.'],
        ],
    ],
    'payment' => [
        'due' => [
            'customer' => ['title' => 'Payment due', 'body' => 'Cash due :amount for contract :number.'],
        ],
    ],
];
