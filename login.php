<?php

/*
 * VISRS Login
 *
 * This page handles user authentication.
 * Do NOT include auth.php here because users
 * must be able to access the login page while
 * they are not authenticated.
 */

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}


require_once __DIR__ . "/includes/database.php";


$error = "";

$email = "";


/*
 * If the user is already logged in,
 * send them directly to the dashboard.
 */

if (isset($_SESSION["UserID"])) {

    header("Location: /visrs/dashboard.php");

    exit;
}


/*
 * Process login form.
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["Email"] ?? "");

    $password = $_POST["Password"] ?? "";


    /*
     * Validate required fields.
     */

    if ($email === "" || $password === "") {

        $error = "Please enter your email address and password.";

    }


    /*
     * Validate email format.
     */

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    }


    /*
     * Look up the account.
     */

    else {

        $stmt = $pdo->prepare("
            SELECT
                UserID,
                FirstName,
                LastName,
                Email,
                Password,
                Role
            FROM users
            WHERE Email = ?
            LIMIT 1
        ");

        $stmt->execute([
            $email
        ]);

        $user = $stmt->fetch();


        /*
         * Verify the account and password.
         */

        if (
            $user &&
            password_verify(
                $password,
                $user["Password"]
            )
        ) {

            /*
             * Regenerate the session ID after
             * successful authentication.
             */

            session_regenerate_id(true);


            /*
             * Store the user's information in
             * the session.
             */

            $_SESSION["UserID"] = (int) $user["UserID"];

            $_SESSION["FirstName"] = $user["FirstName"];

            $_SESSION["LastName"] = $user["LastName"];

            $_SESSION["Email"] = $user["Email"];

            $_SESSION["Role"] = $user["Role"];


            /*
             * Redirect to dashboard.
             */

            header("Location: /visrs/dashboard.php");

            exit;

        } else {

            /*
             * Do not reveal whether the email
             * or password was incorrect.
             */

            $error = "Invalid email address or password.";

        }

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Login - VISRS
    </title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

</head>


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


        <!-- LOGIN HEADING -->

        <div
            style="
                margin-bottom:25px;
            "
        >

            <h2
                style="
                    margin:0 0 8px 0;
                "
            >
                Welcome Back
            </h2>

            <p
                style="
                    margin:0;
                    color:#6b7280;
                "
            >
                Sign in to access the VISRS system.
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

                <strong>
                    Login Error
                </strong>

                <p
                    style="
                        margin:6px 0 0 0;
                    "
                >
                    <?= htmlspecialchars($error) ?>
                </p>

            </div>

        <?php endif; ?>


        <!-- LOGIN FORM -->

        <form
            method="POST"
            action=""
        >


            <!-- EMAIL -->

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


            <!-- PASSWORD -->

            <div class="form-group">

                <label for="Password">
                    Password
                </label>

                <div
                    style="
                        position:relative;
                    "
                >

                    <input
                        type="password"
                        id="Password"
                        name="Password"
                        required
                        autocomplete="current-password"
                        style="
                            padding-right:80px;
                        "
                    >

                    <button
                        type="button"
                        data-toggle-password="Password"
                        style="
                            position:absolute;
                            right:10px;
                            top:50%;
                            transform:translateY(-50%);
                            border:none;
                            background:transparent;
                            cursor:pointer;
                            color:#374151;
                            font-weight:600;
                        "
                    >
                        Show
                    </button>

                </div>

            </div>


            <!-- LOGIN BUTTON -->

            <button
                type="submit"
                class="button"
                style="
                    width:100%;
                    margin-top:10px;
                "
            >
                Login
            </button>


        </form>


        <!-- REGISTRATION -->

        <div
            style="
                text-align:center;
                margin-top:25px;
                padding-top:20px;
                border-top:1px solid #e5e7eb;
            "
        >

            <p
                style="
                    margin:0 0 10px 0;
                    color:#6b7280;
                "
            >
                Don't have an account?
            </p>

            <a
                href="/visrs/register.php"
                class="button button-secondary"
            >
                Create Account
            </a>

        </div>


    </div>


</div>


<!-- VISRS GLOBAL JAVASCRIPT -->

<script
    src="/visrs/js/app.js"
    defer
></script>


</body>

</html>