@props([
    'link',
    'portfolios',
    'visibilityOptions',
    'canManage' => false,
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Link',
])

<form method="POST" action="{{ $action }}" class="enterprise-card space-y-6 rounded-xl border p-6 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <section>
        <h2 class="text-sm font-semibold text-[var(--color-text)]">Link Information</h2>
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div>
                <x-input-label for="title" value="Title" />
                <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title', $link->title)" placeholder="e.g. Audit MQA Evidence Folder" />
                <p class="mt-1 text-xs text-[var(--color-muted)]">Use a clear name so other staff can recognize the link quickly.</p>
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="url" value="URL" />
                <x-text-input id="url" name="url" type="url" class="mt-1 block w-full" :value="old('url', $link->url)" placeholder="https://drive.google.com/..." />
                <p class="mt-1 text-xs text-[var(--color-muted)]">Must start with http:// or https://.</p>
                <x-input-error :messages="$errors->get('url')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="portfolio_id" value="Portfolio" />
                <select id="portfolio_id" name="portfolio_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                    <option value="">No portfolio selected</option>
                    @foreach ($portfolios as $portfolio)
                        <option value="{{ $portfolio->id }}" @selected((string) old('portfolio_id', $link->portfolio_id) === (string) $portfolio->id)>{{ $portfolio->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-[var(--color-muted)]">Portfolio is optional but recommended for easier filtering.</p>
                <x-input-error :messages="$errors->get('portfolio_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="visibility" value="Visibility" />
                <select id="visibility" name="visibility" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                    @foreach ($visibilityOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('visibility', $link->visibility ?: 'all_jtmk') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-[var(--color-muted)]">All JTMK is visible to approved LinkGo users. Owner only keeps it private to you and admins.</p>
                <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4">
            <x-input-label for="description" value="Description" />
            <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" placeholder="Optional notes about what this link is for.">{{ old('description', $link->description) }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div>

        @if ($canManage)
            <label class="mt-4 flex items-center gap-3 rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-4">
                <input type="checkbox" name="is_pinned" value="1" class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" @checked(old('is_pinned', $link->is_pinned))>
                <span class="text-sm font-semibold text-[var(--color-text)]">Pin this link</span>
            </label>
        @endif
    </section>

    <div class="flex flex-wrap justify-end gap-2 border-t border-[var(--color-border)] pt-5">
        <a href="{{ url()->previous() }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</a>
        <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">{{ $submitLabel }}</button>
    </div>
</form>
