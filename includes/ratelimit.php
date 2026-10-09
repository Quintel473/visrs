<?php

/*
 * VISRS Login Rate Limiting
 *
 * Tracks failed login attempts and blocks
 * repeated failures by email and by IP.
 */


/* ---------- Configuration ---------- */

const RL_MAX_EMAIL_FAILS = 5;    // max failures per email in window
const RL_MAX_IP_FAILS    = 10;   // max failures per IP in window
const RL_WINDOW_MINUTES  = 15;   // rolling window
const RL_LOCK_MINUTES    = 15;   // how long a lockout lasts


/**
 * Record a failed login attempt.
 */
function rate_limit_record_failure(PDO $pdo, string $email, string $ip): void
{
    $stmt = $pdo->prepare("
        INSERT INTO login_attempts (Email, IPAddress)
        VALUES (?, ?)
    ");

    $stmt->execute([$email, $ip]);
}


/**
 * Clear failed attempts for a specific email
 * (called on successful login).
 */
function rate_limit_clear(PDO $pdo, string $email): void
{
    $stmt = $pdo->prepare("
        DELETE FROM login_attempts
        WHERE Email = ?
    ");

    $stmt->execute([$email]);
}


/**
 * Check whether the current email/IP is locked out.
 * Returns null if not locked, or an array with
 * 'message' and 'retry_after' (seconds) if locked.
 */
function rate_limit_check(PDO $pdo, string $email, string $ip): ?array
{
    /* Check email lockout */
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS fails
        FROM login_attempts
        WHERE Email = ?
          AND AttemptedAt > (NOW() - INTERVAL ? MINUTE)
    ");

    $stmt->execute([$email, RL_WINDOW_MINUTES]);

    $emailFails = (int) $stmt->fetchColumn();

    if ($emailFails >= RL_MAX_EMAIL_FAILS) {

        return [
            "message" =>
                "Too many failed attempts for this account. "
                . "Please try again later.",
            "retry_after" => RL_LOCK_MINUTES * 60
        ];
    }

    /* Check IP lockout */
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS fails
        FROM login_attempts
        WHERE IPAddress = ?
          AND AttemptedAt > (NOW() - INTERVAL ? MINUTE)
    ");

    $stmt->execute([$ip, RL_WINDOW_MINUTES]);

    $ipFails = (int) $stmt->fetchColumn();

    if ($ipFails >= RL_MAX_IP_FAILS) {

        return [
            "message" =>
                "Too many failed login attempts from this "
                . "network. Please try again later.",
            "retry_after" => RL_LOCK_MINUTES * 60 * 2
        ];
    }

    return null;
}


/**
 * Get the client IP.
 * Respects proxy headers only if you're behind a
 * trusted reverse proxy. Otherwise uses REMOTE_ADDR.
 */
function rate_limit_client_ip(): string
{
    /*
     * IMPORTANT: only trust X-Forwarded-For if you know
     * your app is behind a trusted reverse proxy (nginx,
     * Cloudflare, load balancer). Otherwise an attacker
     * can spoof the header and bypass the limiter.
     */

    return $_SERVER["REMOTE_ADDR"] ?? "0.0.0.0";
}


/**
 * Housekeeping — delete attempts older than the
 * window so the table doesn't grow unbounded.
 */
function rate_limit_cleanup(PDO $pdo): void
{
    $pdo->exec("
        DELETE FROM login_attempts
        WHERE AttemptedAt < (NOW() - INTERVAL 1 DAY)
    ");
}