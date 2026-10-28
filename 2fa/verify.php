<?php

/*
 * VISRS — 2FA Verify (second login step)
 *
 * Shown after a correct password when the user has 2FA
 * enabled. Accepts either a 6-digit TOTP code or one of
 * the user's recovery codes.
 *
 * Does NOT require auth.php — the user is not fully logged
 * in yet. Instead it checks $_SESSION["2fa_pending_user"].
 */

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/totp.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/../includes/functions.php";

$basePath  = "../";
$pageTitle = "Verify - VISRS";


/* ---------- Ensure a pending user exists ---------- */

$pendingUserID = (int) ($_SESSION["2fa_pending_user"] ?? 0);

if ($pendingUserID <= 0) {

    header("Location: /visrs/login.php");
    exit;
}


/* ---------- If already fully logged in, skip ---------- */

if (isset($_SESSION["UserID"])) {

    header("Location: /visrs/dashboard.php");
    exit;
}


/* ---------- Load the pending user ---------- */

$stmt = $pdo->prepare("
    SELECT
        UserID,
        FirstName,
        LastName,
        Email,
        Role,
        ProfileImage,
        TwoFactorSecret,
        TwoFactorEnabled
    FROM users
    WHERE UserID = ?
    LIMIT 1
");

$stmt->execute([$pendingUserID]);
$user = $stmt->fetch();

if (!$user || (int) $user["TwoFactorEnabled"] !== 1 || empty($user["TwoFactorSecret"])) {

    /* Pending state is broken — reset and send to login */
    unset($_SESSION["2fa_pending_user"]);

    header("Location: /visrs/login.php");
    exit;
}


/* ---------- Handle POST ---------- */

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verifyCSRFToken($_POST["csrf_token"] ?? "")) {

        $error = "Session expired. Please try again.";

    } else {

        $code = strtoupper(trim($_POST["code"] ?? ""));
        $code = str_replace(["-", " "], "", $code);

        if ($code === "") {

            $error = "Please enter the code from your authenticator app.";

        } else {

            $verified = false;
            $usedRecovery = false;


            /* ---------- Try TOTP first (6 digits) ---------- */

            if (preg_match('/^\d{6}$/', $code)) {

                if (totp_verify($user["TwoFactorSecret"], $code)) {
                    $verified = true;
                }
            }


            /* ---------- Try recovery code (10 chars) ---------- */

            if (!$verified && preg_match('/^[A-F0-9]{10}$/', $code)) {

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

                    $verified = true;
                    $usedRecovery = true;

                    /* Mark the recovery code as used */
                    $markUsed = $pdo->prepare("
                        UPDATE recovery_codes
                        SET UsedAt = NOW()
                        WHERE CodeID = ?
                    ");

                    $markUsed->execute([$rc["CodeID"]]);
                }
            }


            /* ---------- Apply verification result ---------- */

            if ($verified) {

                /* Promote to full login */
                session_regenerate_id(true);

                $_SESSION["UserID"]       = (int) $user["UserID"];
                $_SESSION["FirstName"]    = $user["FirstName"];
                $_SESSION["LastName"]     = $user["LastName"];
                $_SESSION["Email"]        = $user["Email"];
                $_SESSION["Role"]         = $user["Role"];
                $_SESSION["ProfileImage"] = $user["ProfileImage"] ?? "";

                unset($_SESSION["2fa_pending_user"]);


                /*
                 * NEW: Save the user's first name to a cookie 
                 * so the login page can greet them on their next visit.
                 */

                setcookie("last_logged_in_user", $user["FirstName"], time() + (86400 * 30), "/");


                /*
                 * Remember-me: if the user checked the box on the
                 * password step, issue the persistent token now
                 * that the second factor has passed.
                 */
                if (!empty($_SESSION["2fa_pending_remember"])) {

                    require_once __DIR__ . "/../includes/remember.php";

                    remember_issue($pdo, (int) $user["UserID"]);
                }

                unset($_SESSION["2fa_pending_remember"]);

                /* Optional: note if recovery was used, for audit/alert */
                if ($usedRecovery) {
                    $_SESSION["2fa_used_recovery"] = true;
                }

                header("Location: /visrs/dashboard.php");
                exit;

            } else {

                /* Count the failure for rate limiting */
                $logFail = $pdo->prepare("
                    INSERT INTO login_attempts (Email, IPAddress)
                    VALUES (?, ?)
                ");

                $logFail->execute([
                    $user["Email"],
                    $_SERVER["REMOTE_ADDR"] ?? "0.0.0.0"
                ]);

                $error = "That code is incorrect. Please try again.";
            }
        }
    }
}

require_once __DIR__ . "/../includes/head.php";

?>


<style>

    .twofa-verify-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        background: #f5f7fb;
        box-sizing: border-box;
    }

    .twofa-verify-card {
        width: 100%;
        max-width: 420px;
        padding: 36px 32px 30px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        text-align: center;
    }

    .twofa-verify-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 64px;
        height: 64px;
        margin-bottom: 18px;
        background: #dbeafe;
        color: #1e40af;
        border-radius: 50%;
        font-size: 28px;
        line-height: 1;
    }

    .twofa-verify-card h1 {
        margin: 0 0 8px;
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.4px;
    }

    .twofa-verify-card p {
        margin: 0 0 24px;
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
    }

    .twofa-verify-card p strong {
        color: #0f172a;
    }

    .twofa-verify-error {
        padding: 12px 14px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 10px;
        color: #b91c1c;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 18px;
        text-align: left;
    }

    .twofa-verify-form label {
        display: block;
        margin-bottom: 8px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        color: #334155;
        text-align: left;
    }

    .twofa-verify-form input[type="text"] {
        width: 100%;
        padding: 14px 16px;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-family: 'Courier New', monospace;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 6px;
        text-align: center;
        color: #0f172a;
        outline: none;
        box-sizing: border-box;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .twofa-verify-form input[type="text"]:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .twofa-verify-form button {
        width: 100%;
        min-height: 46px;
        margin-top: 16px;
        background: linear-gradient(90deg, #ea580c 0%, #f97316 100%);
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-family: inherit;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 0.2px;
        cursor: pointer;
        transition: filter 0.15s ease, box-shadow 0.15s ease;
    }

    .twofa-verify-form button:hover {
        filter: brightness(1.05);
        box-shadow: 0 8px 20px -6px rgba(249, 115, 22, 0.5);
    }

    .twofa-verify-help {
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid #f1f5f9;
        color: #94a3b8;
        font-size: 12px;
        line-height: 1.6;
    }

    .twofa-verify-help a {
        color: #2563eb;
        text-decoration: none;
        font-weight: 600;
    }

    .twofa-verify-help a:hover {
        text-decoration: underline;
    }

    /* Dark mode */
    [data-theme="dark"] .twofa-verify-page {
        background: #0f172a;
    }

    [data-theme="dark"] .twofa-verify-card {
        background: #1e293b;
        border-color: #334155;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4);
    }

    [data-theme="dark"] .twofa-verify-icon {
        background: #1e3a8a;
        color: #bfdbfe;
    }

    [data-theme="dark"] .twofa-verify-card h1 {
        color: #f1f5f9;
    }

    [data-theme="dark"] .twofa-verify-card p {
        color: #94a3b8;
    }

    [data-theme="dark"] .twofa-verify-card p strong {
        color: #e2e8f0;
    }

    [data-theme="dark"] .twofa-verify-form label {
        color: #cbd5e1;
    }

    [data-theme="dark"] .twofa-verify-form input[type="text"] {
        background: #0f172a;
        border-color: #334155;
        color: #f1f5f9;
    }

    [data-theme="dark"] .twofa-verify-form input[type="text"]:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.20);
    }

    [data-theme="dark"] .twofa-verify-help {
        border-top-color: #334155;
        color: #64748b;
    }

    [data-theme="dark"] .twofa-verify-help a {
        color: #60a5fa;
    }

</style>


<body>

<div class="twofa-verify-page">

    <div class="twofa-verify-card">

        <div class="twofa-verify-icon" aria-hidden="true">
            🔐
        </div>

        <h1>Two-Factor Verification</h1>

        <p>
            Enter the 6-digit code from your authenticator app
            for <strong><?= e($user["Email"]) ?></strong>.
        </p>

        <?php if ($error !== ""): ?>

            <div class="twofa-verify-error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <form
            method="POST"
            action=""
            class="twofa-verify-form"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(generateCSRFToken()) ?>"
            >

            <label for="code">Verification Code</label>

            <input
                type="text"
                id="code"
                name="code"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="11"
                placeholder="000000"
                autofocus
                required
            >

            <button type="submit">
                Verify
            </button>

        </form>

        <div class="twofa-verify-help">

            Lost your device?
            <a href="/visrs/login.php">Use a recovery code instead</a> —
            enter it in the same field.

        </div>

    </div>

</div>


<script>

(function () {

    'use strict';

    var input = document.getElementById('code');
    if (!input) return;

    /* Force digits and auto-format the display.
       Recovery codes are 10 hex chars — we also allow those. */

    input.addEventListener('input', function () {

        var v = input.value.toUpperCase();

        /* Strip anything that's not A-Z or 0-9 */
        v = v.replace(/[^A-Z0-9]/g, '');

        input.value = v;
    });

    /* Auto-submit when a 6-digit code is typed (convenience) */
    input.addEventListener('input', function () {

        var v = input.value;

        if (/^\d{6}$/.test(v)) {

            /* Let the user see the code briefly before submitting */
            setTimeout(function () {
                if (input.value === v) {
                    input.form.requestSubmit();
                }
            }, 200);
        }
    });

})();

</script>


</body>

</html>