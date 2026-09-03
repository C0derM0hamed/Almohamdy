<?php

namespace Tests\Unit;

use App\Support\Licenses\BusinessDayCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class LicenseBusinessDayCalculatorTest extends TestCase
{
    public function test_ksa_weekend_is_skipped_when_calculating_undertaking_deadline(): void
    {
        $calculator = new BusinessDayCalculator;

        $deadline = $calculator->add(CarbonImmutable::parse('2026-08-27 09:00:00'), 3);

        $this->assertSame('2026-09-01 09:00:00', $deadline->format('Y-m-d H:i:s'));
        $this->assertFalse($calculator->isOverdue('2026-08-27 09:00:00', 3, '2026-09-01 18:00:00'));
        $this->assertTrue($calculator->isOverdue('2026-08-27 09:00:00', 3, '2026-09-02 00:00:00'));
    }
}
