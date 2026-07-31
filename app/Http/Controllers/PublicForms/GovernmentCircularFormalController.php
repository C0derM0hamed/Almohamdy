<?php

namespace App\Http\Controllers\PublicForms;

use App\Http\Controllers\Controller;
use App\Services\GovernmentCirculars\GovernmentCircularService;
use Illuminate\View\View;
use InvalidArgumentException;

class GovernmentCircularFormalController extends Controller
{
    public function __construct(
        private readonly GovernmentCircularService $circulars,
    ) {}

    public function show(string $token): View
    {
        try {
            [$circular, $parsed, $attachmentUrls, $sectionNames] = $this->circulars->openFormalPage($token);
        } catch (InvalidArgumentException $exception) {
            return view('public.government-circulars.formal-result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        return view('public.government-circulars.formal', [
            'circular' => $circular,
            'token' => $token,
            'administratorId' => $parsed->administratorId,
            'channel' => $parsed->channel,
            'attachmentUrls' => $attachmentUrls,
            'sectionNames' => $sectionNames,
        ]);
    }
}
