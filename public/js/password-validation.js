function initPasswordValidation(passwordId, confirmPasswordId, passwordHelpId, passwordMatchId, submitButtonId) {
    const passwordInput = document.getElementById(passwordId);
    const confirmPasswordInput = document.getElementById(confirmPasswordId);
    const passwordHelp = document.getElementById(passwordHelpId);
    const passwordMatch = document.getElementById(passwordMatchId);
    const submitButton = document.getElementById(submitButtonId);

    if (!passwordInput || !confirmPasswordInput || !submitButton) return;

    function validatePassword() {
        const password = passwordInput.value.trim();
        const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        const isValid = regex.test(password);
        if (passwordHelp) passwordHelp.style.display = password.length > 0 && !isValid ? 'block' : 'none';
        return isValid;
    }

    function checkPasswordMatch() {
        const match = passwordInput.value === confirmPasswordInput.value;
        if (passwordMatch) passwordMatch.textContent = match ? '' : 'Passwords do not match';
        return match;
    }

    function validateForm() {
        submitButton.disabled = !(validatePassword() && checkPasswordMatch());
    }

    passwordInput.addEventListener('input', validateForm);
    confirmPasswordInput.addEventListener('input', validateForm);
    validateForm();
}
