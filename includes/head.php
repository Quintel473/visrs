<?php
/*
 * VISRS Global <head>
 */
$pageTitle = $pageTitle ?? "VISRS";
$basePath  = $basePath  ?? "";
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <!-- Anti-FOUC theme loader -->
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

    <!-- Favicon — Dynamic SVG "V" -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23111827'/><text x='50' y='52' font-family='Arial,sans-serif' font-size='65' font-weight='bold' fill='%23ffffff' text-anchor='middle' dominant-baseline='middle'>V</text></svg>">
    
    <meta name="theme-color" content="#111827">

    <!-- CRITICAL LAYOUT CSS -->
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
            width: 250px;
            flex-shrink: 0;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
            background-color: #1e293b;
        }
        .main-content {
            flex-grow: 1;
            margin-left: 250px;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }
        .content {
            padding: 24px;
            flex-grow: 1;
        }
        /* Reduced delay to stop blinking */
        .layout {
            visibility: hidden;
            animation: showLayout 0s 0.05s forwards; 
        }
        @keyframes showLayout {
            to { visibility: visible; }
        }
    </style>

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars($basePath) ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars($basePath) ?>css/print.css"
        media="print"
    >

    <?php if (!empty($loadCharts)): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php endif; ?>

</head>