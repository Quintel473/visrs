<?php
/*
 * VISRS Global Header
 *
 * Provides the same topbar on every page, including:
 *   - page title
 *   - language switcher
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
            >
                <span class="language-icon" aria-hidden="true">🌐</span>
                <span class="language-current" id="language-current">EN</span>
            </button>

            <div class="language-menu" id="language-menu" role="menu">

                <button
                    type="button"
                    class="language-option"
                    data-lang="en"
                    role="menuitem"
                >
                    🇬🇧 English
                </button>

                <button
                    type="button"
                    class="language-option"
                    data-lang="es"
                    role="menuitem"
                >
                    🇪🇸 Español
                </button>

                <button
                    type="button"
                    class="language-option"
                    data-lang="fr"
                    role="menuitem"
                >
                    🇫🇷 Français
                </button>

                <button
                    type="button"
                    class="language-option"
                    data-lang="ja"
                    role="menuitem"
                >
                    🇯🇵 日本語
                </button>

            </div>

        </div>


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