<?php

namespace App\Models;

use App\Services\Currency as CurrencyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $table = 'currencies';

    protected $fillable = [
        'name',
        'code',
        'symbol',
    ];

    /**
     * Get all users with this currency as default.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the flag details for this currency.
     */
    public function flag(): object
    {
        return (new CurrencyService)->flag((string) $this->id);
    }

    /**
     * Get formatted display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->symbol} {$this->name} ({$this->code})";
    }
}
