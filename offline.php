<?php
$pageTitle = "Offline - VISRS";
$basePath  = "";
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <!--
        Anti-FOUC theme loader — same as head.php.
    -->
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('visrs-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var theme = saved || (prefersDark ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="icon" type="image/svg+xml" href="/visrs/images/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/visrs/images/favicon.ico">
    <link rel="icon" type="image/png" sizes="96x96" href="/visrs/images/favicon-96x96.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/visrs/images/apple-touch-icon.png">
    <meta name="theme-color" content="#1e3a8a">

    <style>

        /* =============================================
           Offline page — self-contained
           Does not rely on style.css, so it renders
           correctly even when the stylesheet can't load.
           ============================================= */

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100%;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100vh;

            font-family: Arial, Helvetica, sans-serif;
            font-size: 15px;
            line-height: 1.5;

            color: #111827;
            background: #f5f7fb;
        }

        /* ---------- Wrapper ---------- */

        .offline-wrapper {
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 40px 20px;

            background:
                radial-gradient(
                    circle at 20% 20%,
                    rgba(37, 99, 235, 0.08) 0%,
                    transparent 55%
                ),
                radial-gradient(
                    circle at 80% 80%,
                    rgba(249, 115, 22, 0.06) 0%,
                    transparent 55%
                ),
                #f5f7fb;
        }

        /* ---------- Card ---------- */

        .offline-card {
            width: 100%;
            max-width: 460px;

            padding: 44px 36px 34px;

            background: #ffffff;

            border: 1px solid #e5e7eb;
            border-radius: 20px;

            box-shadow:
                0 25px 50px -12px rgba(15, 23, 42, 0.12),
                0 0 0 1px rgba(15, 23, 42, 0.02);

            text-align: center;
        }

        /* ---------- Icon ---------- */

        .offline-icon {
            position: relative;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            width: 84px;
            height: 84px;

            margin-bottom: 24px;

            background: linear-gradient(
                135deg,
                #dbeafe 0%,
                #eff6ff 100%
            );
            color: #2563eb;

            border-radius: 50%;

            font-size: 36px;
            line-height: 1;

            box-shadow:
                0 0 0 8px rgba(37, 99, 235, 0.06),
                0 0 0 16px rgba(37, 99, 235, 0.03);
        }

        .offline-icon::after {
            content: '';

            position: absolute;
            inset: 0;

            border-radius: 50%;

            border: 2px solid rgba(37, 99, 235, 0.35);

            animation: offlineRipple 2.4s ease-out infinite;
        }

        @keyframes offlineRipple {
            0% {
                transform: scale(1);
                opacity: 0.7;
            }
            100% {
                transform: scale(1.45);
                opacity: 0;
            }
        }

        /* ---------- Text ---------- */

        .offline-card h1 {
            margin: 0 0 12px;

            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;

            color: #0f172a;

            line-height: 1.2;
        }

        .offline-card p {
            margin: 0 auto 28px;

            max-width: 340px;

            color: #64748b;

            font-size: 14.5px;
            line-height: 1.65;
        }

        /* ---------- Actions ---------- */

        .offline-actions {
            display: flex;
            flex-direction: column;

            gap: 10px;

            margin-bottom: 26px;
        }

        .offline-actions .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            width: 100%;
            min-height: 46px;

            padding: 10px 16px;

            background: #2563eb;
            color: #ffffff;

            border: none;
            border-radius: 10px;

            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            font-weight: 700;

            text-decoration: none;

            cursor: pointer;

            transition: background-color 0.15s ease;
        }

        .offline-actions .button:hover {
            background: #1d4ed8;
        }

        .offline-actions .button-secondary {
            background: #6b7280;
            color: #ffffff;
        }

        .offline-actions .button-secondary:hover {
            background: #4b5563;
        }

        /* ---------- Status ---------- */

        .offline-status {
            display: inline-flex;
            align-items: center;

            gap: 8px;

            padding: 8px 14px;

            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;

            font-size: 12px;
            font-weight: 500;

            color: #64748b;

            line-height: 1;
        }

        .offline-status-dot {
            position: relative;

            display: inline-block;

            width: 8px;
            height: 8px;

            background: #dc2626;

            border-radius: 50%;

            flex-shrink: 0;
        }

        .offline-status-dot::after {
            content: '';

            position: absolute;
            inset: 0;

            border-radius: 50%;

            background: inherit;

            animation: offlinePulse 1.6s ease-in-out infinite;
        }

        .offline-status-dot.online {
            background: #16a34a;
        }

        .offline-status-dot.online::after {
            animation: none;
            opacity: 0;
        }

        @keyframes offlinePulse {
            0%, 100% {
                transform: scale(1);
                opacity: 0.6;
            }
            50% {
                transform: scale(2.2);
                opacity: 0;
            }
        }

        .offline-status-text {
            display: inline-block;
        }


        /* =============================================
           Dark mode
           ============================================= */

        [data-theme="dark"] body {
            color: #e2e8f0;
            background: #0f172a;
        }

        [data-theme="dark"] .offline-wrapper {
            background:
                radial-gradient(
                    circle at 20% 20%,
                    rgba(37, 99, 235, 0.15) 0%,
                    transparent 55%
                ),
                radial-gradient(
                    circle at 80% 80%,
                    rgba(249, 115, 22, 0.10) 0%,
                    transparent 55%
                ),
                #0f172a;
        }

        [data-theme="dark"] .offline-card {
            background: #1e293b;

            border-color: #334155;

            box-shadow:
                0 25px 50px -12px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.04);
        }

        [data-theme="dark"] .offline-icon {
            background: linear-gradient(
                135deg,
                #1e3a8a 0%,
                #1e40af 100%
            );
            color: #bfdbfe;

            box-shadow:
                0 0 0 8px rgba(96, 165, 250, 0.06),
                0 0 0 16px rgba(96, 165, 250, 0.03);
        }

        [data-theme="dark"] .offline-icon::after {
            border-color: rgba(96, 165, 250, 0.35);
        }

        [data-theme="dark"] .offline-card h1 {
            color: #f1f5f9;
        }

        [data-theme="dark"] .offline-card p {
            color: #94a3b8;
        }

        [data-theme="dark"] .offline-actions .button {
            background: #2563eb;
            color: #ffffff;
        }

        [data-theme="dark"] .offline-actions .button:hover {
            background: #3b82f6;
        }

        [data-theme="dark"] .offline-actions .button-secondary {
            background: #475569;
            color: #ffffff;
        }

        [data-theme="dark"] .offline-actions .button-secondary:hover {
            background: #64748b;
        }

        [data-theme="dark"] .offline-status {
            background: #0f172a;
            border-color: #334155;
            color: #94a3b8;
        }


        /* =============================================
           Responsive
           ============================================= */

        @media (max-width: 500px) {

            .offline-card {
                padding: 36px 26px 28px;
                border-radius: 16px;
            }

            .offline-icon {
                width: 72px;
                height: 72px;
                font-size: 30px;
                margin-bottom: 20px;
            }

            .offline-card h1 {
                font-size: 22px;
            }

            .offline-card p {
                font-size: 14px;
                margin-bottom: 22px;
            }

        }


        /* =============================================
           Reduced motion
           ============================================= */

        @media (prefers-reduced-motion: reduce) {
            .offline-icon::after,
            .offline-status-dot::after {
                animation: none;
            }
        }

    </style>

