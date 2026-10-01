<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    protected $fillable = ['email', 'otp_code', 'expires_at'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }
}