<?php

/*
 * VISRS Forgot Password
 *
 * Step 1 of the reset flow:
 *   - User enters their email address
 *   - If the email exists, generate a token and send a link
 *   - Always show the same success message (don't leak which
 *     emails are registered)
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


$email = "";
$error = "";
$success = false;


/* ---------------------------------------------------------
   Handle POST
   --------------------------------------------------------- */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* CSRF token (we generate one below and validate it here) */
    $token = $_POST["csrf_token"] ?? "";
    $expected = $_SESSION["forgot_csrf"] ?? "";

    if (empty($token) || empty($expected) || !hash_equals($expected, $token)) {
        $error = "Security token expired. Please try again.";
    } else {

        $email = trim($_POST["Email"] ?? "");

        if ($email === "") {
            $error = "Please enter your email address.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {

            try {

                /* Clean up old tokens first */
                cleanupExpiredResets($pdo);

                /* Look up the user by email */
                $stmt = $pdo->prepare("
                    SELECT UserID, FirstName, Email
                    FROM users
                    WHERE Email = ?
                    LIMIT 1
                ");

                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {

                    /*
                     * Rate-limit: only one active (unused, unexpired)
                     * token per user at a time. Delete any existing ones.
                     */
                    $del = $pdo->prepare("
                        DELETE FROM password_resets
                        WHERE UserID = ?
                    ");
                    $del->execute([(int) $user["UserID"]]);

                    /*
                     * Generate a fresh token.
                     */
                    list($plainToken, $hashedToken) = generateResetToken();

                    $expiresAt = date(
                        "Y-m-d H:i:s",
                        time() + 3600   /* 1 hour */
                    );

                    $ins = $pdo->prepare("
                        INSERT INTO password_resets
                        (
                            UserID,
                            TokenHash,
                            ExpiresAt,
                            IPAddress
                        )
                        VALUES (?, ?, ?, ?)
                    ");

                    $ins->execute([
                        (int) $user["UserID"],
                        $hashedToken,
                        $expiresAt,
                        $_SERVER["REMOTE_ADDR"] ?? null
                    ]);

                    /*
                     * Build the reset link.
                     * Uses current host so it works on any environment.
                     */
                    $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
                    $host   = $_SERVER["HTTP_HOST"] ?? "localhost";

                    $resetLink = $scheme . "://" . $host
                               . "/visrs/reset-password.php?token=" . urlencode($plainToken);

                    /*
                     * Attempt to email, log regardless.
                     */
                    sendResetEmail(
                        $user["Email"],
                        $user["FirstName"],
                        $resetLink
                    );

                    /*
                     * Audit log
                     */
                    logAudit(
                        $pdo,
                        "Requested password reset",
                        "users",
                        (int) $user["UserID"]
                    );
                }

                /* Always show success message (don't leak existence) */
                $success = true;

            } catch (Throwable $e) {

                $error = "Unable to process your request. Please try again.";
            }
        }
    }
}


/* Generate a fresh CSRF token */
if (empty($_SESSION["forgot_csrf"])) {
    $_SESSION["forgot_csrf"] = bin2hex(random_bytes(32));
}


/*
 * Global <head>
 */

$basePath  = "/visrs/";
$pageTitle = "Forgot Password";

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

            <div
                style="
                    text-align:center;
                    padding:20px 0 10px;
                "
            >

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
                    ✉
                </div>

                <h2 style="margin:0 0 10px 0;">
                    Check your email
                </h2>

                <p style="color:#6b7280; line-height:1.6;">
                    If an account exists for
                    <strong><?= htmlspecialchars($email) ?></strong>,
                    we've sent a password reset link.
                </p>

                <p style="color:#6b7280; line-height:1.6; font-size:13px;">
                    The link expires in 1 hour.
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
                        class="button button-secondary"
                    >
                        Back to Login
                    </a>

                </div>

            </div>


        <?php else: ?>


            <!-- HEADING -->

            <div style="margin-bottom:25px;">

                <h2 style="margin:0 0 8px 0;">
                    Forgot your password?
                </h2>

                <p style="margin:0; color:#6b7280;">
                    Enter your email address and we'll send you a link
                    to reset your password.
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

                    <strong>Reset Error</strong>

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
                    value="<?= htmlspecialchars($_SESSION["forgot_csrf"]) ?>"
                >


                <div class="form-group">

                    <label for="Email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="Email"
                        name="Email"
                        value="<?= htmlspecialchars($email) ?>"
                        required
                        maxlength="150"
                        autocomplete="email"
                        class="auto-focus"
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
                    Send Reset Link
                </button>


            </form>


            <!-- BACK TO LOGIN -->

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