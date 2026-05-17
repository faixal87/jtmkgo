@props([
    'subject',
    'history' => null,
    'selectable' => false,
])

<article {{ $attributes->merge(['class' => 'enterprise-card min-w-0 rounded-xl border p-4 shadow-sm']) }}>
    <div class="flex min-w-0 flex-col gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <h3 class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $subject->course_code }} - {{ $subject->course_name }}</h3>
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
                <dt class="text-[var(--color-muted)]">Taught Before</dt>
                <dd class="mt-1 font-semibold text-[var(--color-text)]">{{ $history['count'] ?? 0 }} time(s)</dd>
            </div>
        </dl>

        <p class="break-words text-xs text-[var(--color-muted)]">
            Coordinator: {{ $subject->coordinator?->name ?: 'Not assigned' }}
        </p>

        @if (! empty($history['last_session']))
            <p class="break-words text-xs text-[var(--color-muted)]">Last taught semester: {{ $history['last_session'] }}</p>
        @endif

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
