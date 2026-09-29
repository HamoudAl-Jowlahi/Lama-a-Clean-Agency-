<?php

return [
    'user_role' => ['customer' => 'Customer', 'worker' => 'Worker'],
    'user_status' => ['active' => 'Active', 'suspended' => 'Suspended'],
    'worker_status' => ['active' => 'Active', 'inactive' => 'Inactive', 'on_leave' => 'On leave'],
    'worker_type' => ['cleaner' => 'Visit team', 'housekeeper' => 'Housekeeper (contracts)'],
    'admin_role' => ['super_admin' => 'Super admin', 'operations' => 'Operations', 'support' => 'Support'],

    'booking_status' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'assigned' => 'Assigned',
        'on_the_way' => 'On the way',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'rejected' => 'Rejected',
    ],
    'assignment_status' => ['pending' => 'Pending', 'accepted' => 'Accepted', 'rejected' => 'Rejected', 'withdrawn' => 'Withdrawn'],

    'contract_status' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'assigned' => 'Assigned',
        'active' => 'Active',
        'completed' => 'Completed',
        'terminated' => 'Terminated early',
        'cancelled' => 'Cancelled',
        'rejected' => 'Rejected',
    ],
    'assignment_end_reason' => [
        'replaced' => 'Replaced',
        'contract_completed' => 'Contract completed',
        'terminated' => 'Contract terminated',
        'worker_unavailable' => 'Worker unavailable',
    ],
    'change_request_type' => ['replace_worker' => 'Replace worker', 'terminate' => 'Terminate contract'],
    'change_request_status' => ['open' => 'Open', 'under_review' => 'Under review', 'approved' => 'Approved', 'rejected' => 'Rejected'],

    'payment_method' => ['cash' => 'Cash'],
    'payment_status' => ['due' => 'Due', 'collected' => 'Collected', 'waived' => 'Waived'],

    'complaint_status' => ['open' => 'Open', 'under_review' => 'Under review', 'resolved' => 'Resolved', 'closed' => 'Closed'],
    'complaint_message_kind' => ['message' => 'Message', 'status_change' => 'Status change', 'internal_note' => 'Internal note'],

    'actor_type' => ['customer' => 'Customer', 'worker' => 'Worker', 'admin' => 'Admin', 'system' => 'System'],
    'price_unit' => ['fixed' => 'Fixed', 'hour' => 'Per hour', 'piece' => 'Per piece', 'square_meter' => 'Per m²'],
];
