@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border-[var(--color-border)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]']) }}>
