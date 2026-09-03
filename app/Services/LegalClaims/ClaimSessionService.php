<?php

namespace App\Services\LegalClaims;

/** مسؤول عن تغيير الحالة ومحاضر الجلسات والملفات المرتبطة بها. */
class ClaimSessionService
{
    public function __construct(private readonly LegalClaimService $claims) {}

    public function record(int $claim, array $data, array $files): void
    {
        $this->claims->addAction($claim, $data, $files);
    }
}
