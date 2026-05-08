@props([
    'user',
    'selected' => false,
])

@php
    $isModuleAdmin = $user->adminModules->isNotEmpty();
@endphp

<button
    type="button"
    @click="selectUser({{ $user->id }})"
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
        <span class="mt-0.5 flex flex-wrap gap-1">
            @if ($user->is_super_admin)
                <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide text-amber-700">Super Admin</span>
            @elseif ($isModuleAdmin)
                <span class="rounded-full border border-[var(--color-border)] px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--color-accent-text)]">Module Admin</span>
            @else
                <span class="rounded-full border border-[var(--color-border)] px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--color-muted)]">Staff</span>
            @endif
        </span>
    </span>

    @if (($user->pending_module_access_request_count ?? 0) > 0)
        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[0.65rem] font-semibold text-amber-700">
            {{ $user->pending_module_access_request_count }}
        </span>
    @endif
</button>
