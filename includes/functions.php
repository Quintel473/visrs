<?php

/*
 * VISRS Global Functions
 *
 * Reusable helper functions used throughout
 * the Vehicle Information Search & Retrieval System.
 */


/* =========================================================
   SECURITY / OUTPUT HELPERS
   ========================================================= */

/**
 * Safely escape output for HTML.
 */
function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/**
 * Sanitize a basic text value.
 */
function cleanInput($value)
{
    return trim(
        htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            "UTF-8"
        )
    );
}


/**
 * Redirect to another page and stop execution.
 */
function redirect($url)
{
    header("Location: " . $url);
    exit;
}


/* =========================================================
   FLASH MESSAGES
   ========================================================= */

/**
 * Store a temporary session message.
 */
function setFlashMessage($type, $message)
{
    $_SESSION["flash_message"] = [
        "type" => $type,
        "message" => $message
    ];
}


/**
 * Retrieve and remove the temporary session message.
 */
function getFlashMessage()
{
    if (!isset($_SESSION["flash_message"])) {
        return null;
    }

    $message = $_SESSION["flash_message"];

    unset($_SESSION["flash_message"]);

    return $message;
}


/**
 * Display the current flash message.
 */
function displayFlashMessage()
{
    $message = getFlashMessage();

    if (!$message) {
        return;
    }

    $type = e($message["type"]);
    $text = e($message["message"]);

    $class = "alert-info";

    if ($type === "success") {
        $class = "alert-success";
    } elseif ($type === "error") {
        $class = "alert-error";
    } elseif ($type === "warning") {
        $class = "alert-warning";
    }
    ?>

    <div class="alert <?= $class ?> auto-dismiss">
        <?= $text ?>
    </div>

    <?php
}


/* =========================================================
   DATE / TIME HELPERS
   ========================================================= */

/**
 * Format a database date.
 */
function formatDate($date)
{
    if (empty($date)) {
        return "—";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return e($date);
    }

    return date("M d, Y", $timestamp);
}


/**
 * Format a database timestamp.
 */
function formatDateTime($dateTime)
{
    if (empty($dateTime)) {
        return "—";
    }

    $timestamp = strtotime($dateTime);

    if ($timestamp === false) {
        return e($dateTime);
    }

    return date("M d, Y h:i A", $timestamp);
}


/* =========================================================
   VALIDATION HELPERS
   ========================================================= */

/**
 * Check whether an email address is valid.
 */
function isValidEmail($email)
{
    return filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    ) !== false;
}


/**
 * Check whether a value is an integer.
 */
function isValidInteger($value)
{
    return filter_var(
        $value,
        FILTER_VALIDATE_INT
    ) !== false;
}


/**
 * Check whether a date is valid.
 */
function isValidDate($date)
{
    if (empty($date)) {
        return false;
    }

    $dateObject = DateTime::createFromFormat(
        "Y-m-d",
        $date
    );

    return $dateObject !== false &&
        $dateObject->format("Y-m-d") === $date;
}


/**
 * Check whether a string is within a specified length.
 */
function isWithinLength(
    $value,
    $minimum = 0,
    $maximum = 255
) {
    $length = strlen((string) $value);

    return $length >= $minimum &&
        $length <= $maximum;
}


/* =========================================================
   ROLE HELPERS
   ========================================================= */

/**
 * Get the currently logged-in user's role.
 */
function currentUserRole()
{
    return $_SESSION["Role"] ?? null;
}


/**
 * Get the currently logged-in user's ID.
 */
function currentUserId()
{
    return $_SESSION["UserID"] ?? null;
}


/**
 * Check whether the current user has one of
 * the specified roles.
 */
function hasRole($roles)
{
    $currentRole = currentUserRole();

    if (!$currentRole) {
        return false;
    }

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    return in_array(
        $currentRole,
        $roles,
        true
    );
}


/**
 * Check whether the current user is an administrator.
 */
