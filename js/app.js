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
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach(function (button) {
                button.disabled = true;
                const buttonText = (button.textContent || button.value || "").trim().toLowerCase();
                if (!buttonText.includes("delete") && !buttonText.includes("remove")) {
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
            const message = element.getAttribute("data-confirm") || "Are you sure you want to continue?";
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
    const autofocusElement = document.querySelector("[data-autofocus]");
    if (autofocusElement) {
        autofocusElement.focus();
    }

    /*
    |--------------------------------------------------------------------------
    | PASSWORD VISIBILITY TOGGLE
    |--------------------------------------------------------------------------
    */
    document.querySelectorAll("[data-password-toggle]").forEach(function (button) {
        button.addEventListener("click", function () {
            const targetId = button.getAttribute("data-password-toggle");
            const input = document.getElementById(targetId);
            if (!input) return;
            if (input.type === "password") {
                input.type = "text";
                button.textContent = "Hide";
            } else {
                input.type = "password";
                button.textContent = "Show";
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | CHARACTER COUNTERS
    |--------------------------------------------------------------------------
    */
    document.querySelectorAll("[data-character-counter]").forEach(function (counter) {
        const targetId = counter.getAttribute("data-character-counter");
        const input = document.getElementById(targetId);
        if (!input) return;

        function updateCounter() {
            const currentLength = input.value.length;
            const maxLength = input.getAttribute("maxlength");
            if (maxLength) {
                counter.textContent = currentLength + " / " + maxLength;
            } else {
                counter.textContent = currentLength + " characters";
            }
        }

        input.addEventListener("input", updateCounter);
        updateCounter();
    });

    /*
    |--------------------------------------------------------------------------
    | SHOW / HIDE TOGGLE TARGET
    |--------------------------------------------------------------------------
    */
    document.querySelectorAll("[data-toggle-target]").forEach(function (toggle) {
        toggle.addEventListener("click", function () {
            const targetId = toggle.getAttribute("data-toggle-target");
            const target = document.getElementById(targetId);
            if (!target) return;
            if (target.style.display === "none" || getComputedStyle(target).display === "none") {
                target.style.display = "";
            } else {
                target.style.display = "none";
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | TABLE SEARCH
    |--------------------------------------------------------------------------
    */
    document.querySelectorAll("[data-table-search]").forEach(function (searchInput) {
        const tableId = searchInput.getAttribute("data-table-search");
        const table = document.getElementById(tableId);
        if (!table) return;

        const rows = table.querySelectorAll("tbody tr");
        searchInput.addEventListener("input", function () {
            const searchTerm = searchInput.value.toLowerCase().trim();
            rows.forEach(function (row) {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(searchTerm) ? "" : "none";
            });
        });
    });

});
