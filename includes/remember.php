<?php

/*
 * VISRS Remember-Me Helpers
 *
 * Handles issuing, verifying, and revoking persistent
 * login tokens used by the "Remember me" checkbox.
 *
 * Security model:
 *   - The raw token is only ever sent to the browser.
 *   - The database stores a SHA-256 hash of the token.
 *   - Tokens are single-use: verified tokens are rotated.
 *   - Tokens expire after REMEMBER_DAYS.
 */


/* ---------- Configuration ---------- */

const REMEMBER_DAYS   = 30;
const REMEMBER_COOKIE = "VISRS_REMEMBER";


/* =========================================================
   ISSUE — create a new token for a user
   ========================================================= */

/**
 * Issue a new remember-me token for a user.
 * Sets the cookie and stores the hashed token in the DB.
 */
function remember_issue(PDO $pdo, int $userId): void
{
    /* 256 bits of entropy, rendered as 64 hex chars */
    $raw  = bin2hex(random_bytes(32));
    $hash = hash("sha256", $raw);

    $expires = (new DateTimeImmutable())
        ->add(new DateInterval("P" . REMEMBER_DAYS . "D"));

    $stmt = $pdo->prepare("
        INSERT INTO remember_tokens
            (UserID, TokenHash, ExpiresAt)
        VALUES
            (?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $hash,
        $expires->format("Y-m-d H:i:s")
    ]);

    remember_set_cookie($raw, $expires->getTimestamp());
}


/* =========================================================
   ATTEMPT — verify the visitor via remember-me cookie
   ========================================================= */

/**
 * Attempt to authenticate the visitor via a remember-me cookie.
 * Returns the user row on success, or null.
 */
function remember_attempt(PDO $pdo): ?array
{
    $raw = $_COOKIE[REMEMBER_COOKIE] ?? "";

    /* Basic shape check before hitting the DB */
    if (
        $raw === "" ||
        !ctype_xdigit($raw) ||
        strlen($raw) !== 64
    ) {
        return null;
    }

    $hash = hash("sha256", $raw);

    $stmt = $pdo->prepare("
        SELECT
            t.TokenID,
            t.UserID,
            u.FirstName,
            u.LastName,
            u.Email,
            u.Role,
            u.ProfileImage
        FROM remember_tokens t
        INNER JOIN users u ON u.UserID = t.UserID
        WHERE t.TokenHash = ?
          AND t.ExpiresAt > NOW()
        LIMIT 1
    ");

    $stmt->execute([$hash]);

    $row = $stmt->fetch();

    if (!$row) {

        /* Stale cookie — clear it so we don't re-hit the DB */
        remember_clear_cookie();

        return null;
    }

    /* Rotate: delete the used token and issue a fresh one */
    $del = $pdo->prepare("
        DELETE FROM remember_tokens
        WHERE TokenID = ?
    ");

    $del->execute([$row["TokenID"]]);

    remember_issue($pdo, (int) $row["UserID"]);

    return $row;
}


/* =========================================================
   REVOKE — clear remember-me state for current visitor
   ========================================================= */

/**
 * Clear remember-me state for the current visitor.
 * Call this on logout.
 */
function remember_revoke(PDO $pdo): void
{
    $raw = $_COOKIE[REMEMBER_COOKIE] ?? "";

    if (
        $raw !== "" &&
        ctype_xdigit($raw) &&
        strlen($raw) === 64
    ) {
        $hash = hash("sha256", $raw);

        $stmt = $pdo->prepare("
            DELETE FROM remember_tokens
            WHERE TokenHash = ?
        ");

        $stmt->execute([$hash]);
    }

    remember_clear_cookie();
}


/* =========================================================
   REVOKE ALL — kill every token for a user
   ========================================================= */

/**
 * Delete every remember-me token belonging to a user.
 *
 * Useful for:
 *   - "Log out of all devices"
 *   - Forced logout after a password change
 *   - Cleanup when a user account is deleted
 */
function remember_revoke_all(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare("
        DELETE FROM remember_tokens
        WHERE UserID = ?
    ");

    $stmt->execute([$userId]);
}


/* =========================================================
   CLEANUP — purge expired tokens
   ========================================================= */

/**
 * Delete all expired tokens.
 * Cheap to run; call probabilistically or on a cron.
 */
function remember_cleanup(PDO $pdo): void
{
    $pdo->exec("
        DELETE FROM remember_tokens
        WHERE ExpiresAt < NOW()
    ");
}


/* =========================================================
   INTERNAL — cookie helpers
   ========================================================= */

function remember_set_cookie(string $value, int $expires): void
{
    setcookie(REMEMBER_COOKIE, $value, [
        "expires"  => $expires,
        "path"     => "/visrs/",
        "httponly" => true,
        "samesite" => "Lax",
        /* "secure" => true,  // enable once on HTTPS */
    ]);
}

function remember_clear_cookie(): void
{
    setcookie(REMEMBER_COOKIE, "", [
        "expires"  => time() - 3600,
        "path"     => "/visrs/",
        "httponly" => true,
        "samesite" => "Lax",
    ]);
}

?>