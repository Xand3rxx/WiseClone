<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReconciliationRun extends Model
{
    protected $fillable = [
        'status',
        'accounts_checked',
        'mismatches_found',
        'summary',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ReconciliationRun $run): void {
            $run->uuid = (string) Str::uuid();
        });
    }
}
