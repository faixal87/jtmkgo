<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\GantiGoSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GantiGoSettingController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('manage-ganti-go');

        return view('ganti-go.settings.edit', [
            'requireEvidenceUpload' => GantiGoSetting::bool('require_evidence_upload'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        $request->validate([
            'require_evidence_upload' => ['nullable', 'boolean'],
        ]);

        GantiGoSetting::put('require_evidence_upload', $request->boolean('require_evidence_upload'));

        return back()->with('status', 'Ganti Go settings have been updated.');
    }
}
