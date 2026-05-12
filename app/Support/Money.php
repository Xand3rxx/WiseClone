<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    private const INTERNAL_SCALE = 8;

    public static function normalizeUserInput(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        if (preg_match('/^\d+(\.\d+)?$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $value) === 1) {
            return str_replace(',', '', $value);
        }

        return $value;
    }

    public static function assertDecimal(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            $value = rtrim(rtrim(sprintf('%.'.self::INTERNAL_SCALE.'F', $value), '0'), '.');
        }

        if (! is_string($value) || preg_match('/^-?\d+(\.\d+)?$/', $value) !== 1) {
            throw new InvalidArgumentException('Invalid decimal money value.');
        }

        return $value;
    }

    public static function round(mixed $value, int $scale = 2): string
    {
        $value = self::assertDecimal($value);
        $roundingIncrement = '0.'.str_repeat('0', $scale).'5';

        return bcadd($value, $roundingIncrement, $scale);
    }

    public static function add(mixed $left, mixed $right, int $scale = 2): string
    {
        $result = bcadd(self::assertDecimal($left), self::assertDecimal($right), self::INTERNAL_SCALE);

        return self::round($result, $scale);
    }

    public static function subtract(mixed $left, mixed $right, int $scale = 2): string
    {
        $result = bcsub(self::assertDecimal($left), self::assertDecimal($right), self::INTERNAL_SCALE);

        return str_starts_with($result, '-') ? $result : self::round($result, $scale);
    }

    public static function percentage(mixed $amount, mixed $percentage, int $scale = 2): string
    {
        $raw = bcdiv(
            bcmul(self::assertDecimal($amount), self::assertDecimal($percentage), self::INTERNAL_SCALE),
            '100',
            self::INTERNAL_SCALE
        );

        return self::round($raw, $scale);
    }

    public static function multiplyByRate(mixed $amount, mixed $rate, int $scale = 2): string
    {
        $raw = bcmul(self::assertDecimal($amount), self::assertDecimal($rate), self::INTERNAL_SCALE);

        return self::round($raw, $scale);
    }

    public static function compare(mixed $left, mixed $right, int $scale = 2): int
    {
        return bccomp(self::assertDecimal($left), self::assertDecimal($right), $scale);
    }

    public static function isGreaterThan(mixed $left, mixed $right, int $scale = 2): bool
    {
        return self::compare($left, $right, $scale) === 1;
    }

    public static function isLessThan(mixed $left, mixed $right, int $scale = 2): bool
    {
        return self::compare($left, $right, $scale) === -1;
    }

    public static function maxZero(mixed $value, int $scale = 2): string
    {
        return self::isLessThan($value, '0', $scale) ? self::round('0', $scale) : self::round($value, $scale);
    }
}
