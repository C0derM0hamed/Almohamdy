<?php

namespace App\Support\Licenses;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class LicenseAlertWindow
{
    public const EXPIRED = 'expired';

    public const RED = 'red';

    public const SIXTY_DAYS = '60_days';

    public const YELLOW = 'yellow';

    public const GREEN = 'green';

    public function daysRemaining(CarbonInterface|string $expiryDate, CarbonInterface|string|null $at = null): int
    {
        $expiry = $expiryDate instanceof CarbonInterface
            ? CarbonImmutable::instance($expiryDate)
            : CarbonImmutable::parse($expiryDate);
        $today = $at instanceof CarbonInterface
            ? CarbonImmutable::instance($at)
            : CarbonImmutable::parse($at ?? now());

        return $today->startOfDay()->diffInDays($expiry->startOfDay(), false);
    }

    /** @param list<int>|null $thresholds */
    public function for(CarbonInterface|string $expiryDate, CarbonInterface|string|null $at = null, ?array $thresholds = null): string
    {
        $days = $this->daysRemaining($expiryDate, $at);
        $thresholds ??= (array) config('hm.licenses.alert_days', [90, 60, 30]);
        rsort($thresholds);
        [$yellow, $sixty, $red] = array_pad(array_values($thresholds), 3, 0);

        return match (true) {
            $days < 0 => self::EXPIRED,
            $days <= $red => self::RED,
            $days <= $sixty => self::SIXTY_DAYS,
            $days <= $yellow => self::YELLOW,
            default => self::GREEN,
        };
    }

    public function eventType(string $window): ?string
    {
        return match ($window) {
            self::YELLOW => 'expiry_90_days',
            self::SIXTY_DAYS => 'expiry_60_days',
            self::RED => 'expiry_30_days',
            self::EXPIRED => 'license_expired',
            default => null,
        };
    }
}
