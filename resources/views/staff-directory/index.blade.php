@php
    $firstStaffId = $selectedUserId ?? $selectedPerson?->id ?? $staff->first()?->id;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Staff Directory</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Search JTMK staff profiles and timetable short codes.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            id="staff-directory-workspace"
            class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            x-data="{
                selectedStaff: @js($firstStaffId),
                staffSearch: @js($search ?? ''),
                detailLoading: false,
                async selectStaff(staffId, detailUrl = null) {
                    this.selectedStaff = Number(staffId);

                    const url = new URL(window.location.href);
                    url.searchParams.set('user_id', staffId);
                    window.history.replaceState({}, '', url);

                    if (!detailUrl) {
                        return;
                    }

                    const detailTarget = document.getElementById('staff-directory-detail-panel');

                    if (!detailTarget) {
                        window.location.href = detailUrl;
                        return;
                    }

                    const requestUrl = new URL(detailUrl, window.location.origin);
                    requestUrl.searchParams.set('user_id', staffId);
                    requestUrl.searchParams.set('_partial', 'staff-directory-detail');

                    this.detailLoading = true;
                    const scrollX = window.scrollX;
                    const scrollY = window.scrollY;

                    try {
                        const response = await fetch(requestUrl, {
                            headers: {
                                Accept: 'text/html',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Unable to load staff profile.');
                        }

                        detailTarget.innerHTML = await response.text();

                        if (window.Alpine?.initTree) {
                            window.Alpine.initTree(detailTarget);
                        }

                        window.scrollTo(scrollX, scrollY);
                    } catch (error) {
                        console.error(error);
                        window.location.href = detailUrl;
                    } finally {
                        this.detailLoading = false;
                    }
                },
            }"
        >
            <x-split-panel-layout>
                <form id="staff-directory-search-form" x-ref="staffSearchForm" method="GET" action="{{ route('staff-directory.index') }}" class="contents">
                    <input id="staff-directory-selected-user" type="hidden" name="user_id" value="{{ $firstStaffId }}">

                    <x-searchable-list-panel
                        title="Staff Directory"
                        placeholder="Search name, code, department, grade"
                        model="staffSearch"
                        name="q"
                        form-ref="staffSearchForm"
                    >
                        <div id="staff-directory-list" class="space-y-1">
                            @include('staff-directory.partials.list', [
                                'staff' => $staff,
                                'search' => $search,
                                'selectedUserId' => $selectedUserId,
                                'officialPhotoUrls' => $officialPhotoUrls,
                            ])
                        </div>
                    </x-searchable-list-panel>
                </form>

                <x-context-detail-panel>
                    <div x-show="detailLoading" x-cloak class="mb-4 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]">
                        Loading staff profile...
                    </div>

                    <div id="staff-directory-detail-panel">
                        @include('staff-directory.partials.detail', [
                            'person' => $selectedPerson,
                            'photoUrl' => $selectedPerson ? ($officialPhotoUrls[$selectedPerson->id] ?? $selectedPerson->profilePhotoUrl()) : null,
                            'canViewSensitiveStaffDirectory' => $canViewSensitiveStaffDirectory,
                            'canViewAuditRequirementLink' => $canViewAuditRequirementLink,
                        ])
                    </div>
                </x-context-detail-panel>
            </x-split-panel-layout>
        </div>
    </div>

    <script>
        (() => {
            const root = document.getElementById('staff-directory-workspace');

            if (!root || root.dataset.staffDirectoryBound === '1') {
                return;
            }

            root.dataset.staffDirectoryBound = '1';

            const form = document.getElementById('staff-directory-search-form');
            const selectedInput = document.getElementById('staff-directory-selected-user');
            const listTarget = document.getElementById('staff-directory-list');
            const detailTarget = document.getElementById('staff-directory-detail-panel');
            const searchInput = form?.querySelector('[name="q"]');
            let searchTimer = null;

            const activeClasses = ['border-[var(--color-accent)]', 'bg-[var(--color-accent-soft)]', 'shadow-sm'];
            const idleClasses = ['border-transparent', 'hover:border-[var(--color-border)]', 'hover:bg-[var(--color-surface)]'];

            const cleanUrl = (url) => {
                const cleaned = new URL(url, window.location.origin);
                cleaned.searchParams.delete('_ajax_list');
                cleaned.searchParams.delete('_partial');

                return cleaned;
            };

            const setSelectedHighlight = (userId) => {
                root.querySelectorAll('[data-staff-directory-staff-link]').forEach((link) => {
                    const isSelected = Number(link.dataset.userId) === Number(userId);

                    link.classList.toggle(activeClasses[0], isSelected);
                    link.classList.toggle(activeClasses[1], isSelected);
                    link.classList.toggle(activeClasses[2], isSelected);
                    idleClasses.forEach((className) => link.classList.toggle(className, !isSelected));
                });
            };

            const fetchList = async (url) => {
                if (!listTarget) {
                    window.location.href = url;
                    return;
                }

                const requestUrl = new URL(url, window.location.origin);
                requestUrl.searchParams.set('_ajax_list', 'staff-directory-list');
                const scrollX = window.scrollX;
                const scrollY = window.scrollY;

                listTarget.classList.add('opacity-60', 'pointer-events-none');

                try {
                    const response = await fetch(requestUrl, {
                        headers: {
                            Accept: 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Unable to update staff list.');
                    }

                    listTarget.innerHTML = await response.text();
                    window.history.pushState({}, '', cleanUrl(requestUrl));
                    setSelectedHighlight(selectedInput?.value);
                    window.scrollTo(scrollX, scrollY);
                } catch (error) {
                    console.error(error);
                    window.location.href = cleanUrl(requestUrl);
                } finally {
                    listTarget.classList.remove('opacity-60', 'pointer-events-none');
                }
            };

            const listUrlFromForm = () => {
                const url = new URL(form.action || window.location.href, window.location.origin);
                url.search = new URLSearchParams(new FormData(form)).toString();

                return url;
            };

            const fetchDetail = async (userId, detailUrl) => {
                if (!detailTarget) {
                    window.location.href = detailUrl;
                    return;
                }

                const requestUrl = new URL(detailUrl, window.location.origin);
                requestUrl.searchParams.set('user_id', userId);
                requestUrl.searchParams.set('_partial', 'staff-directory-detail');
                const scrollX = window.scrollX;
                const scrollY = window.scrollY;

                detailTarget.classList.add('opacity-60', 'pointer-events-none');

                try {
                    const response = await fetch(requestUrl, {
                        headers: {
                            Accept: 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Unable to load staff profile.');
                    }

                    detailTarget.innerHTML = await response.text();
                    window.history.replaceState({}, '', cleanUrl(requestUrl));
                    window.scrollTo(scrollX, scrollY);
                } catch (error) {
                    console.error(error);
                    window.location.href = cleanUrl(requestUrl);
                } finally {
                    detailTarget.classList.remove('opacity-60', 'pointer-events-none');
                }
            };

            form?.addEventListener('submit', (event) => {
                event.preventDefault();
                fetchList(listUrlFromForm());
            });

            searchInput?.addEventListener('input', () => {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(() => fetchList(listUrlFromForm()), 300);
            });

            root.addEventListener('click', (event) => {
                const staffLink = event.target.closest('[data-staff-directory-staff-link]');

                if (staffLink) {
                    event.preventDefault();
                    const userId = staffLink.dataset.userId;

                    if (selectedInput) {
                        selectedInput.value = userId;
                    }

                    setSelectedHighlight(userId);
                    fetchDetail(userId, staffLink.dataset.detailUrl || staffLink.href);

                    return;
                }

                const pageLink = event.target.closest('[data-staff-directory-pagination] a');

                if (pageLink) {
                    event.preventDefault();
                    fetchList(pageLink.href);
                }
            });
        })();
    </script>
</x-app-layout>
