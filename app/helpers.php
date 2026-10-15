<?php

if (! function_exists('format_trimmed_number')) {
    /**
     * Format a float for display, trimming trailing zeros.
     */
    function format_trimmed_number(float|int|string $amount, int $decimals = 8): string
    {
        $formatted = rtrim(rtrim(number_format((float) $amount, $decimals, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
