<?php
/*
 * VISRS Global <head>
 *
 * Every page includes this file to guarantee:
 *   - correct <!DOCTYPE>, <html>, <head>
 *   - anti-FOUC theme loader (runs before CSS paints)
 *   - global stylesheet link
 *   - print stylesheet for PDF export
 *   - dynamic favicon
 *   - Chart.js (only when $loadCharts is true)
 *
 * Set BEFORE including this file:
 *   $pageTitle  — string, shown in <title>
 *   $basePath   — "" at root, "../" in subfolders
 *   $loadCharts — bool (optional), loads Chart.js CDN
 *
 * The page is responsible for opening <body>.
 */
$pageTitle = $pageTitle ?? "VISRS";
$basePath  = $basePath  ?? "";
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
        Anti-FOUC theme loader.
        MUST run before the stylesheet so the correct theme
        is applied before the first paint.
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

    <!--
        Favicon — Dynamic SVG "V"
    -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23111827'/><text x='50' y='52' font-family='Arial,sans-serif' font-size='65' font-weight='bold' fill='%23ffffff' text-anchor='middle' dominant-baseline='middle'>V</text></svg>">
    
    <meta name="theme-color" content="#111827">

    <!-- CRITICAL LAYOUT CSS (Prevents sidebar flash) -->
    <style>
        html, body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        .layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: 100;
            background-color: #1e293b; /* Fallback dark color */
        }
        .main-content {
            flex-grow: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }
        .content {
            padding: 24px;
            flex-grow: 1;
        }
        /* Hide layout until CSS loads to prevent text jumping */
        .layout {
            visibility: hidden;
            animation: showLayout 0s 0.1s forwards;
        }
        @keyframes showLayout {
            to { visibility: visible; }
        }
    </style>

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars($basePath) ?>css/style.css"
    >

    <!--
        Print stylesheet.
    -->
    <link
        rel="stylesheet"
        href="<?= htmlspecialchars($basePath) ?>css/print.css"
        media="print"
    >

    <?php if (!empty($loadCharts)): ?>

        <!--
            Chart.js
        -->
        <script
            src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"
        ></script>

    <?php endif; ?>

</head>