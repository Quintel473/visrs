<?php
/*
 * VISRS Global Header
 *
 * Provides the same topbar on every page.
 */

require_once __DIR__ . "/functions.php";

$headerProfileImage = $_SESSION["ProfileImage"] ?? "";
$headerFirstName    = $_SESSION["FirstName"] ?? "";
$headerLastName     = $_SESSION["LastName"] ?? "";
$headerFullName     = trim($headerFirstName . " " . $headerLastName);

/* Unread notifications count (with graceful fallback if PDO not available) */
$headerUnreadCount = 0;
if (isset($pdo) && isset($_SESSION["UserID"])) {
    $headerUnreadCount = unreadNotificationCount($pdo, (int) $_SESSION["UserID"]);
}
?>

<header class="topbar">

    <!-- MOBILE HAMBURGER MENU -->
    <button type="button" class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Open menu">
        ☰
    </button>

    <div class="topbar-title">
        <?= htmlspecialchars($pageTitle ?? "VISRS") ?>
    </div>

    <!-- Added class "topbar-actions" for mobile wrapping -->
    <div class="user-info topbar-actions">

        <!-- ========================= -->
        <!-- LANGUAGE SWITCHER -->
        <!-- ========================= -->
        <div class="language-switcher" id="language-switcher">

            <button
                type="button"
                class="language-toggle"
                id="language-toggle"
                aria-label="Change language"
                title="Change language"
                aria-haspopup="true"
                aria-expanded="false"
                style="display: flex; align-items: center; gap: 6px;"
            >
                <span class="language-icon" aria-hidden="true" style="display: flex; align-items: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                </span>
                <span class="language-current" id="language-current">EN</span>
            </button>

            <div class="language-menu" id="language-menu" role="menu">

                <button type="button" class="language-option" data-lang="en" role="menuitem">
                    🇬🇧 English
                </button>
                <button type="button" class="language-option" data-lang="es" role="menuitem">
                    🇪🇸 Español
                </button>
                <button type="button" class="language-option" data-lang="fr" role="menuitem">
                    🇫🇷 Français
                </button>
                <button type="button" class="language-option" data-lang="ja" role="menuitem">
                    🇯🇵 日本語
                </button>

            </div>

        </div>


        <!-- ========================= -->
        <!-- NOTIFICATIONS -->
        <!-- ========================= -->
        <a
            href="/visrs/notifications/index.php"
            class="notifications-button"
            id="notifications-button"
            aria-label="Notifications"
            title="Notifications"
            style="display: flex; align-items: center;"
        >
            <span class="notifications-icon" aria-hidden="true" style="display: flex; align-items: center;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            </span>

            <span
                class="notifications-badge"
                id="notifications-badge"
                <?= $headerUnreadCount > 0 ? "" : "style=\"display:none;\"" ?>
            >
                <?= (int) $headerUnreadCount ?>
            </span>
        </a>


        <!-- ========================= -->
        <!-- DARK MODE TOGGLE -->
        <!-- ========================= -->
        <button
            type="button"
            id="theme-toggle"
            class="theme-toggle"
            aria-label="Switch to dark mode"
            aria-pressed="false"
            title="Toggle dark mode"
            style="display: flex; align-items: center; justify-content: center;"
        >
            <span class="theme-icon" aria-hidden="true" style="display: flex; align-items: center;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </span>
        </button>


        <!-- ========================= -->
        <!-- USER PROFILE LINK -->
        <!-- ========================= -->
        <a
            href="/visrs/users/settings.php?id=<?= (int) ($_SESSION["UserID"] ?? 0) ?>"
            class="profile-link"
            title="View profile"
        >

            <span class="profile-name">
                <?= htmlspecialchars(
                    $headerFullName !== ""
                        ? $headerFullName
                        : "User"
                ) ?>
            </span>

            <div class="user-avatar">

                <?php if ($headerProfileImage !== ""): ?>

                    <img
                        src="/visrs/<?= htmlspecialchars($headerProfileImage) ?>"
                        alt="<?= htmlspecialchars($headerFullName ?: "User") ?>"
                        class="user-avatar-image"
                    >

                <?php elseif ($headerFirstName !== ""): ?>

                    <?= htmlspecialchars(
                        strtoupper(
                            substr($headerFirstName, 0, 1)
                        )
                    ) ?>

                <?php else: ?>

                    U

                <?php endif; ?>

            </div>

        </a>

    </div>

</header>