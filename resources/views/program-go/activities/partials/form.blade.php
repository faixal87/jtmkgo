@php
    $activity ??= new \App\Modules\ProgramGo\Models\ProgramActivity;
    $currentStatus = $activity->exists ? $activity->status : \App\Modules\ProgramGo\Models\ProgramActivity::STATUS_DRAFT;
    $statusHelp = match ($currentStatus) {
        \App\Modules\ProgramGo\Models\ProgramActivity::STATUS_IN_PROGRESS => 'Use In Progress for planned or ongoing activities.',
        \App\Modules\ProgramGo\Models\ProgramActivity::STATUS_COMPLETED => 'Submit for verification when the activity details and report are ready for admin review.',
        \App\Modules\ProgramGo\Models\ProgramActivity::STATUS_PENDING => 'This activity has been submitted for admin verification.',
        \App\Modules\ProgramGo\Models\ProgramActivity::STATUS_APPROVED => 'This activity has been approved and is locked.',
        \App\Modules\ProgramGo\Models\ProgramActivity::STATUS_RETURNED => 'Save your corrections or resubmit for admin verification.',
        \App\Modules\ProgramGo\Models\ProgramActivity::STATUS_REJECTED => 'This activity was rejected.',
        default => 'Save as draft if the activity details are still being prepared.',
    };
@endphp

<div
    class="space-y-6"
    x-data="{
        os21000: Number(@js(old('os_21000', $activity->os_21000 ?? 0))) || 0,
        os29000: Number(@js(old('os_29000', $activity->os_29000 ?? 0))) || 0,
        os42000: Number(@js(old('os_42000', $activity->os_42000 ?? 0))) || 0,
        hep: Number(@js(old('hep_allocation', $activity->hep_allocation ?? 0))) || 0,
        money(value) {
            return new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR' }).format(Number(value) || 0);
        },
        get subtotal() {
            return this.os21000 + this.os29000 + this.os42000;
        },
        get total() {
            return this.subtotal + this.hep;
        },
    }"
