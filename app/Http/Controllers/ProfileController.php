<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\Auth\TwoFactorAuthenticationService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request, TwoFactorAuthenticationService $twoFactor): Response
    {
        $user = $request->user();

        // In-progress TOTP enrollment: render the provisioning QR from the
        // secret held pending in the session (never persisted unconfirmed).
        $pendingSecret = $request->session()->get('mfa.pending_secret');
        $pendingMethod = $request->session()->get('mfa.pending_method');

        $twoFactorSetup = null;
        if ($pendingMethod === 'totp' && $pendingSecret) {
            $twoFactorSetup = [
                'method' => 'totp',
                'secret' => $pendingSecret,
                'qr' => $twoFactor->qrCodeSvg($user, $pendingSecret),
            ];
        } elseif ($pendingMethod === 'email') {
            $twoFactorSetup = ['method' => 'email'];
        }

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'twoFactorEnabled' => $user->hasTwoFactorEnabled(),
            'twoFactorMethod' => $user->two_factor_method,
            'twoFactorSetup' => $twoFactorSetup,
            'mfaRecoveryCodes' => session('mfaRecoveryCodes'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
