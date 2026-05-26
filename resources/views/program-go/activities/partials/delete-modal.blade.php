@props([
    'activity',
    'modalName' => 'delete-program-activity-'.$activity->id,
    'redirectTo' => null,
])

<button
    type="button"
    x-data
    @click="$dispatch('open-modal', @js($modalName))"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-lg border border-red-300 bg-red-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-red-700']) }}
>
    Delete
</button>

<x-modal :name="$modalName" maxWidth="md">
    <form method="POST" action="{{ route('program-go.activities.destroy', $activity) }}" class="p-6">
        @csrf
        @method('DELETE')
        @if ($redirectTo)
            <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
        @endif

        <h3 class="text-lg font-semibold text-[var(--color-text)]">Delete activity?</h3>
        <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">
            This activity will be moved to the deleted archive. You can only delete draft or in-progress activities unless you are a ProgramGo module admin.
        </p>

        <div class="mt-5 rounded-lg bg-[var(--color-secondary-bg)] p-3 text-sm">
            <p class="break-words font-semibold text-[var(--color-text)]">{{ $activity->activity_name }}</p>
            <p class="mt-1 text-[var(--color-muted)]">{{ $activity->statusLabel() }}</p>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" x-on:click="$dispatch('close')" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</button>
            <x-danger-button>Delete Activity</x-danger-button>
        </div>
    </form>
</x-modal>
