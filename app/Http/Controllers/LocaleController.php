<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'language' => ['required', Rule::in(['en', 'ms'])],
        ]);

        $request->session()->put('locale', $validated['language']);

        if ($request->user()) {
            $request->user()->forceFill([
                'language_preference' => $validated['language'],
            ])->save();
        }

        app()->setLocale($validated['language']);

        return back()
            ->with('status', __('app.language.updated'))
            ->with('notification', __('app.language.updated'));
    }
}
