@php
    $recipientModes = [
        'individual' => ['title' => 'Individual Users', 'description' => 'Send a direct notification to selected staff.'],
        'module' => ['title' => 'Module Access Group', 'description' => 'Notify everyone with access to selected modules.'],
    ];

    if ($canSendAll) {
        $recipientModes['all'] = ['title' => 'All Users', 'description' => 'Send to every approved staff account.'];
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Send Notification</h2>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Compose scoped intranet notifications for staff and module groups.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="{ mode: @js(old('recipient_mode', 'individual')), recipientSearch: '' }">
            <x-toast />

            <x-split-panel-layout height="min-h-[38rem]">
                <x-searchable-list-panel title="Recipient Context" placeholder="Search modes" model="recipientSearch">
                    @foreach ($recipientModes as $mode => $meta)
                        @php($searchableMode = strtolower($meta['title'].' '.$meta['description']))
                        <button
                            type="button"
                            x-show="@js($searchableMode).includes(recipientSearch.toLowerCase())"
                            @click="mode = @js($mode)"
                            class="w-full rounded-xl border px-3 py-3 text-left transition duration-200"
                            :class="mode === @js($mode) ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : 'border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface)]'"
                        >
                            <span class="block text-sm font-semibold text-[var(--color-text)]">{{ $meta['title'] }}</span>
                            <span class="mt-1 block text-xs leading-5 text-[var(--color-muted)]">{{ $meta['description'] }}</span>
                        </button>
                    @endforeach
                </x-searchable-list-panel>

                <x-context-detail-panel>
                    <form method="POST" action="{{ route('admin.notifications.store') }}" class="space-y-6">
                        @csrf
                        <input type="hidden" name="recipient_mode" :value="mode">

                        <section class="border-b border-[var(--color-border)] pb-5">
                            <h3 class="text-lg font-semibold text-[var(--color-text)]">Compose Message</h3>
                            <p class="mt-1 text-sm text-[var(--color-muted)]">Choose recipients from the active context, then write a short and clear notification.</p>
                        </section>

                        <section x-show="mode === 'individual'" x-cloak class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-[var(--color-text)]">Individual Users</h4>
                                    <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $users->count() }} users available in your permission scope.</p>
                                </div>
                            </div>
                            <div class="grid max-h-72 gap-2 overflow-y-auto rounded-xl border border-[var(--color-border)] p-3 md:grid-cols-2">
                                @foreach ($users as $user)
                                    <label class="flex items-start gap-3 rounded-lg px-2 py-2 text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">
                                        <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="mt-0.5 rounded border-slate-300 text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                        <span>
                                            <span class="block font-medium">{{ $user->name }}</span>
                                            <span class="block text-xs text-[var(--color-muted)]">IC: {{ $user->ic_number }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('user_ids')" class="mt-2" />
                        </section>

                        <section x-show="mode === 'module'" x-cloak class="space-y-3">
                            <div>
                                <h4 class="text-sm font-semibold text-[var(--color-text)]">Module Access Groups</h4>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">Select one or more modules. Users with active access will receive the notification.</p>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                @foreach ($modules as $module)
                                    <x-toggle-card
                                        :title="$module->name"
                                        :description="$module->description ?: $module->slug"
                                        name="module_ids[]"
                                        :value="$module->id"
                                        :state="false"
                                    />
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('module_ids')" class="mt-2" />
                        </section>

                        <section x-show="mode === 'all'" x-cloak>
                            <div class="enterprise-card rounded-xl border p-5">
                                <h4 class="text-sm font-semibold text-[var(--color-text)]">All Approved Users</h4>
                                <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">This notification will be sent to every approved staff account. Use this only for broad system announcements.</p>
                            </div>
                        </section>

                        <section class="grid gap-5">
                            <div>
                                <x-input-label for="title" value="Title" />
                                <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title')" required />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="message" value="Message" />
                                <textarea id="message" name="message" rows="5" required class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('message') }}</textarea>
                                <x-input-error :messages="$errors->get('message')" class="mt-2" />
                            </div>
                        </section>

                        <div class="flex flex-wrap items-center gap-3 border-t border-[var(--color-border)] pt-5">
                            <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Send Notification</button>
                            <a href="{{ route('notifications.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-medium">Notification Center</a>
                        </div>
                    </form>
                </x-context-detail-panel>
            </x-split-panel-layout>
        </div>
    </div>
</x-app-layout>