function isCurrentUserAdmin()
{
    return currentUserRole() === "Admin";
}


/* =========================================================
   AUDIT LOGGING
   ========================================================= */

/**
 * Create an audit log entry.
 *
 * Requires the database connection from database.php.
 */
function logAudit(
    $pdo,
    $action,
    $tableAffected = null,
    $recordId = null
) {
    $userId = $_SESSION["UserID"] ?? null;

    $ipAddress = $_SERVER["REMOTE_ADDR"] ?? null;

    $sql = "
        INSERT INTO audit_logs
        (
            UserID,
            Action,
            TableAffected,
            RecordID,
            IPAddress
        )
        VALUES
        (
            :user_id,
            :action,
            :table_affected,
            :record_id,
            :ip_address
        )
    ";

    $statement = $pdo->prepare($sql);

    $statement->execute([
        ":user_id" => $userId,
        ":action" => $action,
        ":table_affected" => $tableAffected,
        ":record_id" => $recordId,
        ":ip_address" => $ipAddress
    ]);
}


/* =========================================================
   DATABASE HELPERS
   ========================================================= */

/**
 * Determine whether a database record exists.
 */
function recordExists(
    $pdo,
    $table,
    $column,
    $value
) {
    $allowedTables = [
        "users",
        "owners",
        "vehicles",
        "ownership_history",
        "insurance",
        "accidents",
        "audit_logs"
    ];

    if (!in_array($table, $allowedTables, true)) {
        return false;
    }

    $allowedColumns = [
        "UserID",
        "OwnerID",
        "VehicleID",
        "OwnershipID",
        "InsuranceID",
        "AccidentID",
        "LogID",
        "Email",
        "PlateNumber",
        "VIN"
    ];

    if (!in_array($column, $allowedColumns, true)) {
        return false;
    }

    $sql = "
        SELECT COUNT(*)
        FROM `$table`
        WHERE `$column` = :value
    ";

    $statement = $pdo->prepare($sql);

    $statement->execute([
        ":value" => $value
    ]);

    return (int) $statement->fetchColumn() > 0;
}


/* =========================================================
   STATUS HELPERS
   ========================================================= */

/**
 * Return a CSS class for a common status value.
 */
function statusClass($status)
{
    switch ($status) {

        case "Active":
            return "status-active";

        case "Inactive":
            return "status-inactive";

        case "Stolen":
            return "status-stolen";

        case "Sold":
            return "status-sold";

        case "Expired":
            return "status-expired";

        case "Cancelled":
            return "status-cancelled";

        case "Open":
            return "status-open";

        case "Closed":
            return "status-closed";

        default:
            return "status-default";
    }
}


/**
 * Return a CSS class for accident damage levels.
 */
function damageLevelClass($damageLevel)
{
    switch ($damageLevel) {

        case "Minor":
            return "damage-minor";

        case "Moderate":
            return "damage-moderate";

        case "Major":
            return "damage-major";

        case "Severe":
            return "damage-severe";

        default:
            return "damage-default";
    }
}


/* =========================================================
   DISPLAY HELPERS
   ========================================================= */

/**
 * Display a safe value or an em dash when empty.
 */
function displayValue($value)
{
    if (
        $value === null ||
        $value === ""
    ) {
        return "—";
    }

    return e($value);
}


/**
 * Return a person's full name.
 */
function fullName($firstName, $lastName)
{
    return trim(
        $firstName . " " . $lastName
    );
}


/* =========================================================
   APPLICATION HELPERS
   ========================================================= */

/**
 * Return the current VISRS application URL.
 */
function appUrl($path = "")
{
    return "/visrs/" . ltrim(
        $path,
        "/"
    );
}


/**
 * Determine whether the current request is POST.
 */
function isPostRequest()
{
    return $_SERVER["REQUEST_METHOD"] === "POST";
}


/**
 * Determine whether the current request is GET.
 */
