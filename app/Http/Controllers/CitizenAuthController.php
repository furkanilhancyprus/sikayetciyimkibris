<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class CitizenAuthController extends Controller
{
    public function loginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'E-posta veya şifre hatalı.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function registerForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:60'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $emailHash = hash('sha256', mb_strtolower($validated['email']));

        if (User::query()->where('email_hash', $emailHash)->exists()) {
            return back()
                ->withErrors(['email' => 'Bu e-posta adresiyle kayıtlı bir hesap var.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $user = User::query()->create($validated);

        Role::findOrCreate('verified_user');
        Role::findOrCreate('reporter');
        $user->assignRole(['verified_user', 'reporter']);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
