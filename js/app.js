/*
 * VISRS Global JavaScript
 *
 * Shared JavaScript functionality used throughout
 * the Vehicle Information Search & Retrieval System.
 */

document.addEventListener("DOMContentLoaded", function () {

    /*
     * Prevent forms from being submitted multiple times.
     *
     * Once a form is submitted, the submit button is disabled
     * so the user cannot accidentally create duplicate records.
     */

    const forms = document.querySelectorAll("form");

    forms.forEach(function (form) {

        form.addEventListener("submit", function () {

            const submitButtons = form.querySelectorAll(
                'button[type="submit"], input[type="submit"]'
            );

            submitButtons.forEach(function (button) {

                if (!button.disabled) {

                    button.disabled = true;

                    /*
                     * Keep the original button text available
                     * so it can be restored if necessary.
                     */

                    if (!button.dataset.originalText) {
                        button.dataset.originalText = button.innerHTML;
                    }

                    /*
                     * Do not change delete button wording.
                     * Keep the existing VISRS design.
                     */

                    if (
                        !button.classList.contains("delete-button") &&
                        !button.textContent.toLowerCase().includes("delete")
                    ) {
                        button.innerHTML = "Processing...";
                    }

                }

            });

        });

    });


    /*
     * Automatically remove temporary alert messages.
     *
     * Any element using the .auto-dismiss class will
     * disappear after a few seconds.
     */

    const alerts = document.querySelectorAll(".auto-dismiss");

    alerts.forEach(function (alert) {

        setTimeout(function () {

            alert.style.opacity = "0";

            setTimeout(function () {
                alert.remove();
            }, 300);

        }, 4000);

    });


    /*
     * Confirmation buttons.
     *
     * Any link or button with the class .confirm-action
     * will ask the user for confirmation before continuing.
     */

    const confirmationElements = document.querySelectorAll(
        ".confirm-action"
    );

    confirmationElements.forEach(function (element) {

        element.addEventListener("click", function (event) {

            const message =
                element.dataset.confirmMessage ||
                "Are you sure you want to continue?";

            if (!confirm(message)) {
                event.preventDefault();
            }

        });

    });


    /*
     * Automatically focus the first field marked
     * with the .auto-focus class.
     */

    const autoFocusElement =
        document.querySelector(".auto-focus");

    if (autoFocusElement) {
        autoFocusElement.focus();
    }


    /*
     * Password visibility toggle.
     *
     * Any button using:
     *
     * data-toggle-password="input-id"
     *
     * can show/hide the corresponding password field.
     */

    const passwordToggles = document.querySelectorAll(
        "[data-toggle-password]"
    );

    passwordToggles.forEach(function (button) {

        button.addEventListener("click", function () {

            const inputId =
                button.getAttribute("data-toggle-password");

            const passwordInput =
                document.getElementById(inputId);

            if (!passwordInput) {
                return;
            }

            if (passwordInput.type === "password") {

                passwordInput.type = "text";

                button.textContent = "Hide";

            } else {

                passwordInput.type = "password";

                button.textContent = "Show";

            }

        });

    });


    /*
     * Character counter.
     *
     * Any textarea using:
     *
     * data-character-count
     *
     * will display its current character count.
     */

    const characterCountFields =
        document.querySelectorAll("[data-character-count]");

    characterCountFields.forEach(function (field) {

        const counterId =
            field.getAttribute("data-character-count");

        const counter =
            document.getElementById(counterId);

        if (!counter) {
            return;
        }

        function updateCounter() {

            counter.textContent =
                field.value.length + " characters";

        }

        field.addEventListener("input", updateCounter);

        updateCounter();

    });


    /*
     * Allow elements to be hidden/shown based on
     * another field's value.
     *
     * Example:
     *
     * data-toggle-target="insurance-fields"
     *
     * data-toggle-value="Active"
     *
     * This will be useful for future dynamic forms.
     */

    const toggleElements =
        document.querySelectorAll("[data-toggle-target]");

    toggleElements.forEach(function (element) {

        const targetId =
            element.getAttribute("data-toggle-target");

        const target =
            document.getElementById(targetId);

        if (!target) {
            return;
        }

        function updateVisibility() {

            const expectedValue =
                element.getAttribute("data-toggle-value");

            if (
                expectedValue === null ||
                element.value === expectedValue
            ) {

                target.style.display = "";

            } else {

                target.style.display = "none";

            }

        }

        element.addEventListener(
            "change",
            updateVisibility
        );

        updateVisibility();

    });


    /*
     * Basic table search.
     *
     * Tables using the .searchable-table class can be
     * filtered by an input using:
     *
     * data-table-search="table-id"
     */

    const tableSearchInputs =
        document.querySelectorAll("[data-table-search]");

    tableSearchInputs.forEach(function (input) {

        const tableId =
            input.getAttribute("data-table-search");

        const table =
            document.getElementById(tableId);

        if (!table) {
            return;
        }

        input.addEventListener("input", function () {

            const searchValue =
                input.value.toLowerCase().trim();

            const rows =
                table.querySelectorAll("tbody tr");

            rows.forEach(function (row) {

                const rowText =
                    row.textContent.toLowerCase();

                if (rowText.includes(searchValue)) {

                    row.style.display = "";

                } else {

                    row.style.display = "none";

                }

            });

        });

    });


    /*
     * Add a small timestamp to the browser console.
     *
     * This is useful during development and testing.
     */

    console.log(
        "VISRS JavaScript loaded successfully."
    );

});