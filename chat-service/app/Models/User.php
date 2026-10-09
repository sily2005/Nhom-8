<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $connection = 'mysql_auth';
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'is_active',
    ];

    protected $appends = [
        'phone_number',
    ];

    public function getPhoneNumberAttribute(): ?string
    {
        return $this->attributes['phone'] ?? null;
    }
}
