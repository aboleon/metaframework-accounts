<?php

return [
    'route_prefix' => 'mfw-accounts',

    'client' => [
        'default_civility' => 'A',
    ],

    // Host app policy for new invoices.
    'invoice' => [
        'default_currency_id' => 1,
    ],

    // Optional secondary/reporting currency conversion.
    // Keep source/rate null to disable conversion in a host app.
    'reporting_currency' => [
        'target_currency_id' => 1,
        'label' => 'EUR',
        'conversion' => [
            'source_currency_id' => null,
            'rate' => null,
        ],
    ],
];
