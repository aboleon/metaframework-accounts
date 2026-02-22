<?php

declare(strict_types=1);

use MetaFramework\Accounts\Enum\DocTypeIncrementationEnum;

return [
    'doc_type_incrementation'       => [
        DocTypeIncrementationEnum::GENERIC->value       => 'Generic',
        DocTypeIncrementationEnum::OWN->value         => 'Own',
    ],
];
