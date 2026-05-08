<button {{ $attributes->merge(['type' => 'button', 'class' => 'theme-button-secondary inline-flex items-center rounded-lg px-4 py-2 text-xs font-semibold uppercase tracking-widest shadow-sm transition duration-150 ease-in-out disabled:opacity-25']) }}>
    {{ $slot }}
</button>
