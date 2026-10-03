<style>
    .password-toggle-wrapper {
        position: relative;
        width: 100%;
    }

    .password-toggle-button {
        position: absolute;
        top: 50%;
        right: .4rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .3rem .4rem;
        border: 1px solid #e2e5e9;
        border-radius: .375rem;
        background: #f8f9fa;
        color: #495057;
        cursor: pointer;
        transform: translateY(-50%);
    }

    .password-toggle-button:hover {
        border-color: #d5d9de;
        background: #f1f3f5;
        color: #343a40;
    }

    .password-toggle-button:focus-visible {
        outline: 2px solid #b71c1c;
        outline-offset: 2px;
    }

    .password-toggle-button svg {
        width: 1.1rem;
        height: 1.1rem;
    }
</style>
<script>
    (() => {
        const eyeIcon = '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        const eyeSlashIcon = '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 5.2A11.4 11.4 0 0 1 12 5c6.4 0 10 7 10 7a15.4 15.4 0 0 1-3.1 3.8"></path><path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10.8 10.8 0 0 0 3.5-.6"></path></svg>';

        function setupPasswordToggles() {
            document.querySelectorAll('input[type="password"]').forEach((input) => {
                if (input.dataset.passwordToggle === 'ready') {
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.className = 'password-toggle-wrapper';
                wrapper.style.marginBottom = getComputedStyle(input).marginBottom;
                input.style.marginBottom = '0';
                input.style.paddingRight = '2.75rem';
                input.dataset.passwordToggle = 'ready';
                if (!input.id) {
                    input.id = `password-field-${document.querySelectorAll('[data-password-toggle="ready"]').length + 1}`;
                }
                input.parentNode.insertBefore(wrapper, input);
                wrapper.appendChild(input);

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'password-toggle-button';
                button.setAttribute('aria-label', 'Show password');
                button.setAttribute('aria-controls', input.id);
                button.setAttribute('aria-pressed', 'false');
                button.innerHTML = eyeIcon;
                button.addEventListener('click', () => {
                    const shouldShow = input.type === 'password';
                    input.type = shouldShow ? 'text' : 'password';
                    button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
                    button.setAttribute('aria-pressed', String(shouldShow));
                    button.innerHTML = shouldShow ? eyeSlashIcon : eyeIcon;
                });
                wrapper.appendChild(button);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupPasswordToggles, { once: true });
        } else {
            setupPasswordToggles();
        }
    })();
</script>
