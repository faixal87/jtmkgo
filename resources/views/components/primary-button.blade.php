<button {{ $attributes->merge(['type' => 'submit', 'class' => 'theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-xs font-semibold uppercase tracking-widest transition duration-150 ease-in-out']) }}>
    {{ $slot }}
</button>
