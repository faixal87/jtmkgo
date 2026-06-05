@props([
    'target',
    'form' => null,
    'loadingText' => 'Loading...',
])

<div
    data-ajax-list-shell
    data-ajax-list-target="{{ $target }}"
    @if ($form) data-ajax-list-form="{{ $form }}" @endif
    {{ $attributes }}
>
    <div data-ajax-list-loading class="hidden rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-xs font-semibold text-[var(--color-muted)]">
        {{ $loadingText }}
    </div>

    {{ $slot }}
</div>

@once
    <script>
        (() => {
            if (window.jtmkAjaxListBound) {
                return;
            }

            window.jtmkAjaxListBound = true;

            const debounceTimers = new WeakMap();

            const shellFor = (element) => element.closest('[data-ajax-list-shell]');

            const setLoading = (shell, loading) => {
                const loader = shell?.querySelector('[data-ajax-list-loading]');
                const target = shell ? document.querySelector(shell.dataset.ajaxListTarget) : null;

                if (loader) {
                    loader.classList.toggle('hidden', !loading);
                }

                if (target) {
                    target.classList.toggle('opacity-60', loading);
                    target.classList.toggle('pointer-events-none', loading);
                }
            };

            const fetchList = async (shell, url, options = {}) => {
                const targetSelector = shell.dataset.ajaxListTarget;
                const target = document.querySelector(targetSelector);

                if (!target || !url) {
                    return;
                }

                const requestUrl = new URL(url, window.location.origin);
                requestUrl.searchParams.set('_ajax_list', targetSelector.replace('#', ''));

                setLoading(shell, true);
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
                        throw new Error('Unable to update list.');
                    }

                    target.innerHTML = await response.text();

                    if (window.Alpine?.initTree) {
                        window.Alpine.initTree(target);
                    }

                    if (options.pushState !== false) {
                        requestUrl.searchParams.delete('_ajax_list');
                        window.history.pushState({}, '', requestUrl);
                    }

                    window.scrollTo(scrollX, scrollY);
                } catch (error) {
                    console.error(error);
                    window.location.href = url;
                } finally {
                    setLoading(shell, false);
                }
            };

            const urlFromForm = (form) => {
                const url = new URL(form.action || window.location.href, window.location.origin);
                const params = new URLSearchParams(new FormData(form));

                url.search = params.toString();

                return url.toString();
            };

            document.addEventListener('submit', (event) => {
                const form = event.target.closest('form[data-ajax-list-form]');

                if (!form) {
                    return;
                }

                const shell = document.querySelector(`[data-ajax-list-form="#${form.id}"]`) || shellFor(form);

                if (!shell) {
                    return;
                }

                event.preventDefault();
                fetchList(shell, urlFromForm(form));
            });

            document.addEventListener('input', (event) => {
                const input = event.target.closest('[data-ajax-list-search]');

                if (!input) {
                    return;
                }

                const form = input.form;
                const shell = form
                    ? document.querySelector(`[data-ajax-list-form="#${form.id}"]`) || shellFor(form)
                    : shellFor(input);

                if (!form || !shell) {
                    return;
                }

                window.clearTimeout(debounceTimers.get(input));
                debounceTimers.set(input, window.setTimeout(() => {
                    fetchList(shell, urlFromForm(form));
                }, 300));
            });

            document.addEventListener('click', (event) => {
                const link = event.target.closest('[data-ajax-list-pagination] a');

                if (!link) {
                    return;
                }

                const shell = shellFor(link);

                if (!shell) {
                    return;
                }

                event.preventDefault();
                fetchList(shell, link.href);
            });

            window.addEventListener('popstate', () => {
                document.querySelectorAll('[data-ajax-list-shell]').forEach((shell) => {
                    fetchList(shell, window.location.href, { pushState: false });
                });
            });
        })();
    </script>
@endonce
