@php
    $stats = [
        ['label' => 'Approved Photos', 'value' => $approvedCount, 'tone' => 'emerald'],
        ['label' => 'My Uploads', 'value' => $myPhotoCount, 'tone' => 'blue'],
    ];

    if ($canViewAnalytics) {
        $stats[] = ['label' => 'Pending Review', 'value' => $pendingCount, 'tone' => 'amber'];
        $stats[] = ['label' => 'Profiles', 'value' => $profileCount, 'tone' => 'purple'];
        $stats[] = ['label' => 'Categories', 'value' => $categoryCount, 'tone' => 'blue'];
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Photo Repository</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Official portrait and profile photos for JTMK staff, management, VIP, and external profiles.</p>
            </div>
            @can('upload-photo-repository')
                <a href="{{ route('photo-repository.upload.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm">Upload Photo</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <x-toast />
            @endif

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @foreach ($stats as $stat)
                    <x-stat-card :label="$stat['label']" :value="$stat['value']" :tone="$stat['tone']" />
                @endforeach
            </section>

            @if ($canViewAnalytics)
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <x-dashboard.status-card title="Analytics" description="Track downloads, access activity, and storage usage." :href="route('photo-repository.admin.analytics')" accent="blue" icon="activity" />
                    @can('manage-photo-repository')
                        <x-dashboard.status-card title="Review Queue" description="Approve or reject pending portrait uploads." :href="route('photo-repository.admin.review-queue')" accent="amber" icon="activity" />
                        <x-dashboard.status-card title="Profiles" description="Create and manage internal, external, VIP, and management profiles." :href="route('photo-repository.admin.profiles')" accent="purple" icon="users" />
                        <x-dashboard.status-card title="Categories" description="Maintain official photo category taxonomy." :href="route('photo-repository.admin.categories')" accent="emerald" icon="shield" />
                    @endcan
                </section>
            @endif

            <section class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Latest Approved Photos</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Recently approved portraits available for staff use.</p>
                    </div>
                    <a href="{{ route('photo-repository.gallery') }}" class="text-sm font-semibold text-[var(--color-accent-text)] hover:underline">Open gallery</a>
                </div>

                @if ($latestPhotos->isEmpty())
                    <x-empty-state title="No approved photos yet" message="Approved repository photos will appear here." />
                @else
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                        @foreach ($latestPhotos as $photo)
                            @include('photo-repository.partials.photo-card', ['photo' => $photo])
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
