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

require_once __DIR__ . "/includes/remember.php";

require_once __DIR__ . "/includes/csrf.php";

require_once __DIR__ . "/includes/ratelimit.php";


/*
 * Housekeeping: purge expired remember-me tokens
 * and old rate-limit attempts.
 *
 * Runs on roughly 1 in 20 page loads so the cost is
 * negligible and no cron job is required.
 */

if (random_int(1, 20) === 1) {

    remember_cleanup($pdo);

    rate_limit_cleanup($pdo);

}


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

    /*
     * Validate CSRF token on the login form.
     */

    if (!verifyCSRFToken($_POST["csrf_token"] ?? "")) {

        $error = "Session expired. Please try again.";

    }

    else {

        $email = trim($_POST["Email"] ?? "");

        $password = $_POST["Password"] ?? "";


        /*
         * Check rate limit before doing anything else.
         */

        $clientIP = rate_limit_client_ip();

        $lock = rate_limit_check($pdo, $email, $clientIP);

        if ($lock !== null) {

            $error = $lock["message"];

        }

        else {

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
                    Role,
                    ProfileImage
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
                 * Clear failed attempts on success.
                 */

                rate_limit_clear($pdo, $email);


                /*
                 * Regenerate the session ID after
                 * successful authentication.
                 */

                session_regenerate_id(true);


                /*
                 * Remember-me: issue a persistent token
                 * if the user asked to be remembered.
                 */

                if (!empty($_POST["RememberMe"])) {

                    remember_issue($pdo, (int) $user["UserID"]);

                }


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
                 * FIX: Store profile picture in session so the
                 * topbar can show it immediately after login.
                 */

                $_SESSION["ProfileImage"] = $user["ProfileImage"] ?? "";


                /*
                 * Redirect to dashboard.
                 */

                header("Location: /visrs/dashboard.php");

                exit;

            } else {

                /*
                 * Record the failed attempt.
                 */

                rate_limit_record_failure($pdo, $email, $clientIP);


                /*
                 * Do not reveal whether the email
                 * or password was incorrect.
                 */

                $error = "Invalid email address or password.";

            }

        }


        }

    }

}


/*
 * Global <head>
 */

$basePath  = "/visrs/";

$pageTitle = "Login - VISRS";

require_once "includes/head.php";

?>


<body class="login-body">


<div class="login-page">


    <!-- =====================================================
         LANDSCAPE LOGIN CARD
         ===================================================== -->

    <div class="login-card">


        <!-- =============================================
             LEFT PANEL — BRANDING
             ============================================= -->

        <aside class="login-card-hero">

            <div class="login-hero-top">

                <span class="login-hero-badge">ADMIN</span>

            </div>

            <div class="login-hero-body">

                <h1 class="login-hero-title">VISRS</h1>

                <p class="login-hero-subtitle">
                    Vehicle Information Search &amp; Retrieval System
                </p>

                <ul class="login-hero-features">

                    <li>
                        <span class="login-hero-check">✓</span>
                        <span>Search vehicle records</span>
                    </li>

                    <li>
                        <span class="login-hero-check">✓</span>
                        <span>Report stolen vehicles</span>
                    </li>

                    <li>
                        <span class="login-hero-check">✓</span>
                        <span>Track ownership history</span>
                    </li>

                </ul>

            </div>

            <p class="login-hero-footer">
                &copy; <?= date("Y") ?> VISRS. All rights reserved.
            </p>

        </aside>


        <!-- =============================================
             RIGHT PANEL — LOGIN FORM
             ============================================= -->

        <main class="login-card-form">


            <!-- HEADING -->

            <div class="login-heading">

                <h2>
                    Welcome back
                </h2>

                <p>
                    Sign in to continue to your dashboard.
                </p>

            </div>


            <!-- ERROR -->

            <?php if ($error !== ""): ?>

                <div class="login-error">

                    <span class="login-error-icon">!</span>

                    <span><?= htmlspecialchars($error) ?></span>

                </div>

            <?php endif; ?>


            <!-- LOGIN FORM -->

            <form
                method="POST"
                action=""
                class="login-form"
            >

                <!-- CSRF TOKEN -->

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(generateCSRFToken()) ?>"
                >


                <!-- EMAIL -->

                <div class="login-field">

                    <label for="Email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="Email"
                        name="Email"
                        value="<?= htmlspecialchars($email) ?>"
                        placeholder="you@example.com"
                        required
                        maxlength="150"
                        autocomplete="email"
                        class="auto-focus"
                    >

                </div>


                <!-- PASSWORD -->

                <div class="login-field">

                    <label for="Password">
                        Password
                    </label>

                    <div class="login-password-wrapper">

                        <input
                            type="password"
                            id="Password"
                            name="Password"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >

                        <button
                            type="button"
                            data-toggle-password="Password"
                            class="login-show-password"
                            aria-label="Show password"
                        >
                            Show
                        </button>

                    </div>

                </div>


                <!-- REMEMBER / FORGOT -->

                <div class="login-options">

                    <label class="login-remember">

                        <input
                            type="checkbox"
                            name="RememberMe"
                            value="1"
                        >

                        <span>Remember me</span>

                    </label>

                    <a
                        href="/visrs/forgot-password.php"
                        class="login-forgot"
                    >
                        Forgot password?
                    </a>

                </div>


                <!-- LOGIN BUTTON -->

                <button
                    type="submit"
                    class="login-submit"
                >
                    Sign in
                </button>


            </form>


            <!-- DIVIDER -->

            <div class="login-divider">

                <span>New to VISRS?</span>

            </div>


            <!-- REGISTRATION -->

            <a
                href="/visrs/register.php"
                class="login-register-btn"
            >
                Create an account
            </a>


            <!-- FOOTER -->

            <div class="login-footer">

                <p>
                    &copy; <?= date("Y") ?> VISRS &nbsp;·&nbsp;
                    <a href="/visrs/support.php">Support</a>
                    &nbsp;·&nbsp;
                    <a href="/visrs/privacy.php">Privacy</a>
                </p>

            </div>


        </main>


    </div>


</div>


<!-- VISRS GLOBAL JAVASCRIPT -->

<script
    src="/visrs/js/app.js"
    defer
></script>


</body>

</html>