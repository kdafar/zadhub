<?php

return [
    'flows' => [
        'allowed_categories' => [
            'SIGN_UP', 'SIGN_IN', 'APPOINTMENT_BOOKING', 'LEAD_GENERATION',
            'CONTACT_US', 'CUSTOMER_SUPPORT', 'SURVEY', 'OTHER',
        ],
        'category_aliases' => [
            // your domain → Meta enum
            'FOOD_DELIVERY' => 'OTHER',
            'RESTAURANT' => 'OTHER',
            'ORDERING' => 'OTHER',
            'REORDER' => 'OTHER',
            'CHECKOUT' => 'OTHER',
            'CONTACT' => 'CONTACT_US',
            'SUPPORT' => 'CUSTOMER_SUPPORT',
            'HELP' => 'CUSTOMER_SUPPORT',
            'RATING' => 'SURVEY',
            'FEEDBACK' => 'SURVEY',
            'APPOINTMENT' => 'APPOINTMENT_BOOKING',
            'BOOKING' => 'APPOINTMENT_BOOKING',
            'LOGIN' => 'SIGN_IN',
            'SIGNUP' => 'SIGN_UP',
            'UTILITY' => 'OTHER',
            'MARKETING' => 'LEAD_GENERATION',
        ],
        'default_categories' => ['OTHER'],
    ],
];
