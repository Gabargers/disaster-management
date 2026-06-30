document.addEventListener("DOMContentLoaded", function () {
    const togglePassword = document.querySelector("#togglePassword");
    const password = document.querySelector("#password");

    if (togglePassword && password) {
        const icon = togglePassword.querySelector("i");

        togglePassword.addEventListener("click", function () {
            const type =
                password.getAttribute("type") === "password"
                    ? "text"
                    : "password";
            password.setAttribute("type", type);

            icon.classList.toggle("fa-eye");
            icon.classList.toggle("fa-eye-slash");
        });
    }
    
    const toggleConfirmPassword = document.querySelector(
        "#toggleConfirmPassword"
    );
    const passwordConfirmation = document.querySelector(
        "#password_confirmation"
    );

    if (toggleConfirmPassword && passwordConfirmation) {
        const iconConfirm = toggleConfirmPassword.querySelector("i");

        toggleConfirmPassword.addEventListener("click", function () {
            const type =
                passwordConfirmation.getAttribute("type") === "password"
                    ? "text"
                    : "password";
            passwordConfirmation.setAttribute("type", type);

            iconConfirm.classList.toggle("fa-eye");
            iconConfirm.classList.toggle("fa-eye-slash");
        });
    }
});
