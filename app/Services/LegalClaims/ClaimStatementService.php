<?php

namespace App\Services\LegalClaims;

use Illuminate\Http\UploadedFile;

/** مسؤول عن إنشاء طلبات الإفادة ومرفقاتها ضمن القضية المخولة فقط. */
class ClaimStatementService
{
    public function __construct(private readonly LegalClaimService $claims) {}

    public function create(int $claim, string $details, ?UploadedFile $file): void
    {
        $this->claims->addStatement($claim, $details, $file);
    }
}
