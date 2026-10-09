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
    | FIX: Match the .auto-focus class used on login/register/forgot
    |      password inputs. Previously looked for [data-autofocus]
    |      which no element uses.
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
    | FIX: Match the [data-toggle-password] attribute used on the
    |      login, register, and reset-password forms. Previously
    |      looked for [data-password-toggle], which no element uses.
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
   =========================================================
   - Reads the theme set by the inline anti-FOUC script.
   - Updates the toggle button state (icon + ARIA).
   - Persists choice to localStorage under 'visrs-theme'.
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
   =========================================================
   - Toggles <html data-sidebar="collapsed"> on click
   - Persists state in localStorage under 'visrs-sidebar'
   - One button inside the sidebar does the toggling
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
   =========================================================
   - Loads JSON translation files from /visrs/lang/
   - Translates:
       * [data-i18n] elements (explicit key lookup)
       * [data-i18n-placeholder] attributes
       * [data-i18n-title] attributes
       * Any text node inside an [data-i18n-auto] container
         (matched by reverse dictionary)
   - Persists choice in localStorage under 'visrs-lang'
   ========================================================= */
(function () {
    'use strict';

    var SUPPORTED = ['en', 'es', 'fr', 'ja'];
    var DEFAULT_LANG = 'en';
    var STORAGE_KEY = 'visrs-lang';

    var translations = {};      // key-based translations
    var reverseDict = {};       // english-text -> target-text
    var currentLang = DEFAULT_LANG;

    /* Store original English text for every translated node so we can
       re-translate from English when the user changes language again. */
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

    /**
     * Translate every text node inside [data-i18n-auto] containers
     * using the reverse dictionary.
     */
    function translateAutoContainers() {

        var containers = document.querySelectorAll('[data-i18n-auto]');

        containers.forEach(function (container) {

            var walker = document.createTreeWalker(
                container,
                NodeFilter.SHOW_TEXT,
                {
                    acceptNode: function (node) {
                        /* Skip empty/whitespace-only text */
                        if (!node.nodeValue || !node.nodeValue.trim()) {
                            return NodeFilter.FILTER_REJECT;
                        }
                        /* Skip script/style contents */
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

                /* Preserve original English text on first pass */
                var original = originalTextMap.get(node);
                if (original === undefined) {
                    original = node.nodeValue;
                    originalTextMap.set(node, original);
                }

                var trimmed = original.trim();
                if (trimmed === '') continue;

                /* English target — restore original */
                if (currentLang === DEFAULT_LANG) {
                    if (node.nodeValue !== original) {
                        node.nodeValue = original;
                    }
                    continue;
                }

                /* Look up in reverse dictionary */
                var translated = reverseDict[trimmed];

                if (translated !== undefined) {
                    /* Preserve leading/trailing whitespace */
                    var leading = original.match(/^\s*/)[0];
                    var trailing = original.match(/\s*$/)[0];
                    node.nodeValue = leading + translated + trailing;
                } else {
                    /* No translation found → restore English */
                    if (node.nodeValue !== original) {
                        node.nodeValue = original;
                    }
                }
            }
        });
    }

    /**
     * Explicit [data-i18n] translation (key-based, existing system).
     */
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

    /**
     * Master translate function.
     */
    function applyTranslations() {

        /* Suppress transitions during the swap to prevent jank */
        document.documentElement.classList.add('i18n-changing');

        document.documentElement.setAttribute('lang', currentLang);

        applyKeyedTranslations();
        translateAutoContainers();

        /* Restore transitions after paint */
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

    /**
     * If a page author forgot to add [data-i18n-auto], we
     * auto-tag the main content area once the DOM is ready.
     * This means NO page edits are needed.
     */
    function autoTagContentAreas() {

        var candidates = [
            'main.content',
            '.content',
            '.card',
            '.page-title'
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
   =========================================================
   - Toggles the bulk action bar based on row checkboxes
   - Handles the "Select All" checkbox
   - Clears the selection when the "Clear" button is clicked
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

            /* Sync "Select All" indeterminate state */
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

        /* Select All toggle */
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                var isChecked = selectAll.checked;
                rowCheckboxes.forEach(function (cb) {
                    cb.checked = isChecked;
                });
                updateBar();
            });
        }

        /* Individual row toggles */
        rowCheckboxes.forEach(function (cb) {
            cb.addEventListener('change', updateBar);
        });

        /* Clear button */
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

        /* Initial state */
        updateBar();
    });
})();