<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class APIPasswordResetTokenModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token_type',
        'token_signature',
        'expires_at',
    ];

    protected $table = "password_resets";
}
