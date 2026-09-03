<?php

namespace App\Http\Controllers\Module\GovAccounts;

use App\Http\Controllers\Controller;
use App\Services\GovAccounts\GovAccountNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GovAccountNotificationController extends Controller
{
    public function __construct(private readonly GovAccountNotificationService $notifications) {}

    public function index(): View
    {
        return view('gov-accounts.self.notifications', ['notifications' => $this->notifications->inbox()]);
    }

    public function read(int $notification): RedirectResponse
    {
        $this->notifications->markRead($notification);

        return back();
    }
}
