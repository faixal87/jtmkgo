@php
    $sectionIconClass = 'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Announcement</h2>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Send announcement emails (system maintenance, holidays, birthday wishes, etc.) to all users or selected users. Delivered 30 emails per minute.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8"
            x-data="announcementPage(
                {{ Illuminate\Support\Js::from($templates) }},
                {{ Illuminate\Support\Js::from($users->map(fn ($user) => ['id' => (string) $user->id, 'name' => $user->name, 'email' => $user->email])) }},
                {{ Illuminate\Support\Js::from(auth()->user()->name) }}
            )"
            x-init="onTemplateChange()"
        >
            <x-toast />

            <div class="flex flex-wrap gap-2 border-b border-[var(--color-border)] pb-4">
                <button type="button" @click="pageTab = 'send'" class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition" :class="pageTab === 'send' ? 'theme-button-primary shadow-sm' : 'border border-[var(--color-border)] text-[var(--color-muted)] hover:bg-[var(--color-surface)]'">
                    <x-themed-icon name="send" size="sm" />
                    Send Announcement
                </button>
                <button type="button" @click="pageTab = 'templates'" class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition" :class="pageTab === 'templates' ? 'theme-button-primary shadow-sm' : 'border border-[var(--color-border)] text-[var(--color-muted)] hover:bg-[var(--color-surface)]'">
                    <x-themed-icon name="file" size="sm" />
                    Manage Templates
                </button>
            </div>

            <div x-show="pageTab === 'send'" x-cloak>
                <form method="POST" action="{{ route('super-admin.announcements.store') }}" class="grid items-start gap-6 lg:grid-cols-2" x-on:submit="onSubmitSend">
                    @csrf

                    <input type="hidden" name="recipients" :value="recipientMode">
                    <template x-for="id in selectedUserIds" :key="id">
                        <input type="hidden" name="user_ids[]" :value="id">
                    </template>
                    <input type="hidden" name="subject" :value="computedSubject">
                    <input type="hidden" name="body_html" :value="computedHtml">

                    <div class="enterprise-card space-y-6 rounded-2xl border p-6">
                        <section>
                            <div class="flex items-center gap-3">
                                <span class="{{ $sectionIconClass }}">
                                    <x-themed-icon name="file" />
                                </span>
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Template</h3>
                            </div>
                            <p class="mt-1 text-sm text-[var(--color-muted)]">Pick a ready-made template for common occasions, or a custom template you have added.</p>

                            <div class="mt-4">
                                <x-input-label for="template_uid" value="Template" />
                                <select id="template_uid" x-model="templateUid" @change="onTemplateChange" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                    <template x-for="template in templates" :key="template.uid">
                                        <option :value="template.uid" x-text="template.label"></option>
                                    </template>
                                </select>
                            </div>
                        </section>

                        <section class="border-t border-[var(--color-border)] pt-6" x-show="placeholders.length > 0">
                            <div class="flex items-center gap-3">
                                <span class="{{ $sectionIconClass }}">
                                    <x-themed-icon name="edit" />
                                </span>
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Announcement Details</h3>
                            </div>
                            <p class="mt-1 text-sm text-[var(--color-muted)]">Just fill in the blanks below — the email content is assembled automatically from the template.</p>

                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <template x-for="token in placeholders" :key="token">
                                    <div :class="isLongField(token) ? 'md:col-span-2' : ''">
                                        <label class="block text-sm font-medium text-[var(--color-text)]" x-text="labelFor(token)"></label>
                                        <template x-if="isLongField(token)">
                                            <textarea x-model="fieldValues[token]" rows="4" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]"></textarea>
                                        </template>
                                        <template x-if="! isLongField(token)">
                                            <input type="text" x-model="fieldValues[token]" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <p class="mt-3 text-xs text-[var(--color-muted)]"><code>{name}</code> in the template is replaced with each recipient's own name automatically. The birthday template also embeds each recipient's profile photo via <code>{photo}</code>.</p>
                        </section>

                        <section class="border-t border-[var(--color-border)] pt-6">
                            <div class="flex items-center gap-3">
                                <span class="{{ $sectionIconClass }}">
                                    <x-themed-icon name="users" />
                                </span>
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Recipients</h3>
                            </div>
                            <p class="mt-1 text-sm text-[var(--color-muted)]">Send to all approved users, or pick specific users only.</p>

                            <div class="mt-4 flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-2 text-sm font-medium text-[var(--color-text)]">
                                    <input type="radio" value="all" x-model="recipientMode" class="border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                    All Users <span class="text-[var(--color-muted)]" x-text="'(' + totalUsers + ')'"></span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm font-medium text-[var(--color-text)]">
                                    <input type="radio" value="selected" x-model="recipientMode" class="border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                    Selected Users <span class="text-[var(--color-muted)]" x-text="'(' + selectedUserIds.length + ' selected)'"></span>
                                </label>
                            </div>

                            <div x-show="recipientMode === 'selected'" x-cloak class="mt-4 rounded-xl border border-[var(--color-border)] p-4">
                                <x-text-input class="block w-full" placeholder="Search name or email..." x-model="userSearch" />

                                <div class="mt-3 max-h-72 overflow-y-auto rounded-lg border border-[var(--color-border)]">
                                    <template x-for="user in filteredUsers" :key="user.id">
                                        <label class="flex items-center gap-3 border-b border-[var(--color-border)] px-3 py-2 text-sm last:border-b-0 hover:bg-[var(--color-surface)]">
                                            <input type="checkbox" :value="user.id" x-model="selectedUserIds" class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                            <span class="min-w-0 flex-1">
                                                <span class="font-semibold text-[var(--color-text)]" x-text="user.name"></span>
                                                <span class="ms-2 text-[var(--color-muted)]" x-text="user.email"></span>
                                            </span>
                                        </label>
                                    </template>
                                    <p x-show="filteredUsers.length === 0" class="px-3 py-4 text-center text-sm text-[var(--color-muted)]">No matching users.</p>
                                </div>

                                <div class="mt-3 flex gap-4 text-sm font-semibold text-[var(--color-accent-text)]">
                                    <button type="button" @click="selectedUserIds = filteredUsers.map(u => u.id)">Select all shown</button>
                                    <button type="button" @click="selectedUserIds = []">Clear selection</button>
                                </div>
                            </div>
                        </section>

                        <div class="flex flex-wrap items-center gap-3 border-t border-[var(--color-border)] pt-5">
                            <button type="submit" class="theme-button-primary inline-flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-semibold shadow-sm">
                                <x-themed-icon name="send" size="sm" />
                                Send Announcement
                            </button>
                            <p class="text-xs text-[var(--color-muted)]">Queued in batches of 30 emails per minute. Track delivery in <a href="{{ route('super-admin.email-logs.index') }}" class="font-semibold underline">Email Log</a>.</p>
                        </div>
                    </div>

                    <div class="enterprise-card rounded-2xl border p-6 lg:sticky lg:top-6">
                        <div class="flex items-center gap-3">
                            <span class="{{ $sectionIconClass }}">
                                <x-themed-icon name="eye" />
                            </span>
                            <h3 class="text-lg font-semibold text-[var(--color-text)]">Preview</h3>
                        </div>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Subject: <span class="font-semibold text-[var(--color-text)]" x-text="previewSubject"></span></p>
                        <div class="mt-3 overflow-hidden rounded-xl border border-[var(--color-border)]">
                            <iframe :srcdoc="previewHtml" @load="resizeFrame($event.target)" class="w-full bg-white" style="height:480px;border:0;display:block;" title="Email preview"></iframe>
                        </div>
                    </div>
                </form>
            </div>

            <div x-show="pageTab === 'templates'" x-cloak class="space-y-6">
                <section class="enterprise-card rounded-2xl border p-6">
                    <div class="flex items-center gap-3">
                        <span class="{{ $sectionIconClass }}">
                            <x-themed-icon name="file" />
                        </span>
                        <h3 class="text-lg font-semibold text-[var(--color-text)]">Custom Templates</h3>
                    </div>
                    <p class="mt-1 text-sm text-[var(--color-muted)]">Built-in templates cannot be edited or deleted. Add a new template below if you need a different design.</p>

                    <div class="mt-4 divide-y divide-[var(--color-border)]">
                        @forelse ($customTemplates as $template)
                            <div class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-[var(--color-text)]">{{ $template->label }}</div>
                                    <div class="truncate text-sm text-[var(--color-muted)]">{{ $template->subject }}</div>
                                </div>
                                <form method="POST" action="{{ route('super-admin.announcements.templates.destroy', $template) }}" onsubmit="return confirm('Delete template &quot;{{ $template->label }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-red-600 hover:underline">Delete</button>
                                </form>
                            </div>
                        @empty
                            <p class="py-4 text-sm text-[var(--color-muted)]">No custom templates yet. Add one using the form below.</p>
                        @endforelse
                    </div>
                </section>

                <form method="POST" action="{{ route('super-admin.announcements.templates.store') }}" class="enterprise-card space-y-6 rounded-2xl border p-6">
                    @csrf

                    <div>
                        <div class="flex items-center gap-3">
                            <span class="{{ $sectionIconClass }}">
                                <x-themed-icon name="plus" />
                            </span>
                            <h3 class="text-lg font-semibold text-[var(--color-text)]">Add New Template</h3>
                        </div>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Write your own email HTML. Use tokens like <code>[TARIKH]</code> or <code>[MESEJ]</code> for the parts admins will fill in when sending — they become form fields automatically. <code>{name}</code> inserts the recipient's name; <code>{photo}</code> embeds their profile photo.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-input-label for="new_template_label" value="Template Name" />
                            <x-text-input id="new_template_label" name="label" class="mt-1 block w-full" :value="old('label')" required />
                            <x-input-error :messages="$errors->get('label')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="new_template_subject" value="Email Subject" />
                            <x-text-input id="new_template_subject" name="subject" class="mt-1 block w-full" :value="old('subject')" placeholder="e.g. [TAJUK] - JTMK Go!" required />
                            <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-2">
                        <div>
                            <x-input-label for="new_template_html" value="HTML Code" />
                            <textarea id="new_template_html" name="html" x-model="newTemplateHtml" rows="20" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 font-mono text-xs text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" required>{{ old('html') }}</textarea>
                            <x-input-error :messages="$errors->get('html')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label value="Preview" />
                            <div class="mt-1 overflow-hidden rounded-xl border border-[var(--color-border)]">
                                <iframe :srcdoc="newTemplateHtml" @load="resizeFrame($event.target)" class="w-full bg-white" style="height:480px;border:0;display:block;" title="Template preview"></iframe>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="theme-button-primary inline-flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-semibold shadow-sm">
                            <x-themed-icon name="save" size="sm" />
                            Save Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function announcementPage(templates, users, previewName) {
            return {
                templates,
                users,
                previewName,
                pageTab: @json($activeTab),
                templateUid: templates[0]?.uid ?? '',
                fieldValues: {},
                recipientMode: 'all',
                selectedUserIds: [],
                userSearch: '',
                newTemplateHtml: @json(old('html', '')),
                get selectedTemplate() {
                    return this.templates.find((template) => template.uid === this.templateUid) ?? null;
                },
                get placeholders() {
                    const template = this.selectedTemplate;
                    if (! template) {
                        return [];
                    }
                    const text = (template.subject || '') + ' ' + (template.html || '');
                    const matches = [...text.matchAll(/\[([^\]]+)\]/g)].map((match) => match[1]);
                    return [...new Set(matches)];
                },
                get totalUsers() {
                    return this.users.length;
                },
                get filteredUsers() {
                    const term = this.userSearch.trim().toLowerCase();
                    if (term === '') {
                        return this.users;
                    }
                    return this.users.filter((user) => user.name.toLowerCase().includes(term) || user.email.toLowerCase().includes(term));
                },
                labelFor(token) {
                    return token.toLowerCase().split(' ').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
                },
                isLongField(token) {
                    return /MESEJ|CATATAN|KANDUNGAN|UCAPAN/i.test(token);
                },
                substitute(text) {
                    let result = text || '';
                    for (const token of this.placeholders) {
                        const value = this.fieldValues[token] ?? '';
                        result = result.split(`[${token}]`).join(value);
                    }
                    return result;
                },
                get computedSubject() {
                    return this.selectedTemplate ? this.substitute(this.selectedTemplate.subject) : '';
                },
                get computedHtml() {
                    return this.selectedTemplate ? this.substitute(this.selectedTemplate.html) : '';
                },
                get previewSubject() {
                    return this.computedSubject.split('{name}').join(this.previewName);
                },
                get previewHtml() {
                    const photoPlaceholder = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 20px;"><tr><td align="center"><div style="width:140px;height:140px;border-radius:70px;background:#F9EDF1;border:4px solid #F0E2E6;text-align:center;line-height:140px;font-size:13px;color:#701A33;font-family:Arial,sans-serif;">Foto Staf</div></td></tr></table>';
                    return this.computedHtml
                        .split('{name}').join(this.previewName)
                        .split('{photo}').join(photoPlaceholder);
                },
                onTemplateChange() {
                    const values = {};
                    for (const token of this.placeholders) {
                        values[token] = '';
                    }
                    this.fieldValues = values;
                },
                resizeFrame(frame) {
                    const doc = frame.contentDocument;
                    if (doc?.documentElement) {
                        frame.style.height = Math.max(360, doc.documentElement.scrollHeight + 8) + 'px';
                    }
                },
                onSubmitSend(event) {
                    if (this.recipientMode === 'selected' && this.selectedUserIds.length === 0) {
                        event.preventDefault();
                        alert('Please select at least one user.');
                        return;
                    }
                    const recipientLabel = this.recipientMode === 'all' ? `ALL users (${this.totalUsers})` : `${this.selectedUserIds.length} selected user(s)`;
                    if (! confirm(`Send this announcement email to ${recipientLabel}?`)) {
                        event.preventDefault();
                    }
                },
            };
        }
    </script>
</x-app-layout>
