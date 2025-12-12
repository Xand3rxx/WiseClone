<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const ROLES = [
        'admin' => 'administrator',
        'customer' => 'customer',
    ];

    protected $fillable = [
        'name',
        'url',
    ];

    /**
     * Get all users with this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if this is the administrator role.
     */
    public function isAdmin(): bool
    {
        return $this->name === self::ROLES['admin'];
    }

    /**
     * Check if this is the customer role.
     */
    public function isCustomer(): bool
    {
        return $this->name === self::ROLES['customer'];
    }
}
