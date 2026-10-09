<?php
/*
 * VISRS Global Header
 *
 * Provides the same topbar on every page, including:
 *   - page title
 *   - dark mode toggle button
 *   - user name + avatar linking to profile
 *
 * The global JavaScript file is loaded from footer.php.
 */
?>

<header class="topbar">

    <div class="topbar-title">
        <?= htmlspecialchars($pageTitle ?? "VISRS") ?>
    </div>

    <div class="user-info">

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
        >
            <span class="theme-icon" aria-hidden="true">🌙</span>
        </button>


        <!-- ========================= -->
        <!-- USER PROFILE LINK -->
        <!-- ========================= -->
        <a
            href="/visrs/users/view.php?id=<?= (int) ($_SESSION["UserID"] ?? 0) ?>"
            class="profile-link"
            title="View profile"
        >

            <span class="profile-name">
                <?php
                if (isset($_SESSION["FirstName"]) && isset($_SESSION["LastName"])) {
                    echo htmlspecialchars($_SESSION["FirstName"] . " " . $_SESSION["LastName"]);
                } else {
                    echo "User";
                }
                ?>
            </span>

            <div class="user-avatar">
                <?php
                if (!empty($_SESSION["FirstName"])) {
                    echo htmlspecialchars(strtoupper(substr($_SESSION["FirstName"], 0, 1)));
                } else {
                    echo "U";
                }
                ?>
            </div>

        </a>

    </div>

</header>