<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use MetaFramework\Polyglote\Interfaces\TranslatableInterface;

class User extends Authenticatable implements TranslatableInterface
{
    protected $table = 'users';

    protected $guarded = [];

    public function getTranslatableProperties(): array
    {
        return $this->setTranslatables();
    }

    public function setTranslatables(): array
    {
        return [
            'first_name' => [
                'label' => 'First name',
                'class' => 'col-md-6',
            ],
            'last_name' => [
                'label' => 'Last name',
                'class' => 'col-md-6',
            ],
        ];
    }

    public static function localizedColumn(string $column, ?string $locale = null): string
    {
        return $column;
    }

    public function names(): string
    {
        return trim((string) $this->first_name . ' ' . (string) $this->last_name);
    }
}
