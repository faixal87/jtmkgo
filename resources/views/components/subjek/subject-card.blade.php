@props([
    'subject',
    'experience' => null,
    'history' => null,
    'selectable' => false,
])

@php
    $experience = $experience ?? $history;
    $choiceTotals = [
        1 => (int) ($subject->choice_1_total ?? 0),
        2 => (int) ($subject->choice_2_total ?? 0),
        3 => (int) ($subject->choice_3_total ?? 0),
        4 => (int) ($subject->choice_4_total ?? 0),
    ];
    $selectionTotal = array_sum($choiceTotals);
@endphp

<article {{ $attributes->merge(['class' => 'enterprise-card min-w-0 rounded-xl border p-4 shadow-sm']) }}>
    <div class="flex min-w-0 flex-col gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <h3 class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $subject->label }}</h3>
                @if ($subject->coordinator)
                    <span class="theme-badge">Subject Coordinator</span>
                @endif
            </div>
            <p class="mt-2 break-words text-xs text-[var(--color-muted)]">
                {{ $subject->programme?->code ?: 'General' }}
                @if ($subject->offered_semester)
                    | {{ $subject->offered_semester }}
                @endif
                @if ($subject->curriculum_version)
                    | {{ $subject->curriculum_version }}
                @endif
            </p>
        </div>

        <dl class="grid gap-3 text-xs sm:grid-cols-2">
            <div>
                <dt class="text-[var(--color-muted)]">Credit Hour</dt>
                <dd class="mt-1 font-semibold text-[var(--color-text)]">{{ $subject->credit_hour ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-[var(--color-muted)]">Weekly Contact</dt>
                <dd class="mt-1 font-semibold text-[var(--color-text)]">{{ $subject->weekly_contact_hour ?? 0 }} hour(s)</dd>
            </div>
            <div>
                <dt class="text-[var(--color-muted)]">Class Groups</dt>
                <dd class="mt-1 font-semibold text-[var(--color-text)]">{{ $subject->total_class_groups }}</dd>
            </div>
            <div>
                <dt class="text-[var(--color-muted)]">Experience</dt>
                <dd class="mt-1 font-semibold text-[var(--color-text)]">
                    {{ $experience['years'] ?? 0 }} year(s)
                </dd>
            </div>
        </dl>

        <p class="break-words text-xs text-[var(--color-muted)]">
            Coordinator: {{ $subject->coordinator?->name ?: 'Not assigned' }}
        </p>

        @if ($subject->classGroups->isNotEmpty())
            <div class="space-y-1 text-xs text-[var(--color-muted)]">
                @foreach ($subject->classGroups as $classGroup)
                    <p class="break-words">{{ $classGroup->class_name }}</p>
                @endforeach
            </div>
        @endif

        @if (! empty($experience['level']) || ! empty($experience['last_session']))
            <p class="break-words text-xs text-[var(--color-muted)]">
                @if (! empty($experience['level']))
                    Level: {{ str($experience['level'])->title() }}
                @endif
                @if (! empty($experience['last_session']))
                    {{ ! empty($experience['level']) ? ' | ' : '' }}Last taught: {{ $experience['last_session'] }}
                @endif
            </p>
        @endif

        <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Preference Demand</p>
                <span class="theme-badge">{{ $selectionTotal }} total</span>
            </div>
            <div class="mt-3 grid grid-cols-4 gap-2 text-center text-xs">
                @foreach ($choiceTotals as $rank => $total)
                    <div class="rounded-lg bg-[var(--color-surface)] px-2 py-2">
                        <p class="text-[var(--color-muted)]">C{{ $rank }}</p>
                        <p class="mt-1 font-semibold text-[var(--color-text)]">{{ $total }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($selectable)
            <div class="grid grid-cols-2 gap-2">
                @foreach ([1, 2, 3, 4] as $rank)
                    <button
                        type="button"
                        @click="choose({{ $rank }}, {{ $subject->id }})"
                        class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold"
                    >
                        Choice {{ $rank }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</article>
