<?php

namespace App\Helpers;

class BandwidthFormatter
{
    /**
     * Create a new class instance.
     * Format bandwidth dari bps ke Kbps atau Mbps dengan 2 desimal
     */
    public static function format(float $bps): string
    {
        if ($bps >= 1_000_000) {
            return number_format($bps / 1_000_000, 2) . ' Mbps';
        }

        if ($bps >= 1_000) {
            return number_format($bps / 1_000, 2) . ' Kbps';
        }

        return number_format($bps, 0) . ' bps';
    }
}
