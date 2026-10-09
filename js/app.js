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

        /*
         * Add new selectors here to opt additional page
         * regions into automatic i18n translation.
         *
         * .login-page covers the split-screen login layout
         * (both hero and form panels).
         */

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

        /* Fetch unread count via a lightweight endpoint */
        function refresh() {

            return fetch('/visrs/notifications/unread_count.php', {
                cache: 'no-store'
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {

                    if (typeof data.unread !== 'number') return;

                    if (data.unread > 0) {
                        badge.textContent = data.unread;
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(function () { /* silent */ });
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

        /* Kick off the loop */
        setTimeout(loop, REFRESH_INTERVAL);
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

            /* Insert right after the input's parent (.login-password-wrapper
               or .settings-field, whichever wraps it) */
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
                if (/(.)\1{2,}/.test(pw)) score -= 1;         // 3+ repeated chars
                if (/^[a-z]+$/.test(pw)) score -= 1;          // all lowercase
                if (/^[0-9]+$/.test(pw)) score -= 2;          // all digits
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

                /* Empty — hide everything */
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

            /* Initial state (in case the browser autofills) */
            update();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();