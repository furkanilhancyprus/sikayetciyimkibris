<?php

namespace App\Http\Controllers;

use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class OrganizationInvitationController extends Controller
{
    public function show(string $token): View
    {
        $invitation = OrganizationInvitation::query()
            ->with('entity')
            ->where('token', $token)
            ->firstOrFail();

        abort_unless($invitation->isUsable(), 410);

        return view('auth.organization-invite', [
            'invitation' => $invitation,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $invitation = OrganizationInvitation::query()
            ->with('entity')
            ->where('token', $token)
            ->firstOrFail();

        abort_unless($invitation->isUsable(), 410);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        $emailHash = hash('sha256', mb_strtolower($validated['email']));

        if ($emailHash !== $invitation->invited_email_hash) {
            return back()
                ->withErrors(['email' => 'Bu davet bağlantısı bu e-posta adresi için oluşturulmadı.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        if (User::query()->where('email_hash', $emailHash)->exists()) {
            return back()
                ->withErrors(['email' => 'Bu e-posta adresiyle kayıtlı bir hesap var.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'entity_id' => $invitation->entity_id,
            'password' => $validated['password'],
            'email_verified_at' => now(),
        ]);

        Role::findOrCreate('organization');
        $user->assignRole('organization');

        $invitation->forceFill([
            'accepted_at' => now(),
            'accepted_user_id' => $user->id,
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('organization-portal.dashboard');
    }
}
