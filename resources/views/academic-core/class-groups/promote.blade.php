<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Promote Class Groups</h2>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Generate next-semester class records while preserving the historical source rows.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" action="{{ route('academic-core.class-groups.promote') }}" class="enterprise-card grid gap-4 rounded-xl border p-5 shadow-sm md:grid-cols-2 xl:grid-cols-[1fr_1fr_auto] xl:items-end">
                <div>
                    <x-input-label for="source_academic_semester_id" value="Source Academic Semester" />
                    <select id="source_academic_semester_id" name="source_academic_semester_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" required>
                        <option value="">Select source semester</option>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->id }}" @selected((int) request('source_academic_semester_id') === $semester->id)>
                                {{ $semester->name }} ({{ $semester->academic_session }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="target_academic_semester_id" value="Target Academic Semester" />
                    <select id="target_academic_semester_id" name="target_academic_semester_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" required>
                        <option value="">Select target semester</option>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->id }}" @selected((int) request('target_academic_semester_id') === $semester->id)>
                                {{ $semester->name }} ({{ $semester->academic_session }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-primary-button>Preview Promotion</x-primary-button>
                    <a href="{{ route('academic-core.class-groups.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Back</a>
                </div>
            </form>

            @if ($sourceSemester && $targetSemester)
                <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-[var(--color-border)] pb-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Promotion Preview</p>
                            <h3 class="mt-2 text-lg font-semibold text-[var(--color-text)]">
                                {{ $sourceSemester->name }} ({{ $sourceSemester->academic_session }})
                                <span class="text-[var(--color-muted)]">to</span>
                                {{ $targetSemester->name }} ({{ $targetSemester->academic_session }})
                            </h3>
                        </div>
                        <span class="theme-badge">{{ $previewRows->count() }} row(s)</span>
                    </div>

                    @if ($previewRows->isEmpty())
                        <div class="mt-5">
                            <x-empty-state title="No source class groups found" message="The selected source semester has no class groups to promote." />
                        </div>
                    @else
                        <form method="POST" action="{{ route('academic-core.class-groups.promote.store') }}" class="mt-5 space-y-5">
                            @csrf
                            <input type="hidden" name="source_academic_semester_id" value="{{ $sourceSemester->id }}">
                            <input type="hidden" name="target_academic_semester_id" value="{{ $targetSemester->id }}">

                            <div class="space-y-4">
                                @foreach ($previewRows as $index => $row)
                                    <article class="rounded-xl border border-[var(--color-border)] p-4">
                                        <input type="hidden" name="rows[{{ $index }}][source_class_group_id]" value="{{ $row['source_class_group_id'] }}">
                                        <input type="hidden" name="rows[{{ $index }}][programme_id]" value="{{ $row['programme_id'] }}">
                                        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)]">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Source</p>
                                                <p class="mt-2 break-words text-sm font-semibold text-[var(--color-text)]">{{ $row['source_class_name'] }}</p>
                                                <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $row['programme_label'] }}</p>
                                            </div>
                                            <div>
                                                <x-input-label for="rows_{{ $index }}_class_name" value="Target Class Group" />
                                                <x-text-input id="rows_{{ $index }}_class_name" name="rows[{{ $index }}][class_name]" class="mt-1 block w-full" :value="old('rows.'.$index.'.class_name', $row['class_name'])" required />
                                            </div>
                                            <div>
                                                <x-input-label for="rows_{{ $index }}_current_semester" value="Semester Level" />
                                                <x-text-input id="rows_{{ $index }}_current_semester" name="rows[{{ $index }}][current_semester]" class="mt-1 block w-full" :value="old('rows.'.$index.'.current_semester', $row['current_semester'])" />
                                            </div>
                                            <div>
                                                <x-input-label for="rows_{{ $index }}_cohort" value="Cohort" />
                                                <x-text-input id="rows_{{ $index }}_cohort" name="rows[{{ $index }}][cohort]" class="mt-1 block w-full" :value="old('rows.'.$index.'.cohort', $row['cohort'])" />
                                            </div>
                                        </div>

                                        <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                                            <div>
                                                <x-input-label for="rows_{{ $index }}_academic_advisor_user_id" value="Academic Advisor" />
                                                <select id="rows_{{ $index }}_academic_advisor_user_id" name="rows[{{ $index }}][academic_advisor_user_id]" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                                    <option value="">Belum ditetapkan</option>
                                                    @foreach ($advisors as $advisor)
                                                        <option value="{{ $advisor->id }}" @selected((int) old('rows.'.$index.'.academic_advisor_user_id', $row['academic_advisor_user_id']) === $advisor->id)>
                                                            {{ $advisor->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <x-input-label for="rows_{{ $index }}_remarks" value="Remarks" />
                                                <x-text-input id="rows_{{ $index }}_remarks" name="rows[{{ $index }}][remarks]" class="mt-1 block w-full" :value="old('rows.'.$index.'.remarks', $row['remarks'])" placeholder="Optional notes for the promoted class group" />
                                            </div>
                                        </div>

                                        <label class="mt-4 inline-flex items-center gap-3 text-sm text-[var(--color-text)]">
                                            <input type="hidden" name="rows[{{ $index }}][is_active]" value="0">
                                            <input type="checkbox" name="rows[{{ $index }}][is_active]" value="1" class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" @checked(old('rows.'.$index.'.is_active', $row['is_active']))>
                                            Active after promotion
                                        </label>
                                    </article>
                                @endforeach
                            </div>

                            <x-input-error :messages="$errors->get('rows')" class="mt-2" />

                            <div class="flex flex-wrap gap-3">
                                <x-primary-button>Generate Next Semester Class Groups</x-primary-button>
                                <a href="{{ route('academic-core.class-groups.promote') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Start Over</a>
                            </div>
                        </form>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
