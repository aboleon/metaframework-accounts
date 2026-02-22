<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public function names(): string
    {
        return trim((string) $this->first_name . ' ' . (string) $this->last_name);
    }
}
