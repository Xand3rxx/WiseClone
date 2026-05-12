<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditExport extends Model
{
    protected $fillable = [
        'type',
        'status',
        'path',
        'checksum',
        'metadata',
        'exported_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'exported_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AuditExport $export): void {
            $export->uuid = (string) Str::uuid();
        });
    }
}
