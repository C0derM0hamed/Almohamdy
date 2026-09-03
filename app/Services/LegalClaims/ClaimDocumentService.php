<?php

namespace App\Services\LegalClaims;

use Illuminate\Http\UploadedFile;

/** مسؤول عن المستندات العامة والمستندات المرفوعة مع القضية. */
class ClaimDocumentService
{
    public function __construct(private readonly LegalClaimService $claims) {}

    public function attach(int $claim, UploadedFile $file): void
    {
        $this->claims->addAttachment($claim, $file);
    }
}