</head>

<body>

<div class="offline-wrapper">

    <div class="offline-card">

        <div class="offline-icon" aria-hidden="true">
            📡
        </div>

        <h1>You're offline</h1>

        <p>
            VISRS needs an internet connection to load fresh data
            and search vehicle records. Check your connection and
            try again.
        </p>

        <div class="offline-actions">

            <button
                type="button"
                class="button"
                id="offline-retry"
            >
                Try Again
            </button>

            <a
                href="/visrs/dashboard.php"
                class="button button-secondary"
            >
                Go to Dashboard
            </a>

        </div>

        <div class="offline-status">

            <span
                class="offline-status-dot"
                id="offline-status-dot"
                aria-hidden="true"
            ></span>

            <span
                class="offline-status-text"
                id="offline-status-text"
            >
                Waiting for connection…
            </span>

        </div>

    </div>

</div>


<script>

(function () {

    'use strict';

    var dot  = document.getElementById('offline-status-dot');
    var text = document.getElementById('offline-status-text');

    function updateStatus() {

        if (navigator.onLine) {

            dot.classList.add('online');
            text.textContent = 'Connection restored';

        } else {

            dot.classList.remove('online');
            text.textContent = 'Waiting for connection…';

        }
    }

    /* Live status updates */
    window.addEventListener('online',  updateStatus);
    window.addEventListener('offline', updateStatus);

    updateStatus();

    /* Retry button */
    document.getElementById('offline-retry')
        .addEventListener('click', function () {

            if (navigator.onLine) {
                window.location.href = '/visrs/dashboard.php';
            } else {
                text.textContent = 'Still offline — try again in a moment.';
            }

        });

    /* Auto-retry when connection returns */
    window.addEventListener('online', function () {

        setTimeout(function () {
            window.location.href = '/visrs/dashboard.php';
        }, 1400);

    });

})();

</script>

</body>
</html>