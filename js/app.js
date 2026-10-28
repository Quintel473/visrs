document.addEventListener("DOMContentLoaded", function () {

    /*
    |--------------------------------------------------------------------------
    | VISRS GLOBAL JAVASCRIPT
    |--------------------------------------------------------------------------
    */

    console.log("VISRS JavaScript loaded successfully.");


    /* =====================================================
       SUBMIT OVERLAY
       ---------------------------------------------------------
       Shows a full-screen spinner while a form is submitting.
       Skipped on forms flagged with data-no-overlay.

       Includes an 8-second safety timeout: if the page hasn't
       navigated by then (cancelled submit, slow server, target
       _blank, etc.) the overlay removes itself so the user
       isn't stuck behind it.
       ===================================================== */

    function showSubmitOverlay() {

        if (document.getElementById("visrs-submit-overlay")) {
            return;
        }

        var overlay = document.createElement("div");
        overlay.id = "visrs-submit-overlay";
        overlay.className = "submit-overlay";

        overlay.innerHTML =
            '<div class="submit-overlay-inner">' +
            '  <div class="submit-spinner" aria-hidden="true"></div>' +
            '  <p class="submit-overlay-text">Processing…</p>' +
            '</div>';

        document.body.appendChild(overlay);

        /*
         * Safety net — remove the overlay after 8 seconds if
         * the page hasn't navigated. Handles edge cases like
         * cancelled confirm dialogs, failed submissions, and
         * forms targeting new tabs.
         */
        setTimeout(function () {
            var stuck = document.getElementById("visrs-submit-overlay");
            if (stuck) {
                stuck.remove();
            }
        }, 8000);
    }


    /*
    |--------------------------------------------------------------------------
    | FORM SUBMISSION PROTECTION
    |--------------------------------------------------------------------------
    |
    | Disables submit buttons to prevent double-submits, but
    | SKIPS the button that actually triggered the submit.
    |
    | Disabling the triggering button causes the browser to
    | drop its name/value pair from the submitted form data.
    | This is why a button with name="action" value="X" would
    | silently lose its value if it was disabled before the
    | form finalized.
    */

    document.querySelectorAll("form").forEach(function (form) {

        form.addEventListener("submit", function (event) {

            /*
             * Identify the button that triggered the submit.
             * We must NOT disable this one.
             */
            var clickedButton = event.submitter || null;

            const submitButtons = form.querySelectorAll(
                'button[type="submit"], input[type="submit"]'
            );

            submitButtons.forEach(function (button) {

                /*
                 * Skip the submitter — its value must survive.
                 */
                if (clickedButton && button === clickedButton) {
                    return;
                }

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

            /* Show full-page overlay (opt out with data-no-overlay) */
            if (!form.hasAttribute("data-no-overlay")) {
                showSubmitOverlay();
            }

        });

    });


    /*
    |--------------------------------------------------------------------------
    | RESTORE SUBMIT BUTTONS ON BFCACHE RESTORE
    |--------------------------------------------------------------------------
    | When the user navigates back to a form (e.g., after a failed POST),
    | some browsers restore the page from the back/forward cache with
    | buttons still disabled and text stuck at "Processing...".
    | This listener undoes that on bfcache restore.
    */

    window.addEventListener("pageshow", function (event) {

        if (!event.persisted) return;

        document.querySelectorAll(
            'form button[type="submit"], form input[type="submit"]'
        ).forEach(function (button) {

            button.disabled = false;

            const buttonText = (
                button.textContent ||
                button.value ||
                ""
            ).trim().toLowerCase();

            if (
                buttonText === "processing..." ||
                buttonText === ""
            ) {

                /*
                 * Cannot know the original label safely, so only
                 * clear the "Processing..." state if the label
                 * still matches. Otherwise leave as-is.
                 *
                 * Most submissions re-render server-side and
                 * never hit this path.
                 */

                if (button.tagName.toLowerCase() === "button") {
                    button.textContent = "Submit";
                } else {
                    button.value = "Submit";
                }

            }

        });

        /* Also remove any stuck submit overlay */
        var overlay = document.getElementById("visrs-submit-overlay");
        if (overlay) {
            overlay.remove();
        }

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
        document.querySelector(".auto-focus");

    if (autofocusElement) {
        autofocusElement.focus();
    }


    /*
    |--------------------------------------------------------------------------
    | PASSWORD VISIBILITY TOGGLE
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-toggle-password]").forEach(
        function (button) {

            button.addEventListener("click", function () {

                const targetId =
                    button.getAttribute("data-toggle-password");

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


});

/* =========================================================
   VISRS Dark Mode Toggle
   ========================================================= */
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    onReady(function () {
        var btn  = document.getElementById('theme-toggle');
        var icon = btn ? btn.querySelector('.theme-icon') : null;

        if (!btn || !icon) {
            return;
        }

        function syncButton(theme) {
            var isDark = theme === 'dark';

            btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            btn.setAttribute(
                'aria-label',
                isDark ? 'Switch to light mode' : 'Switch to dark mode'
            );
            btn.setAttribute(
                'title',
                isDark ? 'Switch to light mode' : 'Switch to dark mode'
            );
            icon.textContent = isDark ? '☀️' : '🌙';
        }

        /* Sync with what the head script already applied */
        var current = document.documentElement.getAttribute('data-theme') || 'light';
        syncButton(current);

        /* Toggle on click */
        btn.addEventListener('click', function () {
            var next = document.documentElement.getAttribute('data-theme') === 'dark'
                ? 'light'
                : 'dark';

            document.documentElement.setAttribute('data-theme', next);

            try {
                localStorage.setItem('visrs-theme', next);
            } catch (e) {
                /* localStorage may be blocked; fail silently */
            }

            syncButton(next);
        });

        /* Keep in sync if user changes OS preference and has no saved choice */
        try {
            var mq = window.matchMedia('(prefers-color-scheme: dark)');
            mq.addEventListener('change', function (e) {
                if (localStorage.getItem('visrs-theme')) return;
                var t = e.matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', t);
                syncButton(t);
            });
        } catch (e) {}
    });
})();

/* =========================================================
   VISRS Sidebar Collapse Toggle
   ========================================================= */
(function () {
    'use strict';

    function init() {

        var toggleBtn = document.getElementById('sidebar-toggle');

        if (!toggleBtn) return;

        function setCollapsed(collapsed) {

            if (collapsed) {
                document.documentElement.setAttribute('data-sidebar', 'collapsed');
                toggleBtn.setAttribute('aria-pressed', 'true');
                toggleBtn.setAttribute('title', 'Expand sidebar');
                try { localStorage.setItem('visrs-sidebar', 'collapsed'); } catch (e) {}
            } else {
                document.documentElement.removeAttribute('data-sidebar');
                toggleBtn.setAttribute('aria-pressed', 'false');
                toggleBtn.setAttribute('title', 'Collapse sidebar');
                try { localStorage.setItem('visrs-sidebar', 'expanded'); } catch (e) {}
            }
        }

        /* Restore saved state on load */
        var saved = 'expanded';
        try {
            saved = localStorage.getItem('visrs-sidebar') || 'expanded';
        } catch (e) {}

        setCollapsed(saved === 'collapsed');

        /* Toggle on click */
        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var isCollapsed = document.documentElement.getAttribute('data-sidebar') === 'collapsed';
            setCollapsed(!isCollapsed);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

/* =========================================================
   VISRS Multi-Language (i18n) — Auto-Translate
   ========================================================= */
(function () {
    'use strict';

    var SUPPORTED = ['en', 'es', 'fr', 'ja'];
    var DEFAULT_LANG = 'en';
    var STORAGE_KEY = 'visrs-lang';

    var translations = {};
    var reverseDict = {};
    var currentLang = DEFAULT_LANG;

    var originalTextMap = new WeakMap();

    /* ---------- Storage helpers ---------- */

    function getSavedLang() {
        try {
            var saved = localStorage.getItem(STORAGE_KEY);
            if (saved && SUPPORTED.indexOf(saved) !== -1) return saved;
        } catch (e) {}
        return DEFAULT_LANG;
    }

    function saveLang(lang) {
        try { localStorage.setItem(STORAGE_KEY, lang); } catch (e) {}
    }

    /* ---------- Path helper ---------- */

    function langPath(lang) {
        return '/visrs/lang/' + lang + '.json';
    }

    /* ---------- Translation core ---------- */

    function translateAutoContainers() {

        var containers = document.querySelectorAll('[data-i18n-auto]');

        containers.forEach(function (container) {

            var walker = document.createTreeWalker(
                container,
                NodeFilter.SHOW_TEXT,
                {
                    acceptNode: function (node) {
                        if (!node.nodeValue || !node.nodeValue.trim()) {
                            return NodeFilter.FILTER_REJECT;
                        }
                        var parent = node.parentNode;
                        if (parent) {
                            var tag = parent.nodeName;
                            if (tag === 'SCRIPT' || tag === 'STYLE') {
                                return NodeFilter.FILTER_REJECT;
                            }
                        }
                        return NodeFilter.FILTER_ACCEPT;
                    }
                }
            );

            var node;
            while ((node = walker.nextNode())) {

                var original = originalTextMap.get(node);
                if (original === undefined) {
                    original = node.nodeValue;
                    originalTextMap.set(node, original);
                }

                var trimmed = original.trim();
                if (trimmed === '') continue;

                if (currentLang === DEFAULT_LANG) {
                    if (node.nodeValue !== original) {
                        node.nodeValue = original;
                    }
                    continue;
                }

                var translated = reverseDict[trimmed];

                if (translated !== undefined) {
                    var leading = original.match(/^\s*/)[0];
                    var trailing = original.match(/\s*$/)[0];
                    node.nodeValue = leading + translated + trailing;
                } else {
                    if (node.nodeValue !== original) {
                        node.nodeValue = original;
                    }
                }
            }
        });
    }

    function applyKeyedTranslations() {

        var textNodes = document.querySelectorAll('[data-i18n]');
        textNodes.forEach(function (el) {
            var key = el.getAttribute('data-i18n');
            if (translations[key] !== undefined) {
                el.textContent = translations[key];
            }
        });

        var placeholders = document.querySelectorAll('[data-i18n-placeholder]');
        placeholders.forEach(function (el) {
            var key = el.getAttribute('data-i18n-placeholder');
            if (translations[key] !== undefined) {
                el.setAttribute('placeholder', translations[key]);
            }
        });

        var titles = document.querySelectorAll('[data-i18n-title]');
        titles.forEach(function (el) {
            var key = el.getAttribute('data-i18n-title');
            if (translations[key] !== undefined) {
                el.setAttribute('title', translations[key]);
            }
        });
    }

    function applyTranslations() {

        document.documentElement.classList.add('i18n-changing');

        document.documentElement.setAttribute('lang', currentLang);

        applyKeyedTranslations();
        translateAutoContainers();

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                document.documentElement.classList.remove('i18n-changing');
            });
        });
    }

    /* ---------- Loading ---------- */

    function loadLanguage(lang) {

        if (SUPPORTED.indexOf(lang) === -1) lang = DEFAULT_LANG;

        fetch(langPath(lang), { cache: 'no-cache' })
            .then(function (res) {
                if (!res.ok) throw new Error('Could not load ' + lang);
                return res.json();
            })
            .then(function (data) {

                translations = data;
                reverseDict = data.__reverse || {};
                currentLang = lang;
                saveLang(lang);

                applyTranslations();
                updateSwitcherUI(lang);
            })
            .catch(function (err) {
                console.warn('[VISRS i18n]', err.message);
                if (lang !== DEFAULT_LANG) {
                    loadLanguage(DEFAULT_LANG);
                }
            });
    }

    /* ---------- Switcher UI ---------- */

    function updateSwitcherUI(lang) {

        var currentLabel = document.getElementById('language-current');
        if (currentLabel) {
            currentLabel.textContent = lang.toUpperCase();
        }

        var options = document.querySelectorAll('.language-option');
        options.forEach(function (btn) {
            if (btn.getAttribute('data-lang') === lang) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    /* ---------- Dropdown wiring ---------- */

    function initSwitcher() {

        var toggle = document.getElementById('language-toggle');
        var menu = document.getElementById('language-menu');
        var switcher = document.getElementById('language-switcher');

        if (!toggle || !menu || !switcher) return;

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = menu.classList.toggle('open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        var options = menu.querySelectorAll('.language-option');
        options.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var lang = btn.getAttribute('data-lang');
                if (lang) {
                    loadLanguage(lang);
                    menu.classList.remove('open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        });

        document.addEventListener('click', function (e) {
            if (!switcher.contains(e.target)) {
                menu.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                menu.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* ---------- Auto-tagging fallback ---------- */

    function autoTagContentAreas() {

        var candidates = [
            'main.content',
            '.content',
            '.card',
            '.page-title',
            '.login-page'
        ];

        candidates.forEach(function (selector) {
            document.querySelectorAll(selector).forEach(function (el) {
                if (!el.hasAttribute('data-i18n-auto')) {
                    el.setAttribute('data-i18n-auto', '');
                }
            });
        });
    }

    /* ---------- Boot ---------- */

    function init() {
        autoTagContentAreas();
        initSwitcher();
        loadLanguage(getSavedLang());
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

/* =========================================================
   VISRS Bulk Selection
   ========================================================= */
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    onReady(function () {

        var selectAll    = document.getElementById('selectAll');
        var rowCheckboxes = document.querySelectorAll('.row-checkbox');
        var actionBar    = document.getElementById('bulkActionBar');
        var selectedCount = document.getElementById('bulkSelectedCount');
        var clearBtn     = document.getElementById('bulkClearSelection');

        if (!rowCheckboxes.length) return;

        function updateBar() {

            var checked = document.querySelectorAll('.row-checkbox:checked').length;

            if (selectedCount) {
                selectedCount.textContent = checked;
            }

            if (actionBar) {
                if (checked > 0) {
                    actionBar.classList.add('visible');
                } else {
                    actionBar.classList.remove('visible');
                }
            }

            if (selectAll) {
                var total = rowCheckboxes.length;
                if (checked === 0) {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                } else if (checked === total) {
                    selectAll.checked = true;
                    selectAll.indeterminate = false;
                } else {
                    selectAll.checked = false;
                    selectAll.indeterminate = true;
                }
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                var isChecked = selectAll.checked;
                rowCheckboxes.forEach(function (cb) {
                    cb.checked = isChecked;
                });
                updateBar();
            });
        }

        rowCheckboxes.forEach(function (cb) {
            cb.addEventListener('change', updateBar);
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                rowCheckboxes.forEach(function (cb) {
                    cb.checked = false;
                });
                if (selectAll) {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                }
                updateBar();
            });
        }

        updateBar();
    });
})();

/* =========================================================
   VISRS Notification Badge Polling
   ========================================================= */
(function () {
    'use strict';

    var REFRESH_INTERVAL = 60000; // 60 seconds

    function init() {

        var badge = document.getElementById('notifications-badge');
        if (!badge) return;

        var stopped = false;
        var firstLoadDone = false;

        /* Show loading state only on the very first fetch */
        function markLoading() {

            if (firstLoadDone) return;

            badge.classList.add('is-loading');
            badge.textContent = '';
            badge.style.display = '';
        }

        function markLoaded() {

            firstLoadDone = true;
            badge.classList.remove('is-loading');
        }

        /* Fetch unread count via a lightweight endpoint */
        function refresh() {

            return fetch('/visrs/notifications/unread_count.php', {
                cache: 'no-store'
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {

                    markLoaded();

                    if (typeof data.unread !== 'number') return;

                    if (data.unread > 0) {
                        badge.textContent = data.unread;
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(function () {
                    /* On error, also clear loading so it doesn't pulse forever */
                    markLoaded();
                });
        }

        /* Recursive setTimeout — schedules the next poll only
           after the previous one finishes, avoiding request
           pile-up if the server is slow. */

        function loop() {

            if (stopped) return;

            refresh().then(function () {
                if (stopped) return;
                setTimeout(loop, REFRESH_INTERVAL);
            });
        }

        /* Show the loading dot immediately on page load */
        markLoading();

        /* Kick off the first fetch right away, then every 60s */
        refresh().then(function () {
            if (!stopped) {
                setTimeout(loop, REFRESH_INTERVAL);
            }
        });

        /* Refresh on page focus (in case another tab made changes) */
        window.addEventListener('focus', refresh);

        /* Stop polling when the page is hidden to save resources,
           resume when visible again. */

        document.addEventListener('visibilitychange', function () {

            if (document.hidden) {
                stopped = true;
            } else {
                if (stopped) {
                    stopped = false;
                    refresh().then(function () {
                        if (!stopped) {
                            setTimeout(loop, REFRESH_INTERVAL);
                        }
                    });
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

/* =========================================================
   VISRS Password Strength Meter
   =========================================================
   Visual feedback while typing. Does NOT replace server-side
   validation — password_policy_check() in PHP remains the
   authority. This is purely UX.

   Hooks onto any input with [data-password-strength].
   The meter is injected below the input on init.
   ========================================================= */
(function () {
    'use strict';

    function init() {

        var inputs = document.querySelectorAll('[data-password-strength]');

        if (!inputs.length) return;

        inputs.forEach(function (input) {

            /* Skip if already initialized */
            if (input.dataset.strengthInit === '1') return;
            input.dataset.strengthInit = '1';

            /* ----- Build the meter ----- */

            var wrapper = document.createElement('div');
            wrapper.className = 'pw-strength';
            wrapper.setAttribute('aria-hidden', 'true');

            var bar = document.createElement('div');
            bar.className = 'pw-strength-bar';

            var fill = document.createElement('div');
            fill.className = 'pw-strength-fill';

            bar.appendChild(fill);
            wrapper.appendChild(bar);

            var label = document.createElement('span');
            label.className = 'pw-strength-label';

            /* Insert right after the input's parent */
            var anchor = input.parentNode;
            anchor.parentNode.insertBefore(wrapper, anchor.nextSibling);
            anchor.parentNode.insertBefore(label, wrapper.nextSibling);

            /* ----- Scoring ----- */

            function scorePassword(pw) {

                var score = 0;

                if (pw.length >= 8)  score++;
                if (pw.length >= 12) score++;
                if (pw.length >= 16) score++;
                if (pw.length >= 20) score++;

                if (/[a-z]/.test(pw)) score++;
                if (/[A-Z]/.test(pw)) score++;
                if (/[0-9]/.test(pw)) score++;
                if (/[^A-Za-z0-9]/.test(pw)) score++;

                /* Penalties */
                if (/(.)\1{2,}/.test(pw)) score -= 1;
                if (/^[a-z]+$/.test(pw)) score -= 1;
                if (/^[0-9]+$/.test(pw)) score -= 2;
                if (pw.length < 8) score -= 2;

                return Math.max(0, Math.min(8, score));
            }

            function bandFor(score) {
                if (score <= 2) return { label: 'Weak',   cls: 'pw-strength-weak',   pct: 25 };
                if (score <= 4) return { label: 'Fair',   cls: 'pw-strength-fair',   pct: 50 };
                if (score <= 6) return { label: 'Good',   cls: 'pw-strength-good',   pct: 75 };
                return           { label: 'Strong', cls: 'pw-strength-strong', pct: 100 };
            }

            function update() {

                var pw = input.value;

                if (pw === '') {
                    wrapper.style.display = 'none';
                    label.style.display = 'none';
                    fill.style.width = '0';
                    return;
                }

                wrapper.style.display = '';
                label.style.display = '';

                var score = scorePassword(pw);
                var band = bandFor(score);

                fill.style.width = band.pct + '%';
                fill.className = 'pw-strength-fill ' + band.cls;
                label.textContent = band.label;
                label.className = 'pw-strength-label ' + band.cls;
            }

            input.addEventListener('input', update);
            input.addEventListener('focus', update);

            update();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

/* =========================================================
   VISRS Top Progress Bar
   =========================================================
   Shows a thin progress bar at the top of the viewport
   when a link click triggers navigation. Uses a delay-
   before-show approach: if the new page loads in under
   300ms, no bar appears at all.
   ========================================================= */
(function () {
    'use strict';

    var SHOW_DELAY_MS   = 300;   // wait this long before showing
    var MIN_VISIBLE_MS  = 200;   // bar stays visible this long once shown

    var barEl = null;
    var barInner = null;
    var showTimer = null;
    var visibleAt = 0;
    var isNavigating = false;

    function ensureBar() {

        if (barEl) return;

        barEl = document.createElement('div');
        barEl.className = 'page-progress';

        barInner = document.createElement('div');
        barInner.className = 'page-progress-bar';

        barEl.appendChild(barInner);
        document.body.appendChild(barEl);
    }

    function startProgress() {

        if (isNavigating) return;

        isNavigating = true;

        ensureBar();

        /* Wait before showing — avoids flash on fast pages */
        showTimer = setTimeout(function () {

            barEl.classList.add('is-active');
            barEl.classList.remove('is-complete', 'is-done');

            visibleAt = Date.now();

        }, SHOW_DELAY_MS);
    }

    function finishProgress() {

        /* If the bar was never shown, just bail — page loaded fast */
        if (!barEl || !barEl.classList.contains('is-active')) {
            if (showTimer) {
                clearTimeout(showTimer);
                showTimer = null;
            }
            isNavigating = false;
            return;
        }

        var visibleFor = Date.now() - visibleAt;

        var finish = function () {

            barEl.classList.remove('is-active');
            barEl.classList.add('is-complete');

            /* One frame so the width transition kicks in */
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {

                    barEl.classList.add('is-done');

                    /* Reset for next navigation */
                    setTimeout(function () {
                        barEl.classList.remove('is-complete', 'is-done');
                        barInner.style.width = '0';
                        isNavigating = false;
                    }, 400);
                });
            });
        };

        if (visibleFor < MIN_VISIBLE_MS) {
            /* Keep it on screen at least MIN_VISIBLE_MS so it
               doesn't just flicker */
            setTimeout(finish, MIN_VISIBLE_MS - visibleFor);
        } else {
            finish();
        }
    }

    function isInternalNavigation(anchor) {

        if (!anchor || !anchor.href) return false;

        /* Skip external links */
        if (anchor.origin !== window.location.origin) return false;

        /* Skip hash-only links */
        if (anchor.pathname === window.location.pathname &&
            anchor.search === window.location.search &&
            anchor.hash) {
            return false;
        }

        /* Skip target=_blank */
        if (anchor.target === '_blank') return false;

        /* Skip downloads */
        if (anchor.hasAttribute('download')) return false;

        /* Skip if it's within the same page and same url */
        if (anchor.href === window.location.href) return false;

        /* Skip anything with data-no-progress opt-out */
        if (anchor.hasAttribute('data-no-progress')) return false;

        return true;
    }

    /* ---------- Wire up ---------- */

    function init() {

        document.addEventListener('click', function (e) {

            /* Ignore modified clicks (new tab, etc.) */
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            if (e.button !== 0) return;

            var anchor = e.target.closest('a');
            if (!anchor) return;

            if (!isInternalNavigation(anchor)) return;

            startProgress();
        });

        /* Browser back/forward */
        window.addEventListener('popstate', function () {
            startProgress();
        });

        /* Page finished loading — kill the bar */
        window.addEventListener('pageshow', function () {
            finishProgress();
        });

        window.addEventListener('load', function () {
            finishProgress();
        });

    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

/* =========================================================
   VISRS Animated Number Counters
   ========================================================= */
(function () {
    'use strict';

    function initCounters() {
        document.querySelectorAll('.stat-number[data-target]').forEach(function (el) {
            var target = parseInt(el.getAttribute('data-target'), 10);
            if (isNaN(target)) return;

            var duration = 1000; // 1 second
            var start = null;

            function animateNumber(timestamp) {
                if (!start) start = timestamp;
                var progress = timestamp - start;
                var current = Math.min(progress / duration, 1);
                
                /* Ease out quad */
                var ease = 1 - Math.pow(1 - current, 3);
                var value = Math.floor(ease * target);

                el.textContent = value.toLocaleString();

                if (current < 1) {
                    window.requestAnimationFrame(animateNumber);
                } else {
                    el.textContent = target.toLocaleString();
                }
            }

            window.requestAnimationFrame(animateNumber);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCounters);
    } else {
        initCounters();
    }
})();


/* =========================================================
   VISRS Photo Lightbox
   ========================================================= */
(function () {
    'use strict';

    function initLightbox() {
        var triggers = document.querySelectorAll('.photo-lightbox-trigger');
        var lightbox = document.getElementById('photo-lightbox');
        
        if (!triggers.length || !lightbox) return;

        var imgEl = document.getElementById('lightbox-img');
        var captionEl = document.getElementById('lightbox-caption');
        var btnClose = lightbox.querySelector('.lightbox-close');
        var btnPrev = lightbox.querySelector('.lightbox-prev');
        var btnNext = lightbox.querySelector('.lightbox-next');

        var currentIndex = 0;
        var images = [];

        // Build array of images from triggers
        triggers.forEach(function (trigger, index) {
            images.push({
                src: trigger.getAttribute('data-full-src'),
                caption: trigger.getAttribute('data-caption')
            });

            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                openLightbox(index);
            });
        });

        if (images.length <= 1) {
            lightbox.classList.add('single-photo');
        }

        function openLightbox(index) {
            currentIndex = index;
            updateLightbox();
            lightbox.classList.add('active');
            lightbox.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeLightbox() {
            lightbox.classList.remove('active');
            lightbox.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function updateLightbox() {
            var image = images[currentIndex];
            imgEl.src = image.src;
            captionEl.textContent = image.caption;
        }

        function showNext() {
            currentIndex = (currentIndex + 1) % images.length;
            updateLightbox();
        }

        function showPrev() {
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            updateLightbox();
        }

        // Event Listeners
        btnClose.addEventListener('click', closeLightbox);
        btnNext.addEventListener('click', showNext);
        btnPrev.addEventListener('click', showPrev);

        // Click outside image to close
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox || e.target.classList.contains('lightbox-content')) {
                closeLightbox();
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', function (e) {
            if (!lightbox.classList.contains('active')) return;
            
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowRight') showNext();
            if (e.key === 'ArrowLeft') showPrev();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLightbox);
    } else {
        initLightbox();
    }

})();