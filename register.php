<?php

require_once __DIR__ . "/includes/database.php";
require_once __DIR__ . "/includes/password_policy.php";

$error = "";
$success = "";

$firstName = "";
$lastName = "";
$email = "";

$justRegistered = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstName = trim($_POST["FirstName"] ?? "");
    $lastName  = trim($_POST["LastName"] ?? "");
    $email     = trim($_POST["Email"] ?? "");
    $password  = $_POST["Password"] ?? "";
    $confirmPassword = $_POST["ConfirmPassword"] ?? "";


    /*
     * Validate required fields
     */

    if (
        $firstName === "" ||
        $lastName === "" ||
        $email === "" ||
        $password === "" ||
        $confirmPassword === ""
    ) {

        $error = "Please complete all required fields.";

    }


    /*
     * Validate email
     */

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    }


    /*
     * Check password against policy
     */

    else {

        $policyError = password_policy_check($password, [
            "email"      => $email,
            "first name" => $firstName,
            "last name"  => $lastName
        ]);

        if ($policyError !== null) {
            $error = $policyError;
        }
    }


    /*
     * Check password confirmation
     */

    if ($error === "" && $password !== $confirmPassword) {

        $error = "Passwords do not match.";

    }


    /*
     * Check whether email already exists
     */

    if ($error === "") {

        $stmt = $pdo->prepare("
            SELECT UserID
            FROM users
            WHERE Email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $existingUser = $stmt->fetch();


        if ($existingUser) {

            $error = "An account with this email address already exists.";

        }

    }


    /*
     * Create account
     */

    if ($error === "") {

        $role = "User";


        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );


        $stmt = $pdo->prepare("
            INSERT INTO users
            (
                FirstName,
                LastName,
                Email,
                Password,
                Role
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");


        try {

            $stmt->execute([
                $firstName,
                $lastName,
                $email,
                $hashedPassword,
                $role
            ]);


            $success = "Your account has been created successfully. Redirecting to sign in…";

            $justRegistered = true;

            $firstName = "";
            $lastName = "";
            $email = "";

        } catch (PDOException $e) {

            $error = "Unable to create your account. Please try again.";

        }

    }

}


/*
 * Global <head>
 */

$basePath  = "/visrs/";

$pageTitle = "Create Account";

require_once "includes/head.php";

?>


<body class="login-body">


<div class="login-page">


    <!-- =====================================================
         LANDSCAPE REGISTER CARD
         ===================================================== -->

    <div class="login-card login-card-register">


        <!-- =============================================
             LEFT PANEL — BRANDING
             ============================================= -->

        <aside class="login-card-hero">

            <div class="login-hero-top">

                <span class="login-hero-badge">SIGN UP</span>

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
             RIGHT PANEL — REGISTER FORM
             ============================================= -->

        <main class="login-card-form">


            <!-- HEADING -->

            <div class="login-heading">

                <h2>
                    Create an account
                </h2>

                <p>
                    Join VISRS to search vehicle records and manage ownership.
                </p>

            </div>


            <!-- ERROR -->

            <?php if ($error !== ""): ?>

                <div class="login-error">

                    <span class="login-error-icon">!</span>

                    <span><?= htmlspecialchars($error) ?></span>

                </div>

            <?php endif; ?>


            <!-- SUCCESS -->

            <?php if ($success !== ""): ?>

                <div class="login-success">

                    <span class="login-success-icon">✓</span>

                    <span><?= htmlspecialchars($success) ?></span>

                </div>

            <?php endif; ?>


            <!-- REGISTRATION FORM -->

            <form
                method="POST"
                action=""
                class="login-form"
            >


                <!-- NAME ROW -->

                <div class="login-field-row">

                    <!-- FIRST NAME -->

                    <div class="login-field">

                        <label for="FirstName">
                            First Name
                        </label>

                        <input
                            type="text"
                            id="FirstName"
                            name="FirstName"
                            value="<?= htmlspecialchars($firstName) ?>"
                            placeholder="Jane"
                            required
                            maxlength="100"
                            autocomplete="given-name"
                            class="auto-focus"
                        >

                    </div>


                    <!-- LAST NAME -->

                    <div class="login-field">

                        <label for="LastName">
                            Last Name
                        </label>

                        <input
                            type="text"
                            id="LastName"
                            name="LastName"
                            value="<?= htmlspecialchars($lastName) ?>"
                            placeholder="Doe"
                            required
                            maxlength="100"
                            autocomplete="family-name"
                        >

                    </div>

                </div>


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
                            placeholder="At least 8 characters"
                            required
                            minlength="8"
                            maxlength="128"
                            autocomplete="new-password"
                            data-password-strength
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

                    <small class="login-field-hint">
                        At least 8 characters. Avoid common passwords and don't include your name or email.
                    </small>

                </div>


                <!-- CONFIRM PASSWORD -->

                <div class="login-field">

                    <label for="ConfirmPassword">
                        Confirm Password
                    </label>

                    <div class="login-password-wrapper">

                        <input
                            type="password"
                            id="ConfirmPassword"
                            name="ConfirmPassword"
                            placeholder="Re-enter your password"
                            required
                            minlength="8"
                            maxlength="128"
                            autocomplete="new-password"
                        >

                        <button
                            type="button"
                            data-toggle-password="ConfirmPassword"
                            class="login-show-password"
                            aria-label="Show password"
                        >
                            Show
                        </button>

                    </div>

                </div>


                <!-- ROLE NOTE -->

                <div class="login-role-note">

                    <p class="login-role-note-label">
                        Account Type
                    </p>

                    <p class="login-role-note-text">
                        New accounts are registered as standard <strong>User</strong> accounts.
                        Administrative and staff roles can only be assigned by an authorized administrator.
                    </p>

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="login-submit"
                >
                    Create Account
                </button>


            </form>


            <!-- DIVIDER -->

            <div class="login-divider">

                <span>Already have an account?</span>

            </div>


            <!-- BACK TO LOGIN -->

            <a
                href="/visrs/login.php"
                class="login-register-btn"
            >
                Back to Sign In
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


<?php if ($justRegistered): ?>

<script>
setTimeout(function () {
    window.location.href = "/visrs/login.php";
}, 2500);
</script>

<?php endif; ?>


</body>

</html>