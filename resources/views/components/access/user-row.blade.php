@props([
    'user',
    'selected' => false,
])

@php
    $searchText = strtolower($user->name.' '.$user->ic_number);
    $isModuleAdmin = $user->adminModules->isNotEmpty();
@endphp

<button
    type="button"
    x-show="@js($searchText).includes(userSearch.toLowerCase())"
    @click="selectedUser = {{ $user->id }}"
    class="group flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition duration-200 hover:bg-[var(--color-accent-soft)]"
    :class="selectedUser === {{ $user->id }} ? 'bg-[var(--color-accent-soft)] ring-1 ring-[var(--color-accent)]' : ''"
>
    <span class="relative inline-flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[var(--color-accent-soft)] text-sm font-semibold text-[var(--color-accent-text)] ring-1 ring-[var(--color-border)]">
        @if ($user->profilePhotoUrl())
            <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
        @else
            {{ $user->initials() }}
        @endif
    </span>

    <span class="min-w-0 flex-1">
        <span class="block truncate text-sm font-semibold text-[var(--color-text)]">{{ $user->name }}</span>
        <span class="mt-0.5 block truncate text-xs text-[var(--color-muted)]">IC: {{ $user->ic_number }}</span>
    </span>

    @if ($isModuleAdmin)
        <span class="rounded-full border border-[var(--color-border)] px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--color-accent-text)]">
            Admin
        </span>
    @endif
</button>
