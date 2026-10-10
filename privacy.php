<?php

/*
 * VISRS — Privacy Policy
 *
 * Public privacy policy page. Explains what data
 * VISRS collects, how it is used, and how users
 * can exercise their rights.
 */

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}


/*
 * Global <head>
 */

$basePath  = "/visrs/";

$pageTitle = "Privacy Policy - VISRS";

require_once "includes/head.php";

?>


<body class="legal-body">


<div class="legal-page">


    <?php require_once "includes/legal-nav.php"; ?>


    <main class="legal-content">


        <!-- =============================================
             PAGE HEADER
             ============================================= -->

        <section class="legal-hero">

            <span class="legal-hero-badge">
                Privacy
            </span>

            <h1 class="legal-hero-title">
                Privacy Policy
            </h1>

            <p class="legal-hero-subtitle">
                How VISRS collects, uses, and protects
                the information you share with us.
            </p>

            <p class="legal-hero-meta">
                Last updated:
                <strong><?= date("F j, Y") ?></strong>
            </p>

        </section>


        <!-- =============================================
             POLICY BODY
             ============================================= -->

        <section class="legal-section">

            <h2 class="legal-section-title">
                1. Information we collect
            </h2>

            <p class="legal-section-text">
                VISRS collects information that you
                provide directly, information generated
                by your use of the system, and limited
                technical information required for
                security and reliability.
            </p>

            <ul class="legal-list">
                <li>
                    <strong>Account data</strong> &mdash;
                    your name, email address, hashed
                    password, and role (Admin, Police, or
                    Seller).
                </li>
                <li>
                    <strong>Profile data</strong> &mdash;
                    optional profile picture and any
                    contact details you choose to add.
                </li>
                <li>
                    <strong>Activity data</strong> &mdash;
                    sign-in timestamps, IP address, and
                    actions taken on vehicle records. This
                    is used for security auditing and
                    accountability.
                </li>
                <li>
                    <strong>Vehicle records</strong> &mdash;
                    registration numbers, ownership
                    history, and related data entered by
                    authorized users.
                </li>
            </ul>

        </section>


        <section class="legal-section">

            <h2 class="legal-section-title">
                2. How we use your information
            </h2>

            <ul class="legal-list">
                <li>
                    To authenticate you and maintain a
                    secure session.
                </li>
                <li>
                    To operate core features such as
                    vehicle search, reporting, and
                    ownership tracking.
                </li>
                <li>
                    To detect and prevent fraudulent or
                    unauthorized activity.
                </li>
                <li>
                    To send essential notifications such as
                    verification codes and security alerts.
                </li>
                <li>
                    To comply with legal obligations where
                    applicable.
                </li>
            </ul>

        </section>


        <section class="legal-section">

            <h2 class="legal-section-title">
                3. Data sharing
            </h2>

            <p class="legal-section-text">
                We do not sell your personal information.
                Data may be shared only with:
            </p>

            <ul class="legal-list">
                <li>
                    Authorized law-enforcement or
                    government bodies when required by law.
                </li>
                <li>
                    Service providers who process data on
                    our behalf strictly under contract
                    (e.g., email delivery for verification
                    codes).
                </li>
            </ul>

        </section>


        <section class="legal-section">

            <h2 class="legal-section-title">
                4. Security
            </h2>

            <p class="legal-section-text">
                We apply industry-standard safeguards:
                hashed passwords, CSRF protection,
                rate-limited authentication, encrypted
                transport, and access controls based on
                role. No system is perfectly secure, so
                we encourage strong unique passwords and
                prompt reporting of suspicious activity.
            </p>

        </section>


        <section class="legal-section">

            <h2 class="legal-section-title">
                5. Retention
            </h2>

            <p class="legal-section-text">
                Account data is kept while your account
                is active. Audit logs and vehicle record
                history are retained for the minimum
                period required for legal and operational
                purposes, after which they are archived or
                securely deleted.
            </p>

        </section>


        <section class="legal-section">

            <h2 class="legal-section-title">
                6. Your rights
            </h2>

            <ul class="legal-list">
                <li>
                    Request a copy of the personal data we
                    hold about you.
                </li>
                <li>
                    Request correction of inaccurate data.
                </li>
                <li>
                    Request deletion of your account, where
                    no legal obligation requires retention.
                </li>
                <li>
                    Withdraw consent for optional
                    communications at any time.
                </li>
            </ul>

            <p class="legal-section-text">
                To exercise any of these rights, contact
                us at
                <a href="mailto:privacy@visrs.local">
                    privacy@visrs.local
                </a>.
            </p>

        </section>


        <section class="legal-section">

            <h2 class="legal-section-title">
                7. Cookies &amp; sessions
            </h2>

            <p class="legal-section-text">
                VISRS uses session cookies strictly for
                authentication. A "remember me" token may
                be stored if you opt in. No third-party
                tracking or advertising cookies are used.
            </p>

        </section>


        <section class="legal-section">

            <h2 class="legal-section-title">
                8. Changes to this policy
            </h2>

            <p class="legal-section-text">
                We may update this policy to reflect
                changes in our practices or the law. The
                revision date at the top of this page will
                always reflect the latest version.
            </p>

        </section>


        <section class="legal-section legal-callout">

            <h2 class="legal-section-title">
                Questions about privacy?
            </h2>

            <p class="legal-section-text">
                Reach out to our team and we'll get back
                to you as soon as possible.
            </p>

            <a
                href="mailto:privacy@visrs.local"
                class="legal-cta"
            >
                Contact privacy team &nbsp;→
            </a>

        </section>


    </main>


    <!-- FOOTER -->

    <footer class="legal-footer">

        <p>
            &copy; <?= date("Y") ?> VISRS &nbsp;·&nbsp;
            <a href="/visrs/support.php">Support</a>
            &nbsp;·&nbsp;
            <a href="/visrs/privacy.php">Privacy</a>
        </p>

    </footer>


</div>


<!-- VISRS GLOBAL JAVASCRIPT -->

<script
    src="/visrs/js/app.js"
    defer
></script>


</body>

</html>