<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\ClassGroup;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\GantiGo\Models\Semester;
use App\Modules\GantiGo\Services\SemesterActivationService;
use App\Modules\GantiGo\Services\SemesterOfferingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ClassGroupController extends Controller
{
    public function index(Request $request, SemesterActivationService $semesterActivation): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Class groups are now managed from Academic Core.');
    }

    public function create(Request $request, SemesterActivationService $semesterActivation): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Create class groups from Academic Core.');
    }

    public function store(Request $request, SemesterOfferingService $offerings): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Class groups are now managed from Academic Core.');
    }

    public function edit(ClassGroup $classGroup): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Edit class groups from Academic Core.');
    }

    public function update(Request $request, ClassGroup $classGroup, SemesterOfferingService $offerings): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Class groups are now managed from Academic Core.');
    }

    public function toggle(ClassGroup $classGroup, SemesterOfferingService $offerings): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Class groups are now managed from Academic Core.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?ClassGroup $classGroup = null): array
    {
        $request->merge([
            'class_name' => strtoupper(trim((string) $request->input('class_name'))),
        ]);

        $classNameRule = Rule::unique('classes', 'class_name')
            ->where('programme_id', $request->integer('programme_id'))
            ->where('semester_id', $request->integer('semester_id'));

        if ($classGroup) {
            $classNameRule->ignore($classGroup);
        }

        $data = [
            ...$request->validate([
                'programme_id' => ['required', 'integer', 'exists:programmes,id'],
                'semester_id' => ['required', 'integer', 'exists:semesters,id'],
                'class_name' => [
                    'required',
                    'string',
                    'max:100',
                    $classNameRule,
                ],
                'is_active' => ['nullable', 'boolean'],
            ]),
            'is_active' => $request->boolean('is_active', true),
        ];

        $data['class_name'] = strtoupper(trim((string) $data['class_name']));

        if (Semester::query()->find((int) $data['semester_id'])?->isArchived()) {
            throw ValidationException::withMessages([
                'semester_id' => 'Past semesters are read-only.',
            ]);
        }

        if ($classGroup && (int) $classGroup->semester_id !== (int) $data['semester_id']) {
            throw ValidationException::withMessages([
                'semester_id' => 'Existing class group offerings cannot be moved to another semester. Create a new offering instead.',
            ]);
        }

        return $data;
    }

    private function deprecatedRedirect(string $message): RedirectResponse
    {
        return redirect()->route('academic-core.class-groups.index')->with('status', $message);
    }
}