function isGetRequest()
{
    return $_SERVER["REQUEST_METHOD"] === "GET";
}


/* =========================================================
   PASSWORD RESET HELPERS
   ========================================================= */

/**
 * Generate a cryptographically secure reset token.
 * Returns [ plainToken, hashedToken ].
 */
function generateResetToken()
{
    $plain = bin2hex(random_bytes(32));   // 64 hex chars
    $hash  = hash('sha256', $plain);
    return [$plain, $hash];
}


/**
 * Deliver a reset link.
 *
 * Attempts to send via PHP mail(). On failure (common on
 * localhost / XAMPP without SMTP), appends the link to
 * /visrs/logs/reset-links.log so the developer can still
 * complete the flow.
 *
 * Returns true if the email was sent, false otherwise.
 */
function sendResetEmail($email, $firstName, $resetLink)
{
    $subject = "VISRS Password Reset Request";

    $body  = "Hello " . ($firstName ?: "there") . ",\n\n";
    $body .= "We received a request to reset your VISRS password.\n\n";
    $body .= "Click the link below to choose a new password:\n\n";
    $body .= $resetLink . "\n\n";
    $body .= "This link expires in 1 hour.\n\n";
    $body .= "If you did not request this, you can ignore this email.\n\n";
    $body .= "— VISRS\n";

    $headers  = "From: VISRS <no-reply@visrs.local>\r\n";
    $headers .= "Reply-To: no-reply@visrs.local\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    /* Try to send. @ suppresses warnings if mail() isn't configured. */
    $sent = false;
    if (function_exists('mail')) {
        $sent = @mail($email, $subject, $body, $headers);
    }

    /* Always log the link in dev so the flow can be completed. */
    writeResetLog($email, $resetLink, $sent);

    return $sent;
}


/**
 * Write the reset link to a local log file.
 * Useful when mail() isn't configured (e.g., XAMPP).
 */
function writeResetLog($email, $resetLink, $emailSent)
{
    $logDir = __DIR__ . "/../logs";

    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . "/reset-links.log";

    $line  = str_repeat("=", 70) . "\n";
    $line .= "TIME:      " . date("Y-m-d H:i:s") . "\n";
    $line .= "EMAIL:     " . $email . "\n";
    $line .= "DELIVERED: " . ($emailSent ? "yes" : "no (log only)") . "\n";
    $line .= "LINK:      " . $resetLink . "\n";
    $line .= str_repeat("=", 70) . "\n\n";

    @file_put_contents($logFile, $line, FILE_APPEND);
}


/**
 * Clean up expired reset tokens.
 * Call this periodically or on each reset request.
 */
