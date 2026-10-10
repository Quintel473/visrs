<?php

/*
 * VISRS Reset Password
 *
 * Step 2 of the reset flow:
 *   - Validate the token from the URL
 *   - Show a form to set a new password
 *   - On submit, update the password and invalidate the token
 */

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}


require_once __DIR__ . "/includes/database.php";
require_once __DIR__ . "/includes/functions.php";


/* If user is already logged in, no need to reset */
if (isset($_SESSION["UserID"])) {
    header("Location: /visrs/dashboard.php");
    exit;
}


$token = trim($_GET["token"] ?? "");

$error = "";
$success = false;

$resetUser = null;
$resetRow = null;


/* ---------------------------------------------------------
   Validate the token
   --------------------------------------------------------- */

if ($token === "" || !preg_match("/^[a-f0-9]{64}$/i", $token)) {

    $error = "Invalid or missing reset link. Please request a new one.";

} else {

    try {

        /* Hash the plain token to match what we stored */
        $hashedToken = hash("sha256", $token);

        /* Clean up expired tokens first */
        cleanupExpiredResets($pdo);

        /* Find a matching, unused, unexpired token */
        $stmt = $pdo->prepare("
            SELECT
                ResetID,
                UserID,
                ExpiresAt,
                UsedAt
            FROM password_resets
            WHERE TokenHash = ?
              AND UsedAt IS NULL
              AND ExpiresAt > NOW()
            LIMIT 1
        ");

        $stmt->execute([$hashedToken]);
        $resetRow = $stmt->fetch();

        if (!$resetRow) {

            $error = "This reset link is invalid, expired, or has already been used.";

        } else {

            /* Look up the user */
            $userStmt = $pdo->prepare("
                SELECT UserID, FirstName, Email
                FROM users
                WHERE UserID = ?
                LIMIT 1
            ");

            $userStmt->execute([(int) $resetRow["UserID"]]);
            $resetUser = $userStmt->fetch();

            if (!$resetUser) {
                $error = "The account associated with this link no longer exists.";
            }
        }

    } catch (Throwable $e) {

        $error = "Unable to validate your reset link. Please try again.";
    }
}


/* ---------------------------------------------------------
   Handle POST (set new password)
   --------------------------------------------------------- */

if ($_SERVER["REQUEST_METHOD"] === "POST" && $resetUser) {

    /* CSRF */
    $csrfToken = $_POST["csrf_token"] ?? "";
    $expected  = $_SESSION["reset_csrf"] ?? "";

    if (empty($csrfToken) || empty($expected) || !hash_equals($expected, $csrfToken)) {

        $error = "Security token expired. Please reload the page and try again.";

    } else {

        /* Make sure the submitted token matches the URL token */
        $submittedToken = trim($_POST["token"] ?? "");

        if ($submittedToken !== $token) {

            $error = "The reset link has changed. Please use the original link.";

        } else {

            $newPassword     = $_POST["Password"] ?? "";
            $confirmPassword = $_POST["ConfirmPassword"] ?? "";

            if ($newPassword === "") {
                $error = "Please enter a new password.";
            } elseif (strlen($newPassword) < 8) {
                $error = "Password must be at least 8 characters long.";
            } elseif ($newPassword !== $confirmPassword) {
                $error = "Passwords do not match.";
            } else {

                try {

                    $pdo->beginTransaction();

                    /* Update the password */
                    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

                    $upd = $pdo->prepare("
                        UPDATE users
                        SET Password = ?
                        WHERE UserID = ?
                    ");
                    $upd->execute([$hashed, (int) $resetUser["UserID"]]);

                    /* Mark this token as used */
                    $mark = $pdo->prepare("
                        UPDATE password_resets
                        SET UsedAt = NOW()
                        WHERE ResetID = ?
                    ");
                    $mark->execute([(int) $resetRow["ResetID"]]);

                    /* Delete any other outstanding tokens for safety */
                    $del = $pdo->prepare("
                        DELETE FROM password_resets
                        WHERE UserID = ?
                          AND ResetID != ?
                    ");
                    $del->execute([(int) $resetUser["UserID"], (int) $resetRow["ResetID"]]);

                    /* Audit */
                    logAudit(
                        $pdo,
                        "Reset password via email link",
                        "users",
                        (int) $resetUser["UserID"]
                    );

                    $pdo->commit();

                    $success = true;

                } catch (Throwable $e) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $error = "Unable to update your password. Please try again.";
                }
            }
        }
    }
}


/* Generate a fresh CSRF token for the form */
if (empty($_SESSION["reset_csrf"])) {
    $_SESSION["reset_csrf"] = bin2hex(random_bytes(32));
}


/*
 * Global <head>
 */

$basePath  = "/visrs/";
$pageTitle = "Reset Password";

require_once "includes/head.php";

?>


<body>


<div
    style="
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:40px 20px;
        background:#f3f4f6;
    "
>


    <div
        class="card"
        style="
            width:100%;
            max-width:450px;
        "
    >


        <!-- VISRS BRANDING -->

        <div
            style="
                text-align:center;
                margin-bottom:30px;
            "
        >

            <h1
                style="
                    margin:0;
                    font-size:34px;
                    font-weight:700;
                "
            >
                VISRS
            </h1>

            <p
                style="
                    margin:8px 0 0 0;
                    color:#6b7280;
                    font-size:14px;
                "
            >
                Vehicle Information Search & Retrieval System
            </p>

        </div>


        <?php if ($success): ?>

            <!-- SUCCESS -->

            <div style="text-align:center; padding:20px 0 10px;">

                <div
                    style="
                        width:64px;
                        height:64px;
                        margin:0 auto 18px;
                        border-radius:50%;
                        background:#dcfce7;
                        color:#166534;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        font-size:30px;
                    "
                >
                    ✓
                </div>

                <h2 style="margin:0 0 10px 0;">
                    Password Updated
                </h2>

                <p style="color:#6b7280; line-height:1.6;">
                    Your password has been reset successfully.
                    You can now log in with your new password.
                </p>

                <div
                    style="
                        margin-top:24px;
                        padding-top:20px;
                        border-top:1px solid #e5e7eb;
                    "
                >

                    <a
                        href="/visrs/login.php"
                        class="button"
                        style="width:100%;"
                    >
                        Go to Login
                    </a>

                </div>

            </div>


        <?php elseif (!$resetUser): ?>


            <!-- INVALID TOKEN -->

            <div style="text-align:center; padding:10px 0;">

                <div
                    style="
                        width:64px;
                        height:64px;
                        margin:0 auto 18px;
                        border-radius:50%;
                        background:#fee2e2;
                        color:#991b1b;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        font-size:30px;
                    "
                >
                    !
                </div>

                <h2 style="margin:0 0 10px 0;">
                    Invalid Reset Link
                </h2>

                <p style="color:#6b7280; line-height:1.6;">
                    <?= htmlspecialchars($error) ?>
                </p>

                <div
                    style="
                        margin-top:24px;
                        padding-top:20px;
                        border-top:1px solid #e5e7eb;
                    "
                >

                    <a
                        href="/visrs/forgot-password.php"
                        class="button"
                    >
                        Request New Link
                    </a>

                    <a
                        href="/visrs/login.php"
                        class="button button-secondary"
                        style="margin-left:8px;"
                    >
                        Back to Login
                    </a>

                </div>

            </div>


        <?php else: ?>


            <!-- HEADING -->

            <div style="margin-bottom:25px;">

                <h2 style="margin:0 0 8px 0;">
                    Set a new password
                </h2>

                <p style="margin:0; color:#6b7280;">
                    Hi <?= htmlspecialchars($resetUser["FirstName"]) ?>,
                    choose a strong new password for your account.
                </p>

            </div>


            <!-- ERROR -->

            <?php if ($error !== ""): ?>

                <div
                    style="
                        background:#fee2e2;
                        color:#991b1b;
                        padding:14px;
                        border-radius:8px;
                        margin-bottom:20px;
                    "
                >

                    <strong>Error</strong>

                    <p style="margin:6px 0 0 0;">
                        <?= htmlspecialchars($error) ?>
                    </p>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <form method="POST" action="">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION["reset_csrf"]) ?>"
                >

                <input
                    type="hidden"
                    name="token"
                    value="<?= htmlspecialchars($token) ?>"
                >


                <div class="form-group">

                    <label for="Password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="Password"
                        name="Password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        class="auto-focus"
                    >

                    <small
                        style="
                            display:block;
                            margin-top:6px;
                            color:#6b7280;
                        "
                    >
                        Minimum 8 characters.
                    </small>

                </div>


                <div class="form-group">

                    <label for="ConfirmPassword">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        id="ConfirmPassword"
                        name="ConfirmPassword"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >

                </div>


                <button
                    type="submit"
                    class="button"
                    style="
                        width:100%;
                        margin-top:10px;
                    "
                >
                    Reset Password
                </button>


            </form>


            <!-- BACK -->

            <div
                style="
                    text-align:center;
                    margin-top:25px;
                    padding-top:20px;
                    border-top:1px solid #e5e7eb;
                "
            >

                <a
                    href="/visrs/login.php"
                    style="
                        color:#6b7280;
                        font-size:14px;
                        font-weight:600;
                    "
                >
                    ← Back to Login
                </a>

            </div>


        <?php endif; ?>


    </div>


</div>


<script
    src="/visrs/js/app.js"
    defer
></script>


</body>

</html>