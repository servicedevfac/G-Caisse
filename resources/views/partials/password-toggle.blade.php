<button
    class="password-toggle"
    type="button"
    data-password-toggle
    data-password-target="{{ $target }}"
    aria-label="Afficher le mot de passe"
    aria-pressed="false"
    title="Afficher le mot de passe"
>
    <svg class="password-eye password-eye-open" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M2.2 12s3.6-6 9.8-6 9.8 6 9.8 6-3.6 6-9.8 6-9.8-6-9.8-6Z"/>
        <circle cx="12" cy="12" r="3"/>
    </svg>
    <svg class="password-eye password-eye-closed" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M3 5 21 19M10.2 6.2A10.8 10.8 0 0 1 12 6c6.2 0 9.8 6 9.8 6a17.2 17.2 0 0 1-3 3.6M14.1 14.2A3 3 0 0 1 9.8 10M6.1 8.3A17.8 17.8 0 0 0 2.2 12s3.6 6 9.8 6c1.1 0 2.1-.2 3-.5"/>
    </svg>
</button>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-password-toggle]').forEach(button => {
                button.addEventListener('click', () => {
                    const input = document.getElementById(button.dataset.passwordTarget);
                    const showPassword = input.type === 'password';
                    input.type = showPassword ? 'text' : 'password';
                    button.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
                    button.setAttribute('aria-label', showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                    button.setAttribute('title', showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                });
            });
        </script>
    @endpush
@endonce
