<?php

namespace App\Support;

class CaseBilling
{
    public const CURRENCIES = [
        'MYR' => 'Malaysian ringgit',
        'GBP' => 'British pound',
        'USD' => 'US dollar',
        'EUR' => 'Euro',
        'SGD' => 'Singapore dollar',
        'AUD' => 'Australian dollar',
        'CAD' => 'Canadian dollar',
        'NZD' => 'New Zealand dollar',
        'HKD' => 'Hong Kong dollar',
        'AED' => 'UAE dirham',
    ];

    public static function parseRate(string $rate): int
    {
        [$whole, $fraction] = array_pad(explode('.', $rate, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function amount(int $minutes, int $hourlyRateMinor): int
    {
        return intdiv($minutes * $hourlyRateMinor + 30, 60);
    }

    public static function decimal(int $minor, bool $groupThousands = false): string
    {
        $whole = (string) intdiv($minor, 100);

        if ($groupThousands) {
            $whole = preg_replace('/\B(?=(?:\d{3})+(?!\d))/', ',', $whole);
        }

        return $whole.'.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function money(string $currency, int $minor): string
    {
        return $currency.' '.self::decimal($minor, groupThousands: true);
    }

    public static function duration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.'m';
        }

        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        return $hours.'h'.($remainder ? ' '.$remainder.'m' : '');
    }
}
