<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function arabic(): RedirectResponse
    {
        session(['locale' => 'ar']);

        return redirect()->back();
    }

    public function english(): RedirectResponse
    {
        session(['locale' => 'en']);

        return redirect()->back();
    }
}
