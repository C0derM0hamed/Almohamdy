<?php

namespace App\Http\Controllers\Module\GovAccounts;

use App\Http\Controllers\Controller;
use App\Services\GovAccounts\GovAccountDashboardService;
use Illuminate\View\View;

class GovAccountDashboardController extends Controller
{
    public function __construct(private readonly GovAccountDashboardService $dashboard) {}

    public function index(): View
    {
        return view('gov-accounts.dashboard', ['metrics' => $this->dashboard->metrics()]);
    }
}
