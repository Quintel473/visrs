<?php

/*
 * VISRS Authentication & Authorization
 *
 * Handles:
 * - Secure session initialization
 * - Login protection
 * - Role validation
 * - Role-based access control
 * - CSRF protection
 */


/*
|--------------------------------------------------------------------------
| Secure Session Configuration
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| Require Login
|--------------------------------------------------------------------------
|
| Every protected page that includes this file requires
| the user to be authenticated.
|
*/

if (!isset($_SESSION["UserID"])) {

    header("Location: /visrs/login.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate Session Role
|--------------------------------------------------------------------------
|
| Only valid VISRS roles are allowed to maintain an
| authenticated session.
|
*/

$validRoles = [
    "Admin",
    "Police",
    "Seller",
    "User"
];


if (
    !isset($_SESSION["Role"]) ||
    !in_array(
        $_SESSION["Role"],
        $validRoles,
        true
    )
) {

    session_unset();

    session_destroy();

    header("Location: /visrs/login.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| Role Authorization
|--------------------------------------------------------------------------
|
| Example:
|
| requireRole(["Admin"]);
|
| requireRole(["Admin", "Police"]);
|
*/

function requireRole($allowedRoles)
{
    if (
        !isset($_SESSION["Role"]) ||
        !in_array(
            $_SESSION["Role"],
            $allowedRoles,
            true
        )
    ) {

        http_response_code(403);

        ?>
        <!DOCTYPE html>

        <html lang="en">

        <head>

            <meta charset="UTF-8">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1.0"
            >

            <title>Access Denied - VISRS</title>

            <link
                rel="stylesheet"
                href="/visrs/css/style.css"
            >

        </head>

        <body>

        <div class="layout">

            <main
                class="main-content"
                style="margin-left:0;width:100%;"
            >

                <div
                    class="content"
                    style="max-width:700px;margin:80px auto;"
                >

                    <div class="card">

                        <div
                            style="
                                background:#fee2e2;
                                color:#991b1b;
                                padding:14px;
                                border-radius:8px;
                                margin-bottom:20px;
                            "
                        >
                            <strong>Access Denied</strong>
                        </div>

                        <h2>
                            You do not have permission to access this page.
                        </h2>

                        <p>
                            Your current account role does not have
                            permission to perform this action.
                        </p>

                        <a
                            href="/visrs/dashboard.php"
                            class="button"
                        >
                            Return to Dashboard
                        </a>

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


/*
|--------------------------------------------------------------------------
| Vehicle Permissions
|--------------------------------------------------------------------------
*/

function canManageVehicles()
{
    return isset($_SESSION["Role"]) &&
        in_array(
            $_SESSION["Role"],
            [
                "Admin",
                "Police",
                "Seller"
            ],
            true
        );
}


/*
|--------------------------------------------------------------------------
| Owner Permissions
|--------------------------------------------------------------------------
*/

function canManageOwners()
{
    return isset($_SESSION["Role"]) &&
        in_array(
            $_SESSION["Role"],
            [
                "Admin",
                "Police"
            ],
            true
        );
}


/*
|--------------------------------------------------------------------------
| Ownership History Permissions
|--------------------------------------------------------------------------
*/

function canManageOwnershipHistory()
{
    return isset($_SESSION["Role"]) &&
        in_array(
            $_SESSION["Role"],
            [
                "Admin",
                "Police"
            ],
            true
        );
}


/*
|--------------------------------------------------------------------------
| Insurance Permissions
|--------------------------------------------------------------------------
*/

function canManageInsurance()
{
    return isset($_SESSION["Role"]) &&
        in_array(
            $_SESSION["Role"],
            [
                "Admin",
                "Police"
            ],
            true
        );
}


/*
|--------------------------------------------------------------------------
| Accident Permissions
|--------------------------------------------------------------------------
*/

function canManageAccidents()
{
    return isset($_SESSION["Role"]) &&
        in_array(
            $_SESSION["Role"],
            [
                "Admin",
                "Police"
            ],
            true
        );
}


/*
|--------------------------------------------------------------------------
| Administrator Check
|--------------------------------------------------------------------------
*/

function isAdmin()
{
    return isset($_SESSION["Role"]) &&
        $_SESSION["Role"] === "Admin";
}


/*
|--------------------------------------------------------------------------
| CSRF Token Generation
|--------------------------------------------------------------------------
|
| Creates a cryptographically secure token for the
| current authenticated session.
|
*/

function generateCSRFToken()
{
    if (
        empty($_SESSION["csrf_token"])
    ) {

        $_SESSION["csrf_token"] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION["csrf_token"];
}


/*
|--------------------------------------------------------------------------
| CSRF Token Validation
|--------------------------------------------------------------------------
*/

function verifyCSRFToken($token)
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


/*
|--------------------------------------------------------------------------
| Require Valid CSRF Token
|--------------------------------------------------------------------------
|
| This should be called before processing POST requests
| that modify system data.
|
*/

function requireValidCSRF()
{
    if (
        $_SERVER["REQUEST_METHOD"] !== "POST"
    ) {

        return;
    }


    $token = $_POST["csrf_token"] ?? "";


    if (
        !verifyCSRFToken($token)
    ) {

        http_response_code(403);

        ?>
        <!DOCTYPE html>

        <html lang="en">

        <head>

            <meta charset="UTF-8">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1.0"
            >

            <title>Security Error - VISRS</title>

            <link
                rel="stylesheet"
                href="/visrs/css/style.css"
            >

        </head>

        <body>

        <div class="layout">

            <main
                class="main-content"
                style="margin-left:0;width:100%;"
            >

                <div
                    class="content"
                    style="max-width:700px;margin:80px auto;"
                >

                    <div class="card">

                        <div
                            style="
                                background:#fee2e2;
                                color:#991b1b;
                                padding:14px;
                                border-radius:8px;
                                margin-bottom:20px;
                            "
                        >
                            <strong>Security Error</strong>
                        </div>

                        <h2>
                            Invalid security token.
                        </h2>

                        <p>
                            Your request could not be completed because
                            the security token was missing or invalid.
                        </p>

                        <p>
                            Please return to the previous page and
                            try the action again.
                        </p>

                        <a
                            href="/visrs/dashboard.php"
                            class="button"
                        >
                            Return to Dashboard
                        </a>

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