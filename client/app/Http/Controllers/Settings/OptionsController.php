<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Requests\Settings\OptionsUpdateRequest;
use Illuminate\Support\Facades\Log;

class OptionsController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/options');
    }

    /**
     * Update the user's profile settings.
     */
    public function update(OptionsUpdateRequest $request): RedirectResponse
    {
        Log::debug("Updating user options", [
            'user_id' => $request->user()->id,
            'validated_data' => $request->validated(),
        ]);
        $request->user()->fill($request->validated());

        $request->user()->save();

        return to_route('options.edit');
    }
}
