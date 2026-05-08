@php
    $isEditing = $user instanceof \App\Models\User;
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <x-input-label for="name" value="Full Name" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $user?->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="ic_number" value="IC Number" />
        <x-text-input id="ic_number" name="ic_number" class="mt-1 block w-full" :value="old('ic_number', $user?->ic_number)" required />
        <x-input-error :messages="$errors->get('ic_number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user?->email)" required />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="phone" value="Phone Number" />
        <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $user?->phone)" />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="date_of_birth" value="Date of Birth" />
        <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth', $user?->date_of_birth?->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="department" value="Department" />
        <x-text-input id="department" name="department" class="mt-1 block w-full" :value="old('department', $user?->department)" />
        <x-input-error :messages="$errors->get('department')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="position" value="Position" />
        <x-text-input id="position" name="position" class="mt-1 block w-full" :value="old('position', $user?->position)" />
        <x-input-error :messages="$errors->get('position')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="grade" value="Grade" />
        <x-text-input id="grade" name="grade" class="mt-1 block w-full" :value="old('grade', $user?->grade)" />
        <x-input-error :messages="$errors->get('grade')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="mbot_membership" value="MBOT Membership" />
        <x-text-input id="mbot_membership" name="mbot_membership" class="mt-1 block w-full" :value="old('mbot_membership', $user?->mbot_membership)" />
        <x-input-error :messages="$errors->get('mbot_membership')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="bem_membership" value="BEM Membership" />
        <x-text-input id="bem_membership" name="bem_membership" class="mt-1 block w-full" :value="old('bem_membership', $user?->bem_membership)" />
        <x-input-error :messages="$errors->get('bem_membership')" class="mt-2" />
    </div>

    @if ($isEditing)
        <div>
            <x-input-label for="account_status" value="Account Status" />
            <select id="account_status" name="account_status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900" @disabled($user->is_super_admin)>
                @foreach (['pending', 'approved', 'rejected', 'inactive'] as $status)
                    <option value="{{ $status }}" @selected(old('account_status', $user->account_status) === $status)>{{ str($status)->title() }}</option>
                @endforeach
            </select>
            @if ($user->is_super_admin)
                <input type="hidden" name="account_status" value="approved">
            @endif
            <x-input-error :messages="$errors->get('account_status')" class="mt-2" />
        </div>
    @endif

    <div>
        <x-input-label for="password" :value="$isEditing ? 'New Password' : 'Password'" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" @required(! $isEditing) />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="password_confirmation" value="Confirm Password" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" @required(! $isEditing) />
    </div>
</div>

<div class="grid gap-5 md:grid-cols-2">
    <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <h3 class="text-sm font-semibold text-slate-900">Module Access</h3>
        <div class="mt-4 space-y-3">
            @foreach ($modules as $module)
                <label class="flex items-center gap-3 text-sm text-slate-700">
                    <input type="checkbox" name="module_access[]" value="{{ $module->id }}" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900" @checked(in_array($module->id, old('module_access', $activeAccessIds), false))>
                    <span>{{ $module->name }}</span>
                </label>
            @endforeach
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <h3 class="text-sm font-semibold text-slate-900">Module Admin</h3>
        <div class="mt-4 space-y-3">
            @foreach ($modules as $module)
                <label class="flex items-center gap-3 text-sm text-slate-700">
                    <input type="checkbox" name="module_admin[]" value="{{ $module->id }}" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900" @checked(in_array($module->id, old('module_admin', $activeAdminIds), false))>
                    <span>{{ $module->name }}</span>
                </label>
            @endforeach
        </div>
    </section>
</div>

<div class="flex flex-wrap items-center gap-3">
    <x-primary-button>{{ $isEditing ? 'Save Changes' : 'Create User' }}</x-primary-button>
    <a href="{{ route('super-admin.users.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</a>
</div>
