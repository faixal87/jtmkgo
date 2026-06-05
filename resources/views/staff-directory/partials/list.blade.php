@if (($search ?? '') !== '')
    <p class="mb-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-xs text-[var(--color-muted)]">
        Showing global results for "{{ $search }}".
    </p>
@endif

<div class="mb-2 flex flex-wrap items-center justify-between gap-2 px-1 text-xs text-[var(--color-muted)]">
    <span class="font-semibold text-[var(--color-text)]">Results Found: {{ $staff->total() }} staff</span>
    @if ($staff->total() > 0)
        <span>Showing {{ $staff->firstItem() }}-{{ $staff->lastItem() }}</span>
    @endif
</div>

@forelse ($staff as $person)
    @php
        $photoUrl = $officialPhotoUrls[$person->id] ?? $person->profilePhotoUrl();
        $detailUrl = route('staff-directory.index', array_filter([
            'q' => $search ?? null,
            'user_id' => $person->id,
            'page' => request('page'),
            'per_page' => request('per_page'),
        ], fn ($value) => filled($value)));
    @endphp
    <a
        href="{{ $detailUrl }}"
        data-staff-directory-staff-link
        data-user-id="{{ $person->id }}"
        data-detail-url="{{ $detailUrl }}"
        class="block min-w-0 w-full rounded-xl border px-3 py-3 text-left transition duration-200 {{ (int) $selectedUserId === (int) $person->id ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : 'border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface)]' }}"
    >
        <span class="flex min-w-0 items-center gap-3">
            @if ($photoUrl)
                <img src="{{ $photoUrl }}" alt="{{ $person->name }}" class="h-10 w-10 rounded-full object-cover ring-1 ring-[var(--color-border)]">
            @else
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[var(--color-accent-soft)] text-xs font-semibold text-[var(--color-accent-text)]">
                    {{ $person->initials() ?: 'JG' }}
                </span>
            @endif
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold text-[var(--color-text)]">{{ $person->name }}</span>
            </span>
        </span>
    </a>
@empty
    <x-empty-state title="No staff found" message="Try another name, department, grade, or short code." />
@endforelse

@if ($staff->hasPages())
    <div class="pt-3" data-ajax-list-pagination data-staff-directory-pagination>
        {{ $staff->links() }}
    </div>
@endif
