<?php

namespace App\Http\Controllers\Module;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesDashboardView;
use Illuminate\View\View;

class ModulePlaceholderController extends Controller
{
    use ResolvesDashboardView;

    public function show(string $moduleKey): View
    {
        $allowed = collect(config('hm.dashboard.cards', []))
            ->pluck('label_key')
            ->merge(collect(config('hm.employee_services.cards', []))->pluck('label_key'))
            ->unique()
            ->values()
            ->all();

        abort_unless(in_array($moduleKey, $allowed, true), 404);

        $pageTitleKey = \Illuminate\Support\Facades\Lang::has('employee_services.cards.'.$moduleKey)
            ? 'employee_services.cards.'.$moduleKey
            : 'dashboard.cards.'.$moduleKey;

        return view('modules.placeholder', [
            'userName' => $this->userDisplayName(),
            'pageTitle' => __($pageTitleKey),
            'moduleKey' => $moduleKey,
            'homeRoute' => $this->homeRouteName(),
        ]);
    }
}
