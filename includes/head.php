<?php
/*
 * VISRS Global <head>
 *
 * Every page includes this file to guarantee:
 *   - correct <!DOCTYPE>, <html>, <head>
 *   - anti-FOUC theme loader (runs before CSS paints)
 *   - global stylesheet link
 *   - print stylesheet for PDF export
 *   - favicon pack
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
        Favicon pack.
        Absolute paths so every page (root or subfolder)
        resolves them identically.
    -->
    <link rel="icon" type="image/svg+xml" href="/visrs/images/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/visrs/images/favicon.ico">
    <link rel="icon" type="image/png" sizes="96x96" href="/visrs/images/favicon-96x96.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/visrs/images/apple-touch-icon.png">
    <link rel="manifest" href="/visrs/images/site.webmanifest">
    <meta name="theme-color" content="#1e3a8a">

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

</head>