<?php

/*
 * VISRS CSRF Helpers
 *
 * Self-contained CSRF token generation and validation.
 * Loaded by both auth.php (for authenticated pages)
 * and login.php (for the login form itself).
 */

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}


/**
 * Generate (or reuse) a CSRF token for this session.
 */
function generateCSRFToken(): string
{
    if (empty($_SESSION["csrf_token"])) {

        $_SESSION["csrf_token"] = bin2hex(
            random_bytes(32)
        );

    }

    return $_SESSION["csrf_token"];
}


/**
 * Validate a submitted CSRF token.
 */
function verifyCSRFToken($token): bool
{
    if (
        empty($token) ||
        empty($_SESSION["csrf_token"])
    ) {

        return false;
    }

    return hash_equals(
        $_SESSION["csrf_token"],
        $token
    );
}


/**
 * Require a valid CSRF token for the current POST request.
 * On failure: 403 + a small error page + exit.
 */
function requireValidCSRF(): void
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        return;
    }

    $token = $_POST["csrf_token"] ?? "";

    if (!verifyCSRFToken($token)) {

        http_response_code(403);

        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Security Error - VISRS</title>
            <link rel="stylesheet" href="/visrs/css/style.css">
        </head>
        <body>
        <div class="layout">
            <main class="main-content" style="margin-left:0;width:100%;">
                <div class="content" style="max-width:700px;margin:80px auto;">
                    <div class="card">
                        <div style="background:#fee2e2;color:#991b1b;padding:14px;border-radius:8px;margin-bottom:20px;">
                            <strong>Security Error</strong>
                        </div>
                        <h2>Invalid security token.</h2>
                        <p>Your request could not be completed because the security token was missing or invalid.</p>
                        <p>Please return to the previous page and try the action again.</p>
                        <a href="/visrs/dashboard.php" class="button">Return to Dashboard</a>
                    </div>
                </div>
            </main>
        </div>
        </body>
        </html>
        <?php

        exit;
    }
}

?>