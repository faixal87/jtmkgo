<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\UserCsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserImportController extends Controller
{
    public function create(): View
    {
        return view('super-admin.users.import');
    }

    public function store(Request $request, UserCsvImportService $importer): RedirectResponse
    {
        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $summary = $importer->import($validated['csv_file'], $request->user()->id);

        return back()
            ->with('status', "Import completed. Created: {$summary['created']}, updated: {$summary['updated']}, skipped: {$summary['skipped']}.")
            ->with('import_summary', $summary);
    }
}
