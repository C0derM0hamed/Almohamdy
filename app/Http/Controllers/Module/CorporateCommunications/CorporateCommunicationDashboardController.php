<?php

namespace App\Http\Controllers\Module\CorporateCommunications;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class CorporateCommunicationDashboardController extends Controller
{
    /**
     * Hub cards were replaced by sidebar sub-items; keep the route as a redirect.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('modules.government-circulars.index');
    }
}
