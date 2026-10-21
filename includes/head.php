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
 *   - service worker registration (offline fallback)
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
        Generates a dark rounded square with a white "V"
        entirely via code. No image files required.
    -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23111827'/><text x='50' y='52' font-family='Arial,sans-serif' font-size='65' font-weight='bold' fill='%23ffffff' text-anchor='middle' dominant-baseline='middle'>V</text></svg>">
    
    <!--
        Updated theme-color to match the new dark favicon background.
    -->
    <meta name="theme-color" content="#111827">

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars($basePath) ?>css/style.css"
    >

    <!--
        Print stylesheet.
        Loaded with media="print" so it only applies when
        the user prints or saves a page as PDF.
    -->
    <link
        rel="stylesheet"
        href="<?= htmlspecialchars($basePath) ?>css/print.css"
        media="print"
    >

    <?php if (!empty($loadCharts)): ?>

        <!--
            Chart.js — loaded only when the page sets
            $loadCharts = true. Currently used by dashboard.php.
        -->
        <script
            src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"
        ></script>

    <?php endif; ?>

    <!--
        Service Worker registration.
        Enables offline fallback via /visrs/sw.js.
        Placed here so every page gets it automatically
        (head.php is included everywhere).
    -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/visrs/sw.js', {
                    scope: '/visrs/'
                }).then(function (reg) {
                    console.log('VISRS Service Worker registered. Scope:', reg.scope);
                }).catch(function (err) {
                    console.warn('VISRS Service Worker failed:', err);
                });
            });
        }
    </script>

</head>