<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\NavigationService;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly NavigationService $navigation,
    ) {}

    public function index(): RedirectResponse
    {
        return redirect()->route($this->navigation->homeRouteName());
    }
}
