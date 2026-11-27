<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    public const DEFAULT_SUPER_ADMIN_EMAIL = 'legaldesk@admin.test';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected $casts = [
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'is_super_admin' => 'boolean',
        'has_access' => 'boolean',
        'demo_expires_at' => 'datetime',
        'demo_expired_at' => 'datetime',
    ];

    public function demoAccessExpired(): bool
    {
        return $this->demo_expires_at !== null
            && ($this->demo_expired_at !== null || $this->demo_expires_at->lte(now()));
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
