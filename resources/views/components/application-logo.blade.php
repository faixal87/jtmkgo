<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center']) }}>
    <img
        src="{{ asset('images/logo-jtmk.png') }}"
        alt="JTMK"
        class="h-full w-auto object-contain"
        onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');"
    >
    <span class="hidden whitespace-nowrap rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm">JTMK</span>
</span>
