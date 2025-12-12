<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'currency_id',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The "booted" method of the model.
     * Create uuid when a new user is to be created
     */
    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            // Generate unique uuid for a new user.
            $user->uuid = (string) Str::uuid();

            // Default new user currency to USD if not set.
            if (empty($user->currency_id)) {
                $usdCurrency = Currency::where('code', 'USD')->first();
                $user->currency_id = $usdCurrency?->id ?? 1;
            }

            // Default role to customer if not set.
            if (empty($user->role_id)) {
                $customerRole = Role::where('name', 'customer')->first();
                $user->role_id = $customerRole?->id ?? 2;
            }
        });
    }

    /**
     * Get the full name of the user.
     */
    public function getFullNameAttribute(): string
    {
        return ucfirst($this->name);
    }

    /**
     * Get the Role associated with the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the transactions of the user.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->latest('created_at');
    }

    /**
     * Get the latest currency balance of the user.
     */
    public function latestCurrencyBalance(): HasOne
    {
        return $this->hasOne(CurrencyBalance::class)->latestOfMany();
    }

    /**
     * Get all currency balances of the user.
     */
    public function currencyBalances(): HasMany
    {
        return $this->hasMany(CurrencyBalance::class);
    }

    /**
     * Get the default currency of the user.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Check if user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role?->name === 'administrator';
    }

    /**
     * Check if user is a customer.
     */
    public function isCustomer(): bool
    {
        return $this->role?->name === 'customer';
    }
}
