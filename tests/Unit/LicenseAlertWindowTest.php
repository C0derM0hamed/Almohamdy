<?php

namespace Tests\Unit;

use App\Support\Licenses\LicenseAlertWindow;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class LicenseAlertWindowTest extends TestCase
{
    public function test_alert_windows_include_boundaries_and_expiry(): void
    {
        $calculator = new LicenseAlertWindow;
        $today = CarbonImmutable::parse('2026-08-30 12:00:00');

        $this->assertSame(LicenseAlertWindow::GREEN, $calculator->for('2026-11-29', $today, [90, 60, 30]));
        $this->assertSame(LicenseAlertWindow::YELLOW, $calculator->for('2026-11-28', $today, [90, 60, 30]));
        $this->assertSame(LicenseAlertWindow::SIXTY_DAYS, $calculator->for('2026-10-29', $today, [90, 60, 30]));
        $this->assertSame(LicenseAlertWindow::RED, $calculator->for('2026-09-29', $today, [90, 60, 30]));
        $this->assertSame(LicenseAlertWindow::RED, $calculator->for('2026-08-30', $today, [90, 60, 30]));
        $this->assertSame(LicenseAlertWindow::EXPIRED, $calculator->for('2026-08-29', $today, [90, 60, 30]));
    }

    public function test_each_non_green_window_has_a_stable_notification_event(): void
    {
        $calculator = new LicenseAlertWindow;

        $this->assertNull($calculator->eventType(LicenseAlertWindow::GREEN));
        $this->assertSame('expiry_90_days', $calculator->eventType(LicenseAlertWindow::YELLOW));
        $this->assertSame('expiry_60_days', $calculator->eventType(LicenseAlertWindow::SIXTY_DAYS));
        $this->assertSame('expiry_30_days', $calculator->eventType(LicenseAlertWindow::RED));
        $this->assertSame('license_expired', $calculator->eventType(LicenseAlertWindow::EXPIRED));
    }
}
