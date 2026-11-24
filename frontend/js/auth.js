document.querySelectorAll("[data-password-toggle]").forEach((toggle) => {
    toggle.addEventListener("click", () => {
        const input = document.getElementById(toggle.getAttribute("aria-controls"));
        if (!input) return;

        const showPassword = input.type === "password";
        input.type = showPassword ? "text" : "password";
        toggle.textContent = showPassword ? "Hide" : "Show";
        toggle.setAttribute("aria-label", `${showPassword ? "Hide" : "Show"} password`);
    });
});

document.querySelectorAll("[data-dialog-open]").forEach((trigger) => {
    trigger.addEventListener("click", (event) => {
        event.preventDefault();
        document.getElementById(trigger.dataset.dialogOpen)?.showModal();
    });
});

document.querySelectorAll("[data-dialog-close]").forEach((button) => {
    button.addEventListener("click", () => button.closest("dialog")?.close());
});

document.querySelectorAll(".auth-dialog").forEach((dialog) => {
    dialog.addEventListener("click", (event) => {
        if (event.target === dialog) dialog.close();
    });
});

document.querySelectorAll("form[data-loading-form]").forEach((form) => {
    form.addEventListener("submit", (event) => {
        if (event.defaultPrevented || form.dataset.submitting === "true") {
            event.preventDefault();
            return;
        }

        form.dataset.submitting = "true";
        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
            button.setAttribute("aria-busy", "true");
            button.classList.add("is-loading");
            button.dataset.originalText = button.textContent;
            button.textContent = button.dataset.loadingText || "Processing...";
        }
    });
});

const registerForm = document.querySelector('[data-auth-form="register"]');

if (registerForm) {
    const password = registerForm.elements.password;
    const confirmPassword = registerForm.elements.confirm_password;

    const validatePasswordMatch = () => {
        confirmPassword.setCustomValidity(
            confirmPassword.value && confirmPassword.value !== password.value
                ? "Passwords do not match."
                : ""
        );
    };

    password.addEventListener("input", validatePasswordMatch);
    confirmPassword.addEventListener("input", validatePasswordMatch);
}