>
    <section class="enterprise-card rounded-xl border p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-[var(--color-text)]">A. Programme Information</h3>
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="reference_no" value="Reference Number" />
                <x-text-input id="reference_no" name="reference_no" class="mt-1 block w-full" :value="old('reference_no', $activity->reference_no)" placeholder="e.g. JTMK/PROG/2026/001" />
                <x-form-helper>Optional internal reference number.</x-form-helper>
                <x-input-error :messages="$errors->get('reference_no')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="activity_name" value="Activity Name" />
                <x-text-input id="activity_name" name="activity_name" class="mt-1 block w-full" :value="old('activity_name', $activity->activity_name)" placeholder="e.g. Cybersecurity Awareness Workshop" required />
                <x-input-error :messages="$errors->get('activity_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="activity_code" value="Activity Code" />
                <select id="activity_code" name="activity_code" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" required>
                    @foreach ($activityCodes as $code => $label)
                        <option value="{{ $code }}" @selected(old('activity_code', $activity->activity_code) === $code)>{{ $code }} {{ $label }}</option>
                    @endforeach
                </select>
                <x-form-helper>Select the category used for programme paperwork and reporting.</x-form-helper>
                <x-input-error :messages="$errors->get('activity_code')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="activity_date" value="Activity Date" />
                <x-text-input id="activity_date" name="activity_date" type="date" class="mt-1 block w-full" :value="old('activity_date', $activity->activity_date?->format('Y-m-d'))" />
                <x-input-error :messages="$errors->get('activity_date')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="enterprise-card rounded-xl border p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-[var(--color-text)]">B. Activity Details</h3>
        <div class="mt-5 grid gap-5 md:grid-cols-3">
            <div>
                <x-input-label for="venue" value="Venue" />
                <x-text-input id="venue" name="venue" class="mt-1 block w-full" :value="old('venue', $activity->venue)" placeholder="e.g. BK 3 / Dewan / Online" />
                <x-input-error :messages="$errors->get('venue')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="participant_count" value="Participant Count" />
                <x-text-input id="participant_count" name="participant_count" type="number" min="0" step="1" class="mt-1 block w-full" :value="old('participant_count', $activity->participant_count)" placeholder="e.g. 45" />
                <x-input-error :messages="$errors->get('participant_count')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="speaker_type" value="Trainer" />
                <select id="speaker_type" name="speaker_type" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" required>
                    @foreach ($speakerTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('speaker_type', $activity->speaker_type ?: 'internal') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('speaker_type')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="enterprise-card rounded-xl border p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 class="text-sm font-semibold text-[var(--color-text)]">C. Budget Breakdown</h3>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Budget totals are calculated automatically and formatted in RM.</p>
            </div>
            <div class="grid gap-2 text-right text-sm">
                <span class="font-semibold text-[var(--color-text)]">OS Subtotal: <span x-text="money(subtotal)"></span></span>
                <span class="font-semibold text-[var(--color-accent-text)]">Total Budget: <span x-text="money(total)"></span></span>
            </div>
        </div>

        <div class="mt-5 grid gap-5 md:grid-cols-4">
            <div>
                <x-input-label for="os_21000" value="OS 21000" />
                <x-text-input id="os_21000" name="os_21000" type="number" min="0" step="0.01" x-model.number="os21000" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('os_21000')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="os_29000" value="OS 29000" />
                <x-text-input id="os_29000" name="os_29000" type="number" min="0" step="0.01" x-model.number="os29000" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('os_29000')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="os_42000" value="OS 42000" />
                <x-text-input id="os_42000" name="os_42000" type="number" min="0" step="0.01" x-model.number="os42000" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('os_42000')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="hep_allocation" value="HEP Allocation" />
                <x-text-input id="hep_allocation" name="hep_allocation" type="number" min="0" step="0.01" x-model.number="hep" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('hep_allocation')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="enterprise-card rounded-xl border p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-[var(--color-text)]">D. Document Links</h3>
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="paperwork_link" value="Paperwork Link" />
                <x-text-input id="paperwork_link" name="paperwork_link" type="url" class="mt-1 block w-full" :value="old('paperwork_link', $activity->paperwork_link)" placeholder="https://drive.google.com/..." />
                <x-form-helper>Paste a Google Drive, OneDrive, or cloud storage link if available.</x-form-helper>
                <x-input-error :messages="$errors->get('paperwork_link')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="implementation_report_link" value="Programme Reports" />
                <x-text-input id="implementation_report_link" name="implementation_report_link" type="url" class="mt-1 block w-full" :value="old('implementation_report_link', $activity->implementation_report_link)" placeholder="https://onedrive.live.com/..." />
                <x-form-helper>Add the programme report link when available.</x-form-helper>
                <x-input-error :messages="$errors->get('implementation_report_link')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="enterprise-card rounded-xl border p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-[var(--color-text)]">E. Submission Status</h3>
        <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">{{ $statusHelp }}</p>
        <div class="mt-5 flex flex-wrap gap-3">
            @if (in_array($currentStatus, [\App\Modules\ProgramGo\Models\ProgramActivity::STATUS_DRAFT], true))
                <button type="submit" name="intent" value="draft" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Save Draft</button>
                <button type="submit" name="intent" value="in_progress" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Submit as In Progress</button>
                <a href="{{ route('program-go.activities.index', ['view' => 'my']) }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</a>
            @elseif ($currentStatus === \App\Modules\ProgramGo\Models\ProgramActivity::STATUS_IN_PROGRESS)
                <button type="submit" name="intent" value="in_progress" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Save Changes</button>
                <button type="submit" name="intent" value="completed" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Mark Completed</button>
                <a href="{{ route('program-go.activities.index', ['view' => 'my']) }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</a>
            @elseif ($currentStatus === \App\Modules\ProgramGo\Models\ProgramActivity::STATUS_COMPLETED)
                <button type="submit" name="intent" value="submit_verification" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Submit for Verification</button>
                <a href="{{ route('program-go.activities.index', ['view' => 'my']) }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</a>
            @elseif ($currentStatus === \App\Modules\ProgramGo\Models\ProgramActivity::STATUS_RETURNED)
                <button type="submit" name="intent" value="returned_save" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Save Changes</button>
                <button type="submit" name="intent" value="submit_verification" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Resubmit for Verification</button>
                <a href="{{ route('program-go.activities.index', ['view' => 'my']) }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</a>
            @else
                <a href="{{ route('program-go.activities.index', ['view' => 'my']) }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Back</a>
            @endif
        </div>
    </section>
</div>
