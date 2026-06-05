@once
    <script>
        function linkGoFallbackCopy(value) {
            const textarea = document.createElement('textarea');
            textarea.value = value;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            textarea.style.pointerEvents = 'none';
            document.body.appendChild(textarea);
            textarea.select();
            textarea.setSelectionRange(0, value.length);

            try {
                document.execCommand('copy');
            } finally {
                document.body.removeChild(textarea);
            }
        }

        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-link-go-copy]');

            if (! button) {
                return;
            }

            event.preventDefault();

            const originalText = button.textContent.trim();

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(button.dataset.url);
                } else {
                    linkGoFallbackCopy(button.dataset.url);
                }

                button.textContent = 'Copied';
                setTimeout(() => button.textContent = originalText, 1800);
            } catch (error) {
                button.textContent = 'Copy failed';
                setTimeout(() => button.textContent = originalText, 1800);

                return;
            }

            if (button.dataset.endpoint) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                fetch(button.dataset.endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    },
                }).catch(() => {
                    // Copy already succeeded; tracking can safely fail without confusing the user.
                });
            }
        });
    </script>
@endonce
