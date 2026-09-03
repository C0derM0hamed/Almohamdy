<?php

namespace App\Services\LegalClaims;

use Illuminate\Http\UploadedFile;

/** مسؤول عن طلبات التنازل وجدول الأقساط وتأكيد السداد. */
class ClaimWaiverService
{
    public function __construct(private readonly LegalClaimService $claims) {}

    public function addInstallment(int $claim, string $date): void { $this->claims->addInstallment($claim, $date); }
    public function markPaid(int $claim, int $installment): void { $this->claims->markInstallmentPaid($claim, $installment); }
    public function requestWaiver(int $claim, array $data, ?UploadedFile $file): void { $this->claims->addSuspension($claim, $data, $file); }
}
