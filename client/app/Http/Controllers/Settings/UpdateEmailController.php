<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateMissingEmailRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UpdateEmailController extends Controller
{
    /**
     * Show the update email page.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('settings/update-email', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's email address.
     */
    public function update(UpdateMissingEmailRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        $user->email = $request->validated()['email'];
        $user->email_verified_at = null;
        $user->save();

        // Trimite email de verificare
        $user->sendEmailVerificationNotification();

        return redirect()->route('dashboard')->with('status', 'email-updated');
    }
}
