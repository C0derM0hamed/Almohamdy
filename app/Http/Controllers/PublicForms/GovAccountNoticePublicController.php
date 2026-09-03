<?php

namespace App\Http\Controllers\PublicForms;

use App\Http\Controllers\Controller;
use App\Services\GovAccounts\GovAccountNoticeService;
use Illuminate\View\View;

class GovAccountNoticePublicController extends Controller
{
    public function __construct(private readonly GovAccountNoticeService $notices) {}

    public function show(string $token): View
    {
        return view('gov-accounts.notices.public', ['recipient' => $this->notices->recordPublicView($token)]);
    }
}
