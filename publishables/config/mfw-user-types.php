<?php

declare(strict_types=1);

use MetaFramework\Accounts\Enum\UserType;

return [
    /*
    |--------------------------------------------------------------------------
    | Core User Type Segregation
    |--------------------------------------------------------------------------
    |
    | Installed by metaframework-accounts. Defines the two supported user
    | domains used on a shared users table: system and account.
    |
    */
    'enabled' => true,

    // Column used to discriminate user domains inside the users table.
    'column' => 'type',

    // Supported type keys.
    'values' => [
        UserType::SYSTEM->value,
        UserType::ACCOUNT->value,
        UserType::COMPANY->value,
        UserType::AGENT->value,
    ],

    // Fallback type when no explicit type is provided.
    'default' => UserType::default(),

    // Guard -> type mapping used by UserTypes::addToCredentials().
    'guards' => [
        'web' => UserType::SYSTEM->value,
        'account' => [
            UserType::ACCOUNT->value,
            UserType::COMPANY->value,
            UserType::AGENT->value,
        ],
    ],
];
