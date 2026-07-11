@php($activeTab = $activeTab ?? 'settings')

<div class="flex flex-wrap gap-2">
    <a
        href="{{ route('super-admin.settings.mail.edit') }}"
        class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $activeTab === 'settings' ? 'theme-button-primary shadow-sm' : 'border border-[var(--color-border)] text-[var(--color-muted)] hover:bg-[var(--color-surface)]' }}"
    >
        Mail Settings
    </a>
    <a
        href="{{ route('super-admin.email-logs.index') }}"
        class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $activeTab === 'logs' ? 'theme-button-primary shadow-sm' : 'border border-[var(--color-border)] text-[var(--color-muted)] hover:bg-[var(--color-surface)]' }}"
    >
        Email Logs
    </a>
</div>
