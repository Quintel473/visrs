<?php
/*
 * VISRS Global <head>
 *
 * Every page includes this file to guarantee:
 *   - correct <!DOCTYPE>, <html>, <head>
 *   - anti-FOUC theme loader (runs before CSS paints)
 *   - global stylesheet link
 *
 * Set BEFORE including this file:
 *   $pageTitle  — string, shown in <title>
 *   $basePath   — "" at root, "../" in subfolders
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

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars($basePath) ?>css/style.css"
    >

</head>