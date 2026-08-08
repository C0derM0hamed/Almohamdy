<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardAnalyticsService;
use App\Services\Dashboard\DashboardCardService;
use App\Services\Dashboard\NavigationService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardAnalyticsService $analytics,
        private readonly DashboardCardService $cards,
        private readonly NavigationService $navigation,
    ) {}

    public function index(): View
    {
        return view('dashboard.home', [
            'analytics' => $this->analytics->overview(),
            'cards' => $this->cards->resolve(),
            'userName' => $this->navigation->userDisplayName(),
        ]);
    }
}
