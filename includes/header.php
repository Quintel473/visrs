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

$headerProfileImage = $_SESSION["ProfileImage"] ?? "";
$headerFirstName    = $_SESSION["FirstName"] ?? "";
$headerLastName     = $_SESSION["LastName"] ?? "";
$headerFullName     = trim($headerFirstName . " " . $headerLastName);
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
                <?= htmlspecialchars(
                    $headerFullName !== ""
                        ? $headerFullName
                        : "User"
                ) ?>
            </span>

            <div class="user-avatar">

                <?php if ($headerProfileImage !== ""): ?>

                    <!-- FIX: Show profile picture when user has uploaded one -->
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