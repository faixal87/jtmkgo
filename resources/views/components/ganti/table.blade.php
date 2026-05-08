<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            {{ $slot }}
        </table>
    </div>

    @isset($pagination)
        <div class="border-t border-slate-200 bg-white px-5 py-4">
            {{ $pagination }}
        </div>
    @endisset
</section>
