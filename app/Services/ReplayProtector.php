<?php

namespace App\Services;

/**
 * Replay protection: enforces that the X-Webhook-Timestamp header lies within
 * the configured clock-skew window, blocking stale or replayed requests.
 */
class ReplayProtector
{
    public function __construct(
        private int $windowSeconds = 300,
    ) {
        //
    }

    public function isValid(string $timestamp): bool
    {
        if ($timestamp === '' || ! is_numeric($timestamp)) {
            return false;
        }

        $headerTime = (int) $timestamp;
        $now = time();

        // Reject timestamps in the future beyond the window (clock skew guard).
        if ($headerTime > $now + $this->windowSeconds) {
            return false;
        }

        // Reject timestamps older than the window (stale/replay).
        return ($now - $headerTime) <= $this->windowSeconds;
    }
}