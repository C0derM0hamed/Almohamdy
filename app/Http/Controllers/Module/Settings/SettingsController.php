<?php

namespace App\Http\Controllers\Module\Settings;

use App\Http\Controllers\Controller;
use App\Services\Auth\PermissionService;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly PermissionService $permissions) {}

    public function index(): View
    {
        return view('settings.index', [
            'isAdmin' => $this->permissions->isAdmin(),
            'homeRoute' => 'dashboard',
        ]);
    }
}
