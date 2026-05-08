<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\Programme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgrammeController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-ganti-go');

        $search = (string) str($request->query('q', ''))->trim();

        return view('ganti-go.programmes.index', [
            'programmes' => Programme::query()
                ->withCount('classes')
                ->when($search !== '', fn ($query) => $query->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                ->orderBy('code')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-ganti-go');

        return view('ganti-go.programmes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        Programme::create($this->validatedData($request));

        return redirect()->route('ganti-go.programmes.index')->with('status', 'Programme has been created.');
    }

    public function edit(Programme $programme): View
    {
        Gate::authorize('manage-ganti-go');

        return view('ganti-go.programmes.edit', [
            'programme' => $programme,
        ]);
    }

    public function update(Request $request, Programme $programme): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        $programme->update($this->validatedData($request, $programme));

        return redirect()->route('ganti-go.programmes.index')->with('status', 'Programme has been updated.');
    }

    public function toggle(Programme $programme): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        $programme->forceFill(['is_active' => ! $programme->is_active])->save();

        return back()->with('status', $programme->is_active ? 'Programme has been enabled.' : 'Programme has been disabled.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Programme $programme = null): array
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $codeRule = Rule::unique('programmes', 'code');

        if ($programme) {
            $codeRule->ignore($programme);
        }

        return [
            ...$request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    $codeRule,
                ],
                'name' => ['required', 'string', 'max:255'],
                'is_active' => ['nullable', 'boolean'],
            ]),
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