function cleanupExpiredResets(PDO $pdo)
{
    $stmt = $pdo->prepare("
        DELETE FROM password_resets
        WHERE ExpiresAt < NOW()
           OR UsedAt IS NOT NULL
    ");
    $stmt->execute();
}

/* =========================================================
   PAGINATION HELPERS
   ========================================================= */

/**
 * Read and validate the current page number from the URL.
 *
 * @param int $maxPage  Upper bound for the page number (optional)
 * @return int          Current page (always >= 1)
 */
function currentPage($maxPage = 0)
{
    $page = isset($_GET["page"]) ? (int) $_GET["page"] : 1;

    if ($page < 1) {
        $page = 1;
    }

    if ($maxPage > 0 && $page > $maxPage) {
        $page = $maxPage;
    }

    return $page;
}


/**
 * Calculate pagination metadata.
 *
 * @param int $totalRows  Total number of rows in the dataset
 * @param int $perPage    Rows to show per page
 * @return array          [currentPage, totalPages, offset, perPage, totalRows]
 */
function paginationMeta($totalRows, $perPage = 25)
{
    $totalRows = max(0, (int) $totalRows);
    $perPage   = max(1, (int) $perPage);

    $totalPages = (int) ceil($totalRows / $perPage);
    if ($totalPages < 1) {
        $totalPages = 1;
    }

    $currentPage = currentPage($totalPages);
    $offset      = ($currentPage - 1) * $perPage;

    return [
        "currentPage" => $currentPage,
        "totalPages"  => $totalPages,
        "offset"      => $offset,
        "perPage"     => $perPage,
        "totalRows"   => $totalRows,
    ];
}


/**
 * Render pagination controls.
 *
 * Preserves any existing query string parameters (search, filters, etc.)
 *
 * @param array $meta   From paginationMeta()
 * @param array $params Extra query params to preserve (defaults to $_GET)
 */
function renderPagination($meta, $params = null)
{
    if ($params === null) {
        $params = $_GET;
    }

    $currentPage = $meta["currentPage"];
    $totalPages  = $meta["totalPages"];
    $totalRows   = $meta["totalRows"];
    $perPage     = $meta["perPage"];

    if ($totalPages <= 1) {
        return;
    }

    /* Build a base URL preserving existing query params */
    $baseParams = $params;
    unset($baseParams["page"]);

    $buildUrl = function ($page) use ($baseParams) {
        $q = $baseParams;
        $q["page"] = $page;
        return "?" . http_build_query($q);
    };

    /* Range of page links to show (current ± 2) */
    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);

    ?>

    <div class="pagination-wrapper">

        <div class="pagination-info">
            Showing
            <strong><?= (($currentPage - 1) * $perPage) + 1 ?></strong>
            –
            <strong><?= min($currentPage * $perPage, $totalRows) ?></strong>
            of
            <strong><?= $totalRows ?></strong>
        </div>


        <nav
            class="pagination"
            aria-label="Pagination"
        >

            <!-- PREVIOUS -->

            <?php if ($currentPage > 1): ?>

                <a
                    href="<?= htmlspecialchars($buildUrl($currentPage - 1)) ?>"
                    class="pagination-link"
                    rel="prev"
                >
                    ‹ Prev
                </a>

            <?php else: ?>

                <span class="pagination-link disabled">
                    ‹ Prev
                </span>

            <?php endif; ?>


            <!-- FIRST PAGE + ELLIPSIS -->

            <?php if ($start > 1): ?>

                <a
                    href="<?= htmlspecialchars($buildUrl(1)) ?>"
                    class="pagination-link"
                >
                    1
                </a>

                <?php if ($start > 2): ?>

                    <span class="pagination-ellipsis">…</span>

                <?php endif; ?>

            <?php endif; ?>


            <!-- PAGE NUMBERS -->

            <?php for ($i = $start; $i <= $end; $i++): ?>

                <?php if ($i === $currentPage): ?>

                    <span
                        class="pagination-link active"
                        aria-current="page"
                    >
                        <?= $i ?>
                    </span>

                <?php else: ?>

                    <a
                        href="<?= htmlspecialchars($buildUrl($i)) ?>"
                        class="pagination-link"
                    >
                        <?= $i ?>
                    </a>

                <?php endif; ?>

            <?php endfor; ?>


            <!-- LAST PAGE + ELLIPSIS -->

            <?php if ($end < $totalPages): ?>

                <?php if ($end < $totalPages - 1): ?>

                    <span class="pagination-ellipsis">…</span>

                <?php endif; ?>

                <a
                    href="<?= htmlspecialchars($buildUrl($totalPages)) ?>"
                    class="pagination-link"
                >
                    <?= $totalPages ?>
                </a>

            <?php endif; ?>


            <!-- NEXT -->

            <?php if ($currentPage < $totalPages): ?>

                <a
                    href="<?= htmlspecialchars($buildUrl($currentPage + 1)) ?>"
                    class="pagination-link"
                    rel="next"
                >
                    Next ›
                </a>

            <?php else: ?>

                <span class="pagination-link disabled">
                    Next ›
                </span>

            <?php endif; ?>


        </nav>

    </div>

    <?php
}