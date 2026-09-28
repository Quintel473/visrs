document.addEventListener("DOMContentLoaded", function () {

    /*
    |--------------------------------------------------------------------------
    | VISRS GLOBAL JAVASCRIPT
    |--------------------------------------------------------------------------
    */

    console.log("VISRS JavaScript loaded successfully.");


    /*
    |--------------------------------------------------------------------------
    | FORM SUBMISSION PROTECTION
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("form").forEach(function (form) {

        form.addEventListener("submit", function () {

            const submitButtons = form.querySelectorAll(
                'button[type="submit"], input[type="submit"]'
            );

            submitButtons.forEach(function (button) {

                button.disabled = true;

                /*
                 * Do not change delete button text because
                 * VISRS uses dedicated delete confirmation pages.
                 */
                const buttonText = (
                    button.textContent ||
                    button.value ||
                    ""
                ).trim().toLowerCase();

                if (
                    !buttonText.includes("delete") &&
                    !buttonText.includes("remove")
                ) {

                    if (button.tagName.toLowerCase() === "button") {
                        button.textContent = "Processing...";
                    } else {
                        button.value = "Processing...";
                    }

                }

            });

        });

    });


    /*
    |--------------------------------------------------------------------------
    | AUTO DISMISS ALERTS
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll(".auto-dismiss").forEach(function (alert) {

        setTimeout(function () {

            alert.style.opacity = "0";

            setTimeout(function () {
                alert.remove();
            }, 400);

        }, 4000);

    });


    /*
    |--------------------------------------------------------------------------
    | CONFIRM ACTIONS
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll(".confirm-action").forEach(function (element) {

        element.addEventListener("click", function (event) {

            const message =
                element.getAttribute("data-confirm") ||
                "Are you sure you want to continue?";

            if (!window.confirm(message)) {
                event.preventDefault();
            }

        });

    });


    /*
    |--------------------------------------------------------------------------
    | AUTO FOCUS
    |--------------------------------------------------------------------------
    */

    const autofocusElement =
        document.querySelector("[data-autofocus]");

    if (autofocusElement) {
        autofocusElement.focus();
    }


    /*
    |--------------------------------------------------------------------------
    | PASSWORD VISIBILITY TOGGLE
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-password-toggle]").forEach(
        function (button) {

            button.addEventListener("click", function () {

                const targetId =
                    button.getAttribute("data-password-toggle");

                const input =
                    document.getElementById(targetId);

                if (!input) {
                    return;
                }

                if (input.type === "password") {

                    input.type = "text";
                    button.textContent = "Hide";

                } else {

                    input.type = "password";
                    button.textContent = "Show";

                }

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CHARACTER COUNTERS
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-character-counter]").forEach(
        function (counter) {

            const targetId =
                counter.getAttribute("data-character-counter");

            const input =
                document.getElementById(targetId);

            if (!input) {
                return;
            }

            function updateCounter() {

                const currentLength =
                    input.value.length;

                const maxLength =
                    input.getAttribute("maxlength");

                if (maxLength) {

                    counter.textContent =
                        currentLength + " / " + maxLength;

                } else {

                    counter.textContent =
                        currentLength + " characters";

                }

            }

            input.addEventListener(
                "input",
                updateCounter
            );

            updateCounter();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | SHOW / HIDE TOGGLE TARGET
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-toggle-target]").forEach(
        function (toggle) {

            toggle.addEventListener("click", function () {

                const targetId =
                    toggle.getAttribute("data-toggle-target");

                const target =
                    document.getElementById(targetId);

                if (!target) {
                    return;
                }

                if (
                    target.style.display === "none" ||
                    getComputedStyle(target).display === "none"
                ) {

                    target.style.display = "";

                } else {

                    target.style.display = "none";

                }

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | TABLE SEARCH
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-table-search]").forEach(
        function (searchInput) {

            const tableId =
                searchInput.getAttribute("data-table-search");

            const table =
                document.getElementById(tableId);

            if (!table) {
                return;
            }

            const rows =
                table.querySelectorAll("tbody tr");

            searchInput.addEventListener("input", function () {

                const searchTerm =
                    searchInput.value
                        .toLowerCase()
                        .trim();

                rows.forEach(function (row) {

                    const rowText =
                        row.textContent.toLowerCase();

                    if (rowText.includes(searchTerm)) {

                        row.style.display = "";

                    } else {

                        row.style.display = "none";

                    }

                });

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | VISRS ACCESSIBILITY
    |--------------------------------------------------------------------------
    */

    const accessibilityToggle =
        document.getElementById(
            "visrsAccessibilityToggle"
        );

    const accessibilityPanel =
        document.getElementById(
            "visrsAccessibilityPanel"
        );

    const dyslexiaToggle =
        document.getElementById(
            "visrsDyslexiaToggle"
        );

    const dyslexiaStatus =
        document.getElementById(
            "visrsDyslexiaStatus"
        );

    const accessibilityReset =
        document.getElementById(
            "visrsAccessibilityReset"
        );


    /*
    |--------------------------------------------------------------------------
    | ACCESSIBILITY STORAGE KEYS
    |--------------------------------------------------------------------------
    */

    const DYSLEXIA_STORAGE_KEY =
        "visrs_accessibility_dyslexia";


    /*
    |--------------------------------------------------------------------------
    | UPDATE DYSLEXIA MODE
    |--------------------------------------------------------------------------
    */

    function updateDyslexiaMode(
        enabled,
        savePreference = true
    ) {

        document.documentElement.classList.toggle(
            "visrs-dyslexia",
            enabled
        );


        /*
         * Update button state
         */
        if (dyslexiaToggle) {

            dyslexiaToggle.setAttribute(
                "aria-pressed",
                enabled ? "true" : "false"
            );

            dyslexiaToggle.classList.toggle(
                "is-active",
                enabled
            );

        }


        /*
         * Update status text
         */
        if (dyslexiaStatus) {

            dyslexiaStatus.textContent =
                enabled ? "On" : "Off";

        }


        /*
         * Save preference
         */
        if (savePreference) {

            try {

                localStorage.setItem(
                    DYSLEXIA_STORAGE_KEY,
                    enabled ? "on" : "off"
                );

            } catch (error) {

                console.warn(
                    "VISRS could not save accessibility preference.",
                    error
                );

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | LOAD SAVED DYSLEXIA PREFERENCE
    |--------------------------------------------------------------------------
    */

    let savedDyslexiaPreference = "off";

    try {

        savedDyslexiaPreference =
            localStorage.getItem(
                DYSLEXIA_STORAGE_KEY
            ) || "off";

    } catch (error) {

        console.warn(
            "VISRS could not read accessibility preference.",
            error
        );

    }


    updateDyslexiaMode(
        savedDyslexiaPreference === "on",
        false
    );


    /*
    |--------------------------------------------------------------------------
    | OPEN / CLOSE ACCESSIBILITY PANEL
    |--------------------------------------------------------------------------
    */

    function openAccessibilityPanel() {

        if (!accessibilityPanel) {
            return;
        }

        accessibilityPanel.classList.add(
            "is-open"
        );

        accessibilityPanel.setAttribute(
            "aria-hidden",
            "false"
        );

        if (accessibilityToggle) {

            accessibilityToggle.setAttribute(
                "aria-expanded",
                "true"
            );

            accessibilityToggle.setAttribute(
                "aria-label",
                "Close accessibility options"
            );

        }

    }


    function closeAccessibilityPanel() {

        if (!accessibilityPanel) {
            return;
        }

        accessibilityPanel.classList.remove(
            "is-open"
        );

        accessibilityPanel.setAttribute(
            "aria-hidden",
            "true"
        );

        if (accessibilityToggle) {

            accessibilityToggle.setAttribute(
                "aria-expanded",
                "false"
            );

            accessibilityToggle.setAttribute(
                "aria-label",
                "Open accessibility options"
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | ACCESSIBILITY BUTTON
    |--------------------------------------------------------------------------
    */

    if (accessibilityToggle) {

        accessibilityToggle.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

                if (
                    accessibilityPanel &&
                    accessibilityPanel.classList.contains("is-open")
                ) {

                    closeAccessibilityPanel();

                } else {

                    openAccessibilityPanel();

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | DYSLEXIA TOGGLE
    |--------------------------------------------------------------------------
    */

    if (dyslexiaToggle) {

        dyslexiaToggle.addEventListener(
            "click",
            function () {

                const currentlyEnabled =
                    document.documentElement.classList.contains(
                        "visrs-dyslexia"
                    );

                updateDyslexiaMode(
                    !currentlyEnabled,
                    true
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | RESET ACCESSIBILITY
    |--------------------------------------------------------------------------
    */

    if (accessibilityReset) {

        accessibilityReset.addEventListener(
            "click",
            function () {

                updateDyslexiaMode(
                    false,
                    true
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE ACCESSIBILITY PANEL WHEN CLICKING OUTSIDE
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "click",
        function (event) {

            if (!accessibilityPanel) {
                return;
            }

            if (!accessibilityPanel.classList.contains("is-open")) {
                return;
            }

            const accessibilityContainer =
                document.querySelector(
                    ".visrs-accessibility"
                );

            if (
                accessibilityContainer &&
                !accessibilityContainer.contains(event.target)
            ) {

                closeAccessibilityPanel();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | ESCAPE KEY
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "keydown",
        function (event) {

            if (event.key === "Escape") {

                closeAccessibilityPanel();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | ACCESSIBILITY READY
    |--------------------------------------------------------------------------
    */

    console.log(
        "VISRS accessibility features loaded."
    );

});