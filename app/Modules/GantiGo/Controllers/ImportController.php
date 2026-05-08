<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Services\LegacyImportPreparationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(LegacyImportPreparationService $importPreparation): View
    {
        return view('ganti-go.import.index', [
            'targets' => $importPreparation->targets(),
            'preview' => null,
        ]);
    }

    public function preview(Request $request, LegacyImportPreparationService $importPreparation): View
    {
        $validated = $request->validate([
            'legacy_file' => ['nullable', 'file', 'mimes:xls,xlsx,csv,txt', 'max:10240'],
        ]);

        return view('ganti-go.import.index', [
            'targets' => $importPreparation->targets(),
            'preview' => $importPreparation->buildPreview($request->file('legacy_file')),
        ])->with('status', $validated ? 'Legacy import preview has been prepared.' : 'Legacy import structure is ready.');
    }
}
