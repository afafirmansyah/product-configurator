<?php

namespace App\Services\Configuration;

use App\Models\ConfigurationSession;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConfigurationNumberGenerator
{
    /**
     * Maximum number of attempts before giving up on resolving a unique
     * configuration number. Each retry re-derives the sequence under a lock.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Generate the next collision-safe configuration number for the current
     * year in the CFG-{year}-{6-pad} format.
     *
     * The sequence is derived under a row lock inside a transaction, and the
     * unique index on configuration_sessions.configuration_number acts as the
     * backstop: a unique-violation or already-taken candidate triggers a
     * bounded retry.
     */
    public function next(): string
    {
        $year = (int) date('Y');

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            try {
                $candidate = DB::transaction(function () use ($year): string {
                    $count = ConfigurationSession::withTrashed()
                        ->whereYear('created_at', $year)
                        ->lockForUpdate()
                        ->count();

                    return sprintf('CFG-%d-%06d', $year, $count + 1);
                });

                $isTaken = ConfigurationSession::withTrashed()
                    ->where('configuration_number', $candidate)
                    ->exists();

                if ($isTaken) {
                    continue;
                }

                return $candidate;
            } catch (QueryException) {
                continue;
            }
        }

        throw new RuntimeException('Unable to generate a unique configuration number after '.self::MAX_ATTEMPTS.' attempts.');
    }
}
