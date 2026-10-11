<?php

/*
 * VISRS — 2FA Setup
 *
 * Handles enabling two-factor authentication for the
 * currently logged-in user. Three phases:
 *
 *   1. If 2FA is off and no pending secret → show "Enable" CTA
 *   2. If a pending secret exists → show QR code + confirm form
 *   3. If 2FA is on → show status + regenerate recovery codes
 *
 * A "pending secret" is stored in the session until the
 * user confirms it with a valid TOTP code. This prevents
 * half-configured accounts.
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/totp.php";

$basePath   = "../";
$activePage = "settings";

$pageTitle  = "Two-Factor Authentication";

$currentUserID = (int) currentUserId();

$successMessage = "";
$errors = [];


/*
|--------------------------------------------------------------------------
| Load Current User (2FA fields)
|--------------------------------------------------------------------------
*/

$userStmt = $pdo->prepare("
    SELECT
        UserID,
        FirstName,
        LastName,
        Email,
        Password,
        TwoFactorSecret,
        TwoFactorEnabled,
        TwoFactorConfirmedAt
    FROM users
    WHERE UserID = ?
    LIMIT 1
");

$userStmt->execute([$currentUserID]);
$user = $userStmt->fetch();

if (!$user) {
    redirect("/visrs/logout.php");
}

$isEnabled = (int) $user["TwoFactorEnabled"] === 1;


/*
|--------------------------------------------------------------------------
| Pending secret (from session, only during setup)
|--------------------------------------------------------------------------
*/

$pendingSecret = $_SESSION["2fa_pending_secret"] ?? "";

$hasPending = (!$isEnabled && $pendingSecret !== "");


/*
|--------------------------------------------------------------------------
| Handle POST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    requireValidCSRF();

    $action = $_POST["action"] ?? "";


    /* =================================================
       ACTION: Begin setup — generate secret, show QR
       ================================================= */

    if ($action === "begin_setup" && !$isEnabled) {

        $secret = totp_generate_secret();

        $_SESSION["2fa_pending_secret"] = $secret;

        /* Redirect to self so a refresh doesn't resubmit */
        header("Location: setup.php");
        exit;

    }


    /* =================================================
       ACTION: Confirm setup — verify code, enable 2FA
       ================================================= */

    elseif ($action === "confirm_setup" && $hasPending) {

        $code = trim($_POST["code"] ?? "");

        if ($code === "") {

            $errors[] = "Please enter the 6-digit code from your authenticator app.";

        } elseif (!totp_verify($pendingSecret, $code)) {

            $errors[] = "That code is incorrect. Please check your app and try again.";

        } else {

            /* Generate recovery codes */
            $plainCodes = totp_generate_recovery_codes(8);

            try {

                $pdo->beginTransaction();


                /* Save secret + enable 2FA */
                $upd = $pdo->prepare("
                    UPDATE users
                    SET
                        TwoFactorSecret = ?,
                        TwoFactorEnabled = 1,
                        TwoFactorConfirmedAt = NOW()
                    WHERE UserID = ?
                ");

                $upd->execute([$pendingSecret, $currentUserID]);


                /* Delete any existing recovery codes for this user */
                $del = $pdo->prepare("
                    DELETE FROM recovery_codes WHERE UserID = ?
                ");

                $del->execute([$currentUserID]);


                /* Insert new hashed recovery codes */
                $ins = $pdo->prepare("
                    INSERT INTO recovery_codes
                        (UserID, CodeHash)
                    VALUES
                        (?, ?)
                ");

                foreach ($plainCodes as $code) {

                    $ins->execute([
                        $currentUserID,
                        totp_hash_recovery_code($code)
                    ]);
                }


                logAudit(
                    $pdo,
                    "Enabled two-factor authentication",
                    "users",
                    $currentUserID
                );


                $pdo->commit();


                /* Clear pending secret */
                unset($_SESSION["2fa_pending_secret"]);


                /* Stash recovery codes in session for one-time display */
                $_SESSION["2fa_recovery_codes"] = $plainCodes;


                /* Redirect to the same page — the recovery codes
                   block will render because the session flag is set */
                header("Location: setup.php?enabled=1");
                exit;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $errors[] = "Unable to enable 2FA. Please try again.";
            }
        }
    }


    /* =================================================
       ACTION: Cancel setup
       ================================================= */

    elseif ($action === "cancel_setup") {

        unset($_SESSION["2fa_pending_secret"]);

        header("Location: setup.php");
        exit;
    }


    /* =================================================
       ACTION: Regenerate recovery codes
       ================================================= */

    elseif ($action === "regenerate_recovery" && $isEnabled) {

        $password = $_POST["password"] ?? "";

        if ($password === "") {

            $errors[] = "Please enter your password to regenerate codes.";

        } elseif (!password_verify($password, $user["Password"])) {

            $errors[] = "Password is incorrect.";

        } else {

            $plainCodes = totp_generate_recovery_codes(8);

            try {

                $pdo->beginTransaction();


                $del = $pdo->prepare("
                    DELETE FROM recovery_codes WHERE UserID = ?
                ");

                $del->execute([$currentUserID]);


                $ins = $pdo->prepare("
                    INSERT INTO recovery_codes
                        (UserID, CodeHash)
                    VALUES
                        (?, ?)
                ");

                foreach ($plainCodes as $code) {

                    $ins->execute([
                        $currentUserID,
                        totp_hash_recovery_code($code)
                    ]);
                }


                logAudit(
                    $pdo,
                    "Regenerated 2FA recovery codes",
                    "users",
                    $currentUserID
                );


                $pdo->commit();


                $_SESSION["2fa_recovery_codes"] = $plainCodes;

                header("Location: setup.php?regenerated=1");
                exit;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $errors[] = "Unable to regenerate recovery codes. Please try again.";
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Pull recovery codes from session (one-time display)
|--------------------------------------------------------------------------
*/

$showRecoveryCodes = $_SESSION["2fa_recovery_codes"] ?? null;

if ($showRecoveryCodes !== null) {
    unset($_SESSION["2fa_recovery_codes"]);
}


/*
|--------------------------------------------------------------------------
| Head
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../includes/head.php";

?>


<!-- QRCode.js — client-side QR rendering, no external fetch -->

<script
    src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"
    defer
></script>


<style>

    .twofa-page {
        max-width: 720px;
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
        margin-bottom: 22px;
        overflow: hidden;
    }

    .twofa-card-header {
        padding: 18px 22px;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    .twofa-card-header h2 {
        margin: 0;
        font-size: 17px;
        color: #111827;
    }

    .twofa-card-header p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 13px;
    }

    .twofa-card-body {
        padding: 22px;
    }

    .twofa-status {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
    }

    .twofa-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        line-height: 1;
    }

    .twofa-status-badge.enabled {
        background: #dcfce7;
        color: #166534;
    }

    .twofa-status-badge.disabled {
        background: #f3f4f6;
        color: #64748b;
    }

    .twofa-status-text {
        color: #64748b;
        font-size: 14px;
    }

    .twofa-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 20px;
    }

    .twofa-steps {
        margin: 0 0 22px 0;
        padding: 0 0 0 22px;
        color: #475569;
        font-size: 14px;
        line-height: 1.7;
    }

    .twofa-steps li {
        margin-bottom: 8px;
    }

    .twofa-setup-grid {
        display: flex;
        gap: 26px;
        align-items: flex-start;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .twofa-qr {
        padding: 12px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        flex-shrink: 0;
    }

    .twofa-qr canvas,
    .twofa-qr img {
        display: block;
    }

    .twofa-secret-block {
        flex: 1;
        min-width: 240px;
    }

    .twofa-secret-block p {
        margin: 0 0 8px;
        color: #64748b;
        font-size: 13px;
        line-height: 1.5;
    }

    .twofa-secret {
        display: inline-block;
        padding: 10px 14px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 1px;
        color: #0f172a;
        word-break: break-all;
        user-select: all;
    }

    .twofa-confirm-form {
        margin-top: 12px;
        padding-top: 18px;
        border-top: 1px solid #f1f5f9;
    }

    .twofa-confirm-form label {
        display: block;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .twofa-confirm-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .twofa-confirm-row input[type="text"] {
        width: 140px;
        text-align: center;
        font-family: 'Courier New', monospace;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: 4px;
        padding: 10px 12px;
    }

    .twofa-recovery-codes {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin: 18px 0;
    }

    .twofa-recovery-code {
        padding: 12px 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: 1px;
        color: #0f172a;
        text-align: center;
        user-select: all;
    }

    .twofa-warning {
        padding: 14px 16px;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 10px;
        color: #9a3412;
        font-size: 13.5px;
        line-height: 1.6;
        margin-bottom: 18px;
    }

    .twofa-warning strong {
        display: block;
        margin-bottom: 4px;
        color: #7c2d12;
        font-size: 14px;
    }

    .twofa-alert {
        padding: 13px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 600;
    }

    .twofa-alert-success {
        background: #dcfce7;
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .twofa-alert-error {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .twofa-alert-error ul {
        margin: 8px 0 0 20px;
        padding: 0;
    }

    /* Dark mode */
    [data-theme="dark"] .twofa-header h1 { color: #f1f5f9; }
    [data-theme="dark"] .twofa-header p { color: #94a3b8; }

    [data-theme="dark"] .twofa-card {
        background: #1e293b;
        border-color: #334155;
    }

    [data-theme="dark"] .twofa-card-header {
        background: #0f172a;
        border-color: #334155;
    }

    [data-theme="dark"] .twofa-card-header h2 { color: #f1f5f9; }
    [data-theme="dark"] .twofa-card-header p { color: #94a3b8; }

    [data-theme="dark"] .twofa-status-text { color: #94a3b8; }

    [data-theme="dark"] .twofa-status-badge.disabled {
        background: #334155;
        color: #cbd5e1;
    }

    [data-theme="dark"] .twofa-status-badge.enabled {
        background: #064e3b;
        color: #6ee7b7;
    }

    [data-theme="dark"] .twofa-steps { color: #cbd5e1; }

    [data-theme="dark"] .twofa-qr {
        background: #ffffff; /* QR is always white for scan reliability */
        border-color: #334155;
    }

    [data-theme="dark"] .twofa-secret-block p { color: #94a3b8; }

    [data-theme="dark"] .twofa-secret {
        background: #0f172a;
        border-color: #334155;
        color: #f1f5f9;
    }

    [data-theme="dark"] .twofa-confirm-form { border-top-color: #334155; }
    [data-theme="dark"] .twofa-confirm-form label { color: #cbd5e1; }

    [data-theme="dark"] .twofa-recovery-code {
        background: #0f172a;
        border-color: #334155;
        color: #f1f5f9;
    }

    [data-theme="dark"] .twofa-warning {
        background: #431407;
        border-color: #7c2d12;
        color: #fdba74;
    }

    [data-theme="dark"] .twofa-warning strong { color: #fed7aa; }

    [data-theme="dark"] .twofa-alert-success {
        background: #064e3b;
        border-color: #065f46;
        color: #6ee7b7;
    }

    [data-theme="dark"] .twofa-alert-error {
        background: #7f1d1d;
        border-color: #991b1b;
        color: #fecaca;
    }

    @media (max-width: 600px) {
        .twofa-recovery-codes {
            grid-template-columns: 1fr;
        }
        .twofa-setup-grid {
            flex-direction: column;
            align-items: center;
        }
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

                    <h1>Two-Factor Authentication</h1>

                    <p>
                        Add an extra layer of security to your account.
                    </p>

                </div>


                <!-- =============================================
                     ALERTS
                     ============================================= -->

                <?php if ($successMessage !== ""): ?>

                    <div class="twofa-alert twofa-alert-success">
                        <?= e($successMessage) ?>
                    </div>

                <?php endif; ?>

                <?php if (!empty($errors)): ?>

                    <div class="twofa-alert twofa-alert-error">

                        <strong>Please correct the following:</strong>

                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>

                    </div>

                <?php endif; ?>


                <!-- =============================================
                     ONE-TIME RECOVERY CODES DISPLAY
                     ============================================= -->

                <?php if (!empty($showRecoveryCodes)): ?>

                    <div class="twofa-card">

                        <div class="twofa-card-header">

                            <h2>
                                ⚠️ Save Your Recovery Codes
                            </h2>

                            <p>
                                These codes will only be shown once.
                            </p>

                        </div>

                        <div class="twofa-card-body">

                            <div class="twofa-warning">

                                <strong>
                                    Write these down or print them now.
                                </strong>

                                Each code can be used once to sign in
                                if you lose access to your authenticator
                                app. They will not be shown again.

                            </div>

                            <div class="twofa-recovery-codes">

                                <?php foreach ($showRecoveryCodes as $rc): ?>

                                    <div class="twofa-recovery-code">
                                        <?= e($rc) ?>
                                    </div>

                                <?php endforeach; ?>

                            </div>

                            <p style="margin:14px 0 0; color:#64748b; font-size:13px; line-height:1.6;">
                                Store them somewhere safe — a password
                                manager, a printed page in a locked drawer,
                                or an encrypted note. Anyone with a code
                                can sign in as you.
                            </p>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- =============================================
                     MAIN STATE CARD
                     ============================================= -->

                <div class="twofa-card">

                    <div class="twofa-card-header">

                        <h2>Status</h2>

                        <p>
                            Your current 2FA configuration.
                        </p>

                    </div>

                    <div class="twofa-card-body">


                        <!-- STATUS BADGE -->

                        <div class="twofa-status">

                            <?php if ($isEnabled): ?>

                                <span class="twofa-status-badge enabled">
                                    ✓ Enabled
                                </span>

                                <span class="twofa-status-text">

                                    Since

                                    <?= e(
                                        $user["TwoFactorConfirmedAt"]
                                            ? date("F j, Y", strtotime($user["TwoFactorConfirmedAt"]))
                                            : "—"
                                    ) ?>

                                </span>

                            <?php else: ?>

                                <span class="twofa-status-badge disabled">
                                    Disabled
                                </span>

                                <span class="twofa-status-text">
                                    Your account is protected by a password only.
                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- =====================================
                             STATE A — 2FA enabled
                             ===================================== -->

                        <?php if ($isEnabled): ?>

                            <p style="color:#64748b; font-size:14px; line-height:1.65; margin:0 0 8px;">
                                Two-factor authentication is active on your
                                account. You will be asked for a 6-digit
                                code from your authenticator app each time
                                you sign in.
                            </p>

                            <div class="twofa-actions">

                                <a
                                    href="recovery.php"
                                    class="button button-secondary"
                                >
                                    Manage Recovery Codes
                                </a>

                                <a
                                    href="disable.php"
                                    class="button button-danger"
                                >
                                    Disable 2FA
                                </a>

                            </div>


                        <!-- =====================================
                             STATE B — Pending setup (QR shown)
                             ===================================== -->

                        <?php elseif ($hasPending): ?>

                            <ol class="twofa-steps">

                                <li>
                                    Open your authenticator app
                                    (Google Authenticator, Authy, 1Password, etc.)
                                </li>

                                <li>
                                    Scan the QR code below, or enter the
                                    secret key manually.
                                </li>

                                <li>
                                    Enter the 6-digit code shown in the app
                                    to confirm.
                                </li>

                            </ol>


                            <div class="twofa-setup-grid">

                                <div class="twofa-qr">

                                    <div id="twofa-qr-target"></div>

                                </div>

                                <div class="twofa-secret-block">

                                    <p>
                                        Can't scan the QR code? Enter this
                                        key manually into your app:
                                    </p>

                                    <div class="twofa-secret">
                                        <?= e($pendingSecret) ?>
                                    </div>

                                    <p style="margin-top:14px;">
                                        <strong>Account:</strong>
                                        <?= e($user["Email"]) ?>
                                        <br>
                                        <strong>Type:</strong>
                                        Time-based (TOTP), 6 digits, 30s
                                    </p>

                                </div>

                            </div>


                            <form
                                method="POST"
                                action="setup.php"
                                class="twofa-confirm-form"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(generateCSRFToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="confirm_setup"
                                >

                                <label for="code">
                                    Enter the 6-digit code from your app:
                                </label>

                                <div class="twofa-confirm-row">

                                    <input
                                        type="text"
                                        id="code"
                                        name="code"
                                        inputmode="numeric"
                                        pattern="[0-9]{6}"
                                        maxlength="6"
                                        autocomplete="one-time-code"
                                        placeholder="000000"
                                        required
                                        autofocus
                                    >

                                    <button
                                        type="submit"
                                        class="button"
                                    >
                                        Confirm &amp; Enable
                                    </button>

                                    <button
                                        type="submit"
                                        name="action"
                                        value="cancel_setup"
                                        class="button button-secondary"
                                        formnovalidate
                                    >
                                        Cancel
                                    </button>

                                </div>

                            </form>


                        <!-- =====================================
                             STATE C — 2FA off, no pending secret
                             ===================================== -->

                        <?php else: ?>

                            <p style="color:#64748b; font-size:14px; line-height:1.65; margin:0 0 8px;">
                                Two-factor authentication adds a second
                                step to your sign-in: your password plus
                                a 6-digit code from your phone. Even if
                                your password is stolen, an attacker
                                can't access your account without the code.
                            </p>

                            <div class="twofa-actions">

                                <form method="POST" action="setup.php">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(generateCSRFToken()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="begin_setup"
                                    >

                                    <button
                                        type="submit"
                                        class="button"
                                    >
                                        Enable Two-Factor Authentication
                                    </button>

                                </form>

                            </div>

                        <?php endif; ?>


                    </div>

                </div>


            </div>

        </div>

    </main>

</div>


<?php if ($hasPending): ?>

<script>

(function () {

    'use strict';

    var target = document.getElementById('twofa-qr-target');

    if (!target) return;

    var uri = <?= json_encode(totp_build_uri($pendingSecret, $user["Email"])) ?>;

    /* Wait for qrcode.js to load (it's loaded with defer) */
    function render() {

        if (typeof QRCode === 'undefined') {
            setTimeout(render, 50);
            return;
        }

        target.innerHTML = '';

        new QRCode(target, {
            text: uri,
            width: 200,
            height: 200,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', render);
    } else {
        render();
    }

})();

</script>

<?php endif; ?>


<?php require_once __DIR__ . "/../includes/footer.php"; ?>