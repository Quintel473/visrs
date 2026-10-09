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

$pageTitle = "Create Account - VISRS";

require_once "includes/head.php";

?>


<body>


<div class="login-page">


    <!-- =====================================================
         LEFT — HERO / BRANDING
         ===================================================== -->

    <div class="login-hero">

        <div class="login-hero-overlay"></div>

        <div class="login-hero-content">

            <h1 class="login-hero-title">
                VISRS
            </h1>

            <p class="login-hero-subtitle">
                Vehicle Information Search & Retrieval System
            </p>

            <ul class="login-hero-features">

                <li>
                    <span class="login-hero-icon">🔍</span>
                    <span>Search vehicle records</span>
                </li>

                <li>
                    <span class="login-hero-icon">🛡️</span>
                    <span>Report stolen vehicles</span>
                </li>

                <li>
                    <span class="login-hero-icon">📋</span>
                    <span>Track ownership history</span>
                </li>

            </ul>

        </div>

    </div>


    <!-- =====================================================
         RIGHT — REGISTER FORM
         ===================================================== -->

    <div class="login-form-panel">

        <div class="login-form-wrapper">


            <div class="login-brandmark">

                <span class="login-brandmark-text">
                    VISRS
                </span>

            </div>


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

                <div class="error-box">

                    <strong>
                        Registration Error
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


            <!-- SUCCESS -->

            <?php if ($success !== ""): ?>

                <div class="settings-alert settings-alert-success" style="margin-bottom:20px;">

                    <strong>
                        Account Created
                    </strong>

                    <p
                        style="
                            margin:6px 0 0 0;
                        "
                    >
                        <?= htmlspecialchars($success) ?>
                    </p>

                </div>

            <?php endif; ?>


            <!-- REGISTRATION FORM -->

            <form
                method="POST"
                action=""
            >


                <!-- FIRST NAME -->

                <div class="form-group">

                    <label for="FirstName">
                        First Name
                    </label>

                    <input
                        type="text"
                        id="FirstName"
                        name="FirstName"
                        value="<?= htmlspecialchars($firstName) ?>"
                        required
                        maxlength="100"
                        autocomplete="given-name"
                        class="auto-focus"
                    >

                </div>


                <!-- LAST NAME -->

                <div class="form-group">

                    <label for="LastName">
                        Last Name
                    </label>

                    <input
                        type="text"
                        id="LastName"
                        name="LastName"
                        value="<?= htmlspecialchars($lastName) ?>"
                        required
                        maxlength="100"
                        autocomplete="family-name"
                    >

                </div>


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
                    >

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label for="Password">
                        Password
                    </label>

                    <div class="login-password-wrapper">

                        <input
                            type="password"
                            id="Password"
                            name="Password"
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
                        >
                            Show
                        </button>

                    </div>

                    <small
                        style="
                            display:block;
                            margin-top:6px;
                            color:#6b7280;
                            font-size:12px;
                        "
                    >
                        At least 8 characters. Avoid common passwords
                        and don't include your name or email.
                    </small>

                </div>


                <!-- CONFIRM PASSWORD -->

                <div class="form-group">

                    <label for="ConfirmPassword">
                        Confirm Password
                    </label>

                    <div class="login-password-wrapper">

                        <input
                            type="password"
                            id="ConfirmPassword"
                            name="ConfirmPassword"
                            required
                            minlength="8"
                            maxlength="128"
                            autocomplete="new-password"
                        >

                        <button
                            type="button"
                            data-toggle-password="ConfirmPassword"
                            class="login-show-password"
                        >
                            Show
                        </button>

                    </div>

                </div>


                <!-- ROLE NOTE -->

                <div class="login-roles" style="margin-top:8px;">

                    <p class="login-roles-label">
                        Account Type
                    </p>

                    <p
                        style="
                            margin:0;
                            color:#64748b;
                            font-size:13px;
                            line-height:1.5;
                        "
                    >
                        New accounts are registered as standard User
                        accounts. Administrative and staff roles can
                        only be assigned by an authorized administrator.
                    </p>

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="button login-submit"
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
                class="button button-secondary login-register-btn"
            >
                Back to Sign In
            </a>


        </div>


        <!-- FOOTER -->

        <div class="login-footer">

            <p>
                &copy; <?= date("Y") ?> VISRS &nbsp;·&nbsp;
                <a href="/visrs/support.php">Support</a>
                &nbsp;·&nbsp;
                <a href="/visrs/privacy.php">Privacy</a>
            </p>

        </div>


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