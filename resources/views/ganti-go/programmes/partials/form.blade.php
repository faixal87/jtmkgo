@php
    $isEditing = isset($programme);
@endphp

<div>
    <x-input-label for="code" value="Programme Code" />
    <x-text-input id="code" name="code" class="mt-1 block w-full uppercase" :value="old('code', $programme->code ?? '')" placeholder="DIT" required />
    <x-input-error :messages="$errors->get('code')" class="mt-2" />
</div>

<div>
    <x-input-label for="name" value="Programme Name" />
    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $programme->name ?? '')" placeholder="Diploma in Information Technology" required />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-900" @checked(old('is_active', $programme->is_active ?? true))>
    <span>
        <span class="block text-sm font-medium text-slate-950">Programme is active</span>
        <span class="mt-1 block text-sm text-slate-500">Inactive programmes remain available for historical records.</span>
    </span>
</label>

<div class="flex flex-wrap gap-3">
    <x-primary-button>{{ $isEditing ? 'Save Changes' : 'Create Programme' }}</x-primary-button>
    <a href="{{ route('ganti-go.programmes.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</a>
</div>
