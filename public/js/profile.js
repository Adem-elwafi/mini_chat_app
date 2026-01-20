(() => {
    const userData = window.profileUser || {};
    const state = {
        initial: {
            username: userData.username || '',
            email: userData.email || '',
            profile_info: userData.profile_info || ''
        }
    };

    const form = document.getElementById('profileForm');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const bioInput = document.getElementById('profileInfo');
    const bioCounter = document.getElementById('bioCounter');
    const resetBtn = document.getElementById('resetBtn');
    const successAlert = document.getElementById('successAlert');
    const errorAlert = document.getElementById('errorAlert');
    const displayName = document.getElementById('displayName');
    const displayEmail = document.getElementById('displayEmail');
    const miniName = document.getElementById('miniName');
    const miniEmail = document.getElementById('miniEmail');
    const avatarNodes = document.querySelectorAll('[data-avatar-initial]');

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function updateAvatar(initial) {
        avatarNodes.forEach((node) => {
            node.textContent = initial;
        });
    }

    function updateCounters() {
        if (!bioCounter || !bioInput) return;
        const length = bioInput.value.length;
        bioCounter.textContent = `${length}/240`;
    }

    function applyUserToUI(user) {
        if (displayName) displayName.textContent = user.username;
        if (displayEmail) displayEmail.textContent = user.email;
        if (miniName) miniName.textContent = user.username;
        if (miniEmail) miniEmail.textContent = user.email;
        updateAvatar(user.username ? user.username.charAt(0).toUpperCase() : '?');
    }

    function setFormValues(user) {
        if (usernameInput) usernameInput.value = user.username;
        if (emailInput) emailInput.value = user.email;
        if (bioInput) bioInput.value = user.profile_info || '';
        updateCounters();
    }

    function showAlert(el, message) {
        if (!el) return;
        el.textContent = message;
        el.classList.add('show');
    }

    function hideAlerts() {
        if (successAlert) successAlert.classList.remove('show');
        if (errorAlert) errorAlert.classList.remove('show');
    }

    function persistToLocalStorage(user) {
        try {
            const stored = localStorage.getItem('user');
            const current = stored ? JSON.parse(stored) : {};
            const merged = { ...current, ...user };
            localStorage.setItem('user', JSON.stringify(merged));
        } catch (err) {
            console.warn('Unable to persist user to localStorage:', err);
        }
    }

    function resetForm() {
        hideAlerts();
        setFormValues(state.initial);
        applyUserToUI(state.initial);
    }

    if (bioInput) {
        bioInput.addEventListener('input', updateCounters);
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', resetForm);
    }

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideAlerts();

            const payload = {
                username: usernameInput?.value.trim() || '',
                email: emailInput?.value.trim() || '',
                profile_info: bioInput?.value.trim() || ''
            };

            if (payload.username.length < 3 || payload.username.length > 50) {
                showAlert(errorAlert, 'Username must be between 3 and 50 characters.');
                return;
            }

            if (!emailRegex.test(payload.email)) {
                showAlert(errorAlert, 'Please enter a valid email address.');
                return;
            }

            if (payload.profile_info.length > 240) {
                showAlert(errorAlert, 'Bio cannot exceed 240 characters.');
                return;
            }

            try {
                const response = await fetch('../backend/auth/updateProfile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    const message = data.message || data.error || 'Failed to update profile.';
                    showAlert(errorAlert, message);
                    return;
                }

                state.initial = {
                    username: data.user.username,
                    email: data.user.email,
                    profile_info: data.user.profile_info || ''
                };

                applyUserToUI(state.initial);
                setFormValues(state.initial);
                persistToLocalStorage(data.user);
                showAlert(successAlert, 'Profile saved successfully.');
            } catch (err) {
                console.error('Profile update failed:', err);
                showAlert(errorAlert, 'Network error. Please try again.');
            }
        });
    }

    // Initial render
    resetForm();
})();
