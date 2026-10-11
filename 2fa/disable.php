<?php

/*
 * VISRS — 2FA Disable
 *
 * Turns off two-factor authentication for the currently
 * logged-in user. Requires:
 *
 *   1. Account password
 *   2. A valid TOTP code OR a recovery code
 *
 * On success: clears the secret, disables 2FA, deletes all
 * recovery codes, and logs the change to the audit trail.
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/totp.php";

$basePath   = "../";
$activePage = "settings";

$pageTitle = "Disable 2FA - VISRS";

$currentUserID = (int) currentUserId();

$error = "";


/* ---------- Load current user ---------- */

$stmt = $pdo->prepare("
    SELECT
        UserID,
        Email,
        Password,
        TwoFactorSecret,
        TwoFactorEnabled
    FROM users
    WHERE UserID = ?
    LIMIT 1
");

$stmt->execute([$currentUserID]);
$user = $stmt->fetch();

if (!$user) {
    redirect("/visrs/logout.php");
}


/* ---------- If 2FA is already off, nothing to do here ---------- */

if ((int) $user["TwoFactorEnabled"] !== 1) {

    header("Location: /visrs/2fa/setup.php");
    exit;
}


/* ---------- Handle POST ---------- */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    requireValidCSRF();

    $password = $_POST["password"] ?? "";
    $code     = strtoupper(trim($_POST["code"] ?? ""));
    $code     = str_replace(["-", " "], "", $code);


    /* ---------- Validate presence ---------- */

    if ($password === "") {

        $error = "Please enter your password.";

    } elseif ($code === "") {

        $error = "Please enter a verification code from your authenticator app or a recovery code.";

    }


    /* ---------- Verify password ---------- */

    if ($error === "" && !password_verify($password, $user["Password"])) {

        $error = "Your password is incorrect.";

    }


    /* ---------- Verify second factor (TOTP or recovery code) ---------- */

    $verifiedSecondFactor = false;
    $usedRecovery = false;

    if ($error === "") {

        /* Try TOTP first */
        if (preg_match('/^\d{6}$/', $code)) {

            if (totp_verify($user["TwoFactorSecret"], $code)) {
                $verifiedSecondFactor = true;
            }
        }

        /* Try recovery code as fallback */
        if (!$verifiedSecondFactor && preg_match('/^[A-F0-9]{10}$/', $code)) {

            $hash = totp_hash_recovery_code(
                substr($code, 0, 5) . "-" . substr($code, 5, 5)
            );

            $rcStmt = $pdo->prepare("
                SELECT CodeID
                FROM recovery_codes
                WHERE UserID = ?
                  AND CodeHash = ?
                  AND UsedAt IS NULL
                LIMIT 1
            ");

            $rcStmt->execute([$user["UserID"], $hash]);
            $rc = $rcStmt->fetch();

            if ($rc) {
                $verifiedSecondFactor = true;
                $usedRecovery = true;
            }
        }

        if (!$verifiedSecondFactor) {
            $error = "The verification code is incorrect.";
        }
    }


    /* ---------- Perform the disable ---------- */

    if ($error === "") {

        try {

            $pdo->beginTransaction();


            /* Clear 2FA on the user */
            $upd = $pdo->prepare("
                UPDATE users
                SET
                    TwoFactorSecret = NULL,
                    TwoFactorEnabled = 0,
                    TwoFactorConfirmedAt = NULL
                WHERE UserID = ?
            ");

            $upd->execute([$currentUserID]);


            /* Delete all recovery codes */
            $del = $pdo->prepare("
                DELETE FROM recovery_codes WHERE UserID = ?
            ");

            $del->execute([$currentUserID]);


            /* Audit */
            logAudit(
                $pdo,
                $usedRecovery
                    ? "Disabled two-factor authentication (via recovery code)"
                    : "Disabled two-factor authentication",
                "users",
                $currentUserID
            );


            $pdo->commit();


            /* Reset setup session state, just in case */
            unset($_SESSION["2fa_pending_secret"]);
            unset($_SESSION["2fa_recovery_codes"]);


            header("Location: /visrs/2fa/setup.php?disabled=1");
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Unable to disable 2FA. Please try again.";
        }
    }
}

require_once __DIR__ . "/../includes/head.php";

?>


<style>

    .twofa-page {
        max-width: 560px;
        margin: 0 auto;
    }

    .twofa-header {
        margin-bottom: 25px;
    }

    .twofa-header h1 {
        margin: 0 0 6px;
        font-size: 25px;
        color: #111827;
    }

    .twofa-header p {
        margin: 0;
        color: #64748b;
        font-size: 14px;
    }

    .twofa-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 2px 7px rgba(0,0,0,0.04);
        overflow: hidden;
    }

    .twofa-card-header {
        padding: 18px 22px;
        border-bottom: 1px solid #e5e7eb;
        background: #fef2f2;
    }

    .twofa-card-header h2 {
        margin: 0;
        font-size: 17px;
        color: #991b1b;
    }

    .twofa-card-header p {
        margin: 4px 0 0;
        color: #b91c1c;
        font-size: 13px;
    }

    .twofa-card-body {
        padding: 22px;
    }

    .twofa-warning {
        padding: 14px 16px;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 10px;
        color: #9a3412;
        font-size: 13.5px;
        line-height: 1.6;
        margin-bottom: 22px;
    }

    .twofa-warning strong {
        display: block;
        margin-bottom: 4px;
        color: #7c2d12;
        font-size: 14px;
    }

    .twofa-warning ul {
        margin: 8px 0 0 20px;
        padding: 0;
    }

    .twofa-warning li {
        margin-bottom: 4px;
    }

    .twofa-field {
        margin-bottom: 16px;
    }

    .twofa-field label {
        display: block;
        margin-bottom: 7px;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .twofa-field input {
        width: 100%;
        box-sizing: border-box;
        padding: 11px 13px;
        border: 1px solid #d1d5db;
        border-radius: 7px;
        background: #ffffff;
        color: #111827;
        font-size: 14px;
        font-family: inherit;
    }

    .twofa-field input:focus {
        outline: none;
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.10);
    }

    .twofa-field-help {
        display: block;
        margin-top: 6px;
        color: #64748b;
        font-size: 12px;
        line-height: 1.5;
    }

    .twofa-error {
        padding: 13px 15px;
        background: #fee2e2;
        border: 1px solid #fecaca;
        border-radius: 8px;
        color: #991b1b;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .twofa-actions {
        display: flex;
        gap: 10px;
        margin-top: 22px;
        flex-wrap: wrap;
    }

    /* Dark mode */
    [data-theme="dark"] .twofa-header h1 { color: #f1f5f9; }
    [data-theme="dark"] .twofa-header p { color: #94a3b8; }

    [data-theme="dark"] .twofa-card {
        background: #1e293b;
        border-color: #334155;
    }

    [data-theme="dark"] .twofa-card-header {
        background: #450a0a;
        border-color: #7f1d1d;
    }

    [data-theme="dark"] .twofa-card-header h2 { color: #fecaca; }
    [data-theme="dark"] .twofa-card-header p  { color: #fca5a5; }

    [data-theme="dark"] .twofa-warning {
        background: #431407;
        border-color: #7c2d12;
        color: #fdba74;
    }

    [data-theme="dark"] .twofa-warning strong { color: #fed7aa; }

    [data-theme="dark"] .twofa-field label { color: #cbd5e1; }

    [data-theme="dark"] .twofa-field input {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }

    [data-theme="dark"] .twofa-field-help { color: #94a3b8; }

    [data-theme="dark"] .twofa-error {
        background: #7f1d1d;
        border-color: #991b1b;
        color: #fecaca;
    }

</style>


<body>

<div class="layout">

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php require_once __DIR__ . "/../includes/header.php"; ?>

        <div class="content">

            <div class="twofa-page">

                <div class="twofa-header">

                    <h1>Disable Two-Factor Authentication</h1>

                    <p>
                        This will remove the second factor from your account.
                    </p>

                </div>


                <div class="twofa-card">

                    <div class="twofa-card-header">

                        <h2>⚠️ Reducing Account Security</h2>

                        <p>
                            Read this before continuing.
                        </p>

                    </div>

                    <div class="twofa-card-body">

                        <div class="twofa-warning">

                            <strong>
                                Disabling 2FA will:
                            </strong>

                            <ul>
                                <li>Remove the second step from your sign-in.</li>
                                <li>Delete all your recovery codes.</li>
                                <li>Make your account vulnerable to password theft.</li>
                            </ul>

                        </div>


                        <?php if ($error !== ""): ?>

                            <div class="twofa-error">
                                <?= e($error) ?>
                            </div>

                        <?php endif; ?>


                        <form method="POST" action="">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(generateCSRFToken()) ?>"
                            >


                            <div class="twofa-field">

                                <label for="password">
                                    Your Password
                                </label>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    autofocus
                                >

                            </div>


                            <div class="twofa-field">

                                <label for="code">
                                    Verification Code
                                </label>

                                <input
                                    type="text"
                                    id="code"
                                    name="code"
                                    inputmode="numeric"
                                    maxlength="11"
                                    placeholder="000000"
                                    required
                                    autocomplete="one-time-code"
                                >

                                <span class="twofa-field-help">
                                    Enter the 6-digit code from your
                                    authenticator app, or a recovery code.
                                </span>

                            </div>


                            <div class="twofa-actions">

                                <button
                                    type="submit"
                                    class="button button-danger"
                                    onclick="return confirm('Disable two-factor authentication? Your account will be less secure.');"
                                >
                                    Disable 2FA
                                </button>

                                <a
                                    href="/visrs/2fa/setup.php"
                                    class="button button-secondary"
                                >
                                    Cancel
                                </a>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>


<script>

(function () {

    'use strict';

    var input = document.getElementById('code');
    if (!input) return;

    input.addEventListener('input', function () {

        var v = input.value.toUpperCase();
        v = v.replace(/[^A-Z0-9]/g, '');

        input.value = v;
    });

})();

</script>


<?php require_once __DIR__ . "/../includes/footer.php"; ?>