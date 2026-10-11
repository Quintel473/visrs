<?php

/*
 * VISRS — 2FA Recovery Codes
 *
 * Shows how many recovery codes remain unused and lets the
 * user regenerate them (invalidating the old batch). A
 * fresh batch is displayed once via session, exactly like
 * the setup flow.
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/totp.php";

$basePath   = "../";
$activePage = "settings";

$pageTitle = "Recovery Codes - VISRS";

$currentUserID = (int) currentUserId();

$errors = [];


/* ---------- Load current user ---------- */

$stmt = $pdo->prepare("
    SELECT
        UserID,
        Email,
        Password,
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


/* ---------- Must have 2FA enabled to manage recovery codes ---------- */

if ((int) $user["TwoFactorEnabled"] !== 1) {

    header("Location: /visrs/2fa/setup.php");
    exit;
}


/* ---------- Count remaining unused codes ---------- */

$countStmt = $pdo->prepare("
    SELECT
        COUNT(*)                                  AS Total,
        SUM(CASE WHEN UsedAt IS NULL THEN 1 ELSE 0 END) AS Remaining
    FROM recovery_codes
    WHERE UserID = ?
");

$countStmt->execute([$currentUserID]);
$counts = $countStmt->fetch();

$totalCodes     = (int) ($counts["Total"] ?? 0);
$remainingCodes = (int) ($counts["Remaining"] ?? 0);


/* ---------- Handle POST (regenerate) ---------- */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    requireValidCSRF();

    $action = $_POST["action"] ?? "";


    if ($action === "regenerate") {

        $password = $_POST["password"] ?? "";

        if ($password === "") {

            $errors[] = "Please enter your password.";

        } elseif (!password_verify($password, $user["Password"])) {

            $errors[] = "Your password is incorrect.";

        } else {

            $plainCodes = totp_generate_recovery_codes(8);

            try {

                $pdo->beginTransaction();


                /* Delete all existing codes for this user */
                $del = $pdo->prepare("
                    DELETE FROM recovery_codes WHERE UserID = ?
                ");

                $del->execute([$currentUserID]);


                /* Insert fresh codes */
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


                /* One-time display via session */
                $_SESSION["2fa_recovery_codes"] = $plainCodes;

                header("Location: recovery.php?regenerated=1");
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


/* ---------- Pull one-time codes from session ---------- */

$showRecoveryCodes = $_SESSION["2fa_recovery_codes"] ?? null;

if ($showRecoveryCodes !== null) {
    unset($_SESSION["2fa_recovery_codes"]);
}

require_once __DIR__ . "/../includes/head.php";

?>


<style>

    .twofa-page {
        max-width: 620px;
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

    .twofa-count {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px 20px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .twofa-count-number {
        font-size: 36px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
        flex-shrink: 0;
    }

    .twofa-count-label {
        color: #64748b;
        font-size: 14px;
        line-height: 1.5;
    }

    .twofa-count-label strong {
        display: block;
        color: #0f172a;
        font-size: 15px;
        margin-bottom: 2px;
    }

    .twofa-count.warning {
        background: #fff7ed;
        border-color: #fed7aa;
    }

    .twofa-count.warning .twofa-count-number {
        color: #c2410c;
    }

    .twofa-count.warning .twofa-count-label strong {
        color: #9a3412;
    }

    .twofa-count.warning .twofa-count-label {
        color: #9a3412;
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
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.10);
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

    .twofa-error ul {
        margin: 8px 0 0 20px;
        padding: 0;
    }

    .twofa-alert-success {
        padding: 13px 15px;
        background: #dcfce7;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        color: #166534;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .twofa-actions {
        display: flex;
        gap: 10px;
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
        background: #0f172a;
        border-color: #334155;
    }

    [data-theme="dark"] .twofa-card-header h2 { color: #f1f5f9; }
    [data-theme="dark"] .twofa-card-header p  { color: #94a3b8; }

    [data-theme="dark"] .twofa-count {
        background: #0f172a;
        border-color: #334155;
    }

    [data-theme="dark"] .twofa-count-number { color: #f1f5f9; }
    [data-theme="dark"] .twofa-count-label  { color: #94a3b8; }
    [data-theme="dark"] .twofa-count-label strong { color: #f1f5f9; }

    [data-theme="dark"] .twofa-count.warning {
        background: #431407;
        border-color: #7c2d12;
    }

    [data-theme="dark"] .twofa-count.warning .twofa-count-number { color: #fdba74; }
    [data-theme="dark"] .twofa-count.warning .twofa-count-label,
    [data-theme="dark"] .twofa-count.warning .twofa-count-label strong { color: #fdba74; }

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

    [data-theme="dark"] .twofa-field label { color: #cbd5e1; }

    [data-theme="dark"] .twofa-field input {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }

    [data-theme="dark"] .twofa-error {
        background: #7f1d1d;
        border-color: #991b1b;
        color: #fecaca;
    }

    [data-theme="dark"] .twofa-alert-success {
        background: #064e3b;
        border-color: #065f46;
        color: #6ee7b7;
    }

    @media (max-width: 600px) {
        .twofa-recovery-codes {
            grid-template-columns: 1fr;
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

                    <h1>Recovery Codes</h1>

                    <p>
                        One-time codes that let you sign in if you lose access to your authenticator app.
                    </p>

                </div>


                <!-- =============================================
                     ONE-TIME DISPLAY (after regeneration)
                     ============================================= -->

                <?php if (!empty($showRecoveryCodes)): ?>

                    <div class="twofa-card">

                        <div class="twofa-card-header">

                            <h2>⚠️ Save Your New Recovery Codes</h2>

                            <p>
                                These codes will only be shown once.
                            </p>

                        </div>

                        <div class="twofa-card-body">

                            <div class="twofa-warning">

                                <strong>
                                    Your old codes no longer work.
                                </strong>

                                Write these down or print them now.
                                Each code can be used once.

                            </div>

                            <div class="twofa-recovery-codes">

                                <?php foreach ($showRecoveryCodes as $rc): ?>

                                    <div class="twofa-recovery-code">
                                        <?= e($rc) ?>
                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- =============================================
                     CURRENT COUNT
                     ============================================= -->

                <div class="twofa-card">

                    <div class="twofa-card-header">

                        <h2>Current Codes</h2>

                        <p>
                            How many unused codes you have left.
                        </p>

                    </div>

                    <div class="twofa-card-body">

                        <div class="twofa-count<?= $remainingCodes <= 2 ? " warning" : "" ?>">

                            <div class="twofa-count-number">
                                <?= $remainingCodes ?>
                            </div>

                            <div class="twofa-count-label">

                                <strong>
                                    of <?= $totalCodes ?> unused
                                </strong>

                                <?php if ($remainingCodes <= 2): ?>

                                    You're running low. Regenerate a
                                    fresh batch now.

                                <?php else: ?>

                                    Each code can be used once to sign in
                                    if you lose your device.

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =============================================
                     REGENERATE
                     ============================================= -->

                <div class="twofa-card">

                    <div class="twofa-card-header">

                        <h2>Regenerate Recovery Codes</h2>

                        <p>
                            Replaces all existing codes with a fresh batch of 8.
                        </p>

                    </div>

                    <div class="twofa-card-body">

                        <?php if (!empty($errors)): ?>

                            <div class="twofa-error">

                                <strong>Please correct the following:</strong>

                                <ul>
                                    <?php foreach ($errors as $err): ?>
                                        <li><?= e($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>

                            </div>

                        <?php endif; ?>

                        <div class="twofa-warning">

                            <strong>
                                Heads up
                            </strong>

                            Any unused codes from your current batch will
                            stop working the moment you regenerate.

                        </div>

                        <form method="POST" action="">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(generateCSRFToken()) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="regenerate"
                            >

                            <div class="twofa-field">

                                <label for="password">
                                    Confirm Your Password
                                </label>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                >

                            </div>

                            <div class="twofa-actions">

                                <button
                                    type="submit"
                                    class="button"
                                    onclick="return confirm('Regenerate recovery codes? Existing codes will stop working.');"
                                >
                                    Regenerate Codes
                                </button>

                                <a
                                    href="/visrs/2fa/setup.php"
                                    class="button button-secondary"
                                >
                                    Back to 2FA Settings
                                </a>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>


<?php require_once __DIR__ . "/../includes/footer.php"; ?>