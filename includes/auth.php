<?php

/*
 * Start a secure session.
 */

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}


/*
 * Require the user to be logged in.
 */

if (!isset($_SESSION["UserID"])) {

    header("Location: /visrs/login.php");

    exit;
}


/*
 * Make sure the session contains a valid role.
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
 * Restrict a page to specific roles.
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
                style="
                    margin-left:0;
                    width:100%;
                "
            >

                <div
                    class="content"
                    style="
                        max-width:700px;
                        margin:80px auto;
                    "
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

                            <strong>
                                Access Denied
                            </strong>

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
 * Determine whether the current user can
 * manage vehicle records.
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
 * Determine whether the current user can
 * manage owner records.
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
 * Determine whether the current user can
 * manage ownership history.
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
 * Determine whether the current user can
 * manage insurance.
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
 * Determine whether the current user can
 * manage accident records.
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
 * Determine whether the current user is an administrator.
 */

function isAdmin()
{

    return isset($_SESSION["Role"]) &&
        $_SESSION["Role"] === "Admin";
}

?>