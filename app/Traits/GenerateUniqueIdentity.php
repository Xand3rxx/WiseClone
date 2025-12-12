<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait GenerateUniqueIdentity
{
    /**
     * Generate a unique reference number for a table.
     *
     * @param string|null $tableName The table name to check uniqueness against
     * @param int $stringLength The length of the reference string
     * @return string The unique reference
     */
    public static function generateReference(?string $tableName = null, int $stringLength = 13): string
    {
        return self::uniqueReference($tableName ?? 'transactions', $stringLength);
    }

    /**
     * Create a unique reference number.
     *
     * @param string $tableName The table name to check uniqueness against
     * @param int $stringLength The length of the reference string
     * @return string The unique reference
     */
    protected static function uniqueReference(string $tableName, int $stringLength): string
    {
        $tested = [];
        $maxAttempts = 100;
        $attempts = 0;

        do {
            $attempts++;

            // Safety check to prevent infinite loops
            if ($attempts > $maxAttempts) {
                throw new \RuntimeException("Unable to generate unique reference after {$maxAttempts} attempts");
            }

            // Generate random string with timestamp prefix for better uniqueness
            $random = strtoupper(Str::random($stringLength));

            // Skip if already tested
            if (in_array($random, $tested, true)) {
                continue;
            }

            // Check if it exists in the database
            $exists = DB::table($tableName)
                ->where('reference', $random)
                ->exists();

            // Track tested values
            $tested[] = $random;

            // Return if unique
            if (!$exists) {
                return $random;
            }
        } while (true);
    }

    /**
     * Generate a UUID v4.
     *
     * @return string The UUID
     */
    public static function generateUuid(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Generate a prefixed reference (e.g., TXN-XXXXXXXXX).
     *
     * @param string $prefix The prefix to add
     * @param string|null $tableName The table name to check uniqueness against
     * @param int $stringLength The length of the random part
     * @return string The prefixed reference
     */
    public static function generatePrefixedReference(
        string $prefix,
        ?string $tableName = null,
        int $stringLength = 10
    ): string {
        $reference = self::uniqueReference($tableName ?? 'transactions', $stringLength);
        return strtoupper($prefix) . '-' . $reference;
    }
}
