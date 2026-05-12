<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IdempotencyKey;
use RuntimeException;

class IdempotencyService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function start(?int $userId, string $scope, string $key, array $payload): IdempotencyKey
    {
        $requestHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        $record = IdempotencyKey::query()
            ->where('user_id', $userId)
            ->where('scope', $scope)
            ->where('key', $key)
            ->first();

        if ($record) {
            if (! hash_equals($record->request_hash, $requestHash)) {
                throw new RuntimeException('This idempotency key was already used for a different request.');
            }

            return $record;
        }

        return IdempotencyKey::create([
            'user_id' => $userId,
            'scope' => $scope,
            'key' => $key,
            'request_hash' => $requestHash,
            'status' => IdempotencyKey::STATUS_PROCESSING,
            'locked_until' => now()->addMinutes(5),
        ]);
    }

    /**
     * @param  array<string, mixed>  $responsePayload
     */
    public function complete(IdempotencyKey $idempotencyKey, array $responsePayload): void
    {
        $idempotencyKey->forceFill([
            'status' => IdempotencyKey::STATUS_COMPLETED,
            'response_payload' => $responsePayload,
            'locked_until' => null,
        ])->save();
    }

    public function fail(IdempotencyKey $idempotencyKey, string $message): void
    {
        $idempotencyKey->forceFill([
            'status' => IdempotencyKey::STATUS_FAILED,
            'response_payload' => ['error' => $message],
            'locked_until' => null,
        ])->save();
    }
}
