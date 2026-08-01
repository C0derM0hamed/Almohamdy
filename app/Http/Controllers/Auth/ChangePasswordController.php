<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function __construct(private readonly UserRepository $users) {}

    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'same:password_confirmation'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $changed = $this->users->changeLegacyPassword(
            (int) $request->session()->get('hr_user_id'),
            $data['current_password'],
            $data['password'],
        );

        if (! $changed) {
            return back()->withInput()->withErrors(['current_password' => __('password_change.current_password_invalid')]);
        }

        return back()->with('status', __('password_change.updated'));
    }
}
