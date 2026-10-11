<?php

/*
 * VISRS TOTP
 *
 * Pure-PHP implementation of TOTP (RFC 6238) and Base32
 * encoding (RFC 4648) for two-factor authentication.
 *
 * No external dependencies.
 *
 * Compatibility: Google Authenticator, Authy, 1Password,
 * Microsoft Authenticator, FreeOTP, and most other
 * standards-compliant TOTP apps.
 */


/* ---------- Configuration ---------- */

const TOTP_DIGITS   = 6;    // digits per code
const TOTP_PERIOD   = 30;   // seconds per code
const TOTP_WINDOW   = 1;    // accept codes from ±N periods (clock skew)


/* =========================================================
   BASE32 ENCODING / DECODING (RFC 4648)
   ========================================================= */

/**
 * Encode raw binary to Base32 (uppercase, no padding).
 */
function totp_base32_encode(string $binary): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output   = '';
    $buffer   = 0;
    $bitsLeft = 0;

    for ($i = 0, $len = strlen($binary); $i < $len; $i++) {

        $buffer = ($buffer << 8) | ord($binary[$i]);
        $bitsLeft += 8;

        while ($bitsLeft >= 5) {
            $bitsLeft -= 5;
            $output  .= $alphabet[($buffer >> $bitsLeft) & 0x1F];
        }
    }

    if ($bitsLeft > 0) {
        $output .= $alphabet[($buffer << (5 - $bitsLeft)) & 0x1F];
    }

    return $output;
}


/**
 * Decode Base32 string to raw binary.
 * Ignores padding, whitespace, and case.
 * Returns false on invalid characters.
 */
function totp_base32_decode(string $base32)
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    $base32 = strtoupper($base32);
    $base32 = str_replace('=', '', $base32);
    $base32 = preg_replace('/\s+/', '', $base32);

    $output   = '';
    $buffer   = 0;
    $bitsLeft = 0;

    for ($i = 0, $len = strlen($base32); $i < $len; $i++) {

        $val = strpos($alphabet, $base32[$i]);

        if ($val === false) {
            return false;
        }

        $buffer = ($buffer << 5) | $val;
        $bitsLeft += 5;

        if ($bitsLeft >= 8) {
            $bitsLeft -= 8;
            $output  .= chr(($buffer >> $bitsLeft) & 0xFF);
        }
    }

    return $output;
}


/* =========================================================
   SECRET GENERATION
   ========================================================= */

/**
 * Generate a new random TOTP secret in Base32.
 * 160 bits of entropy (20 bytes → 32 Base32 chars), the
 * length recommended by RFC 4226.
 */
function totp_generate_secret(): string
{
    return totp_base32_encode(random_bytes(20));
}


/* =========================================================
   CODE GENERATION
   ========================================================= */

/**
 * Generate the TOTP code for a given secret and counter.
 */
function totp_generate_code(string $secret, int $counter): string
{
    $key = totp_base32_decode($secret);

    if ($key === false) {
        return '';
    }

    /* Pack counter as 64-bit big-endian binary */
    $binaryCounter = pack('N*', 0) . pack('N*', $counter);

    /* HMAC-SHA1 */
    $hash = hash_hmac('sha1', $binaryCounter, $key, true);

    /* Dynamic truncation (RFC 4226 §5.4) */
    $offset = ord($hash[19]) & 0x0F;

    $part =
        ((ord($hash[$offset])     & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
         (ord($hash[$offset + 3]) & 0xFF);

    $code = $part % (10 ** TOTP_DIGITS);

    return str_pad((string) $code, TOTP_DIGITS, '0', STR_PAD_LEFT);
}


/* =========================================================
   CODE VERIFICATION
   ========================================================= */

/**
 * Verify a submitted code against the secret.
 * Accepts codes from ±TOTP_WINDOW periods to tolerate
 * small clock differences between server and app.
 */
function totp_verify(string $secret, string $code): bool
{
    /* Must be exactly N digits */
    if (!preg_match('/^\d{' . TOTP_DIGITS . '}$/', $code)) {
        return false;
    }

    $currentCounter = (int) floor(time() / TOTP_PERIOD);

    for ($offset = -TOTP_WINDOW; $offset <= TOTP_WINDOW; $offset++) {

        $expected = totp_generate_code(
            $secret,
            $currentCounter + $offset
        );

        /* Constant-time compare */
        if (hash_equals($expected, $code)) {
            return true;
        }
    }

    return false;
}


/* =========================================================
   OT PAUTH URI
   ========================================================= */

/**
 * Build the otpauth:// URI that authenticator apps read
 * from the QR code.
 *
 * Example:
 *   otpauth://totp/VISRS:user@example.com?secret=ABC...&issuer=VISRS
 */
function totp_build_uri(string $secret, string $accountName, string $issuer = 'VISRS'): string
{
    $label = $issuer . ':' . $accountName;

    $params = http_build_query([
        'secret'    => $secret,
        'issuer'    => $issuer,
        'algorithm' => 'SHA1',
        'digits'    => TOTP_DIGITS,
        'period'    => TOTP_PERIOD,
    ]);

    return 'otpauth://totp/'
        . rawurlencode($label)
        . '?'
        . $params;
}


/* =========================================================
   RECOVERY CODES
   ========================================================= */

/**
 * Generate a batch of recovery codes.
 * Returns the plaintext codes — the caller must hash and
 * store them, and show them to the user exactly once.
 */
function totp_generate_recovery_codes(int $count = 8): array
{
    $codes = [];

    for ($i = 0; $i < $count; $i++) {

        /* 10 random chars, formatted as XXXXX-XXXXX */
        $raw = strtoupper(bin2hex(random_bytes(5)));

        $codes[] = substr($raw, 0, 5) . '-' . substr($raw, 5, 5);
    }

    return $codes;
}


/**
 * Hash a recovery code for storage.
 */
function totp_hash_recovery_code(string $code): string
{
    /* Normalise: strip dashes, lowercase, re-add for consistency */
    $clean = strtolower(str_replace('-', '', $code));

    return hash('sha256', $clean);
}

?>