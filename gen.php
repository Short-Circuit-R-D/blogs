<?php
/**
 * Generate a secure, unique Bearer token.
 *
 * @param int $length Length of the random bytes (not the final string length).
 * @return string Bearer token string.
 * @throws Exception If secure random generation fails.
 */
function generateBearerToken(int $length = 32): string {
    if ($length <= 0) {
        throw new InvalidArgumentException("Length must be a positive integer.");
    }

    // Generate cryptographically secure random bytes
    $randomBytes = random_bytes($length);

    // Convert to Base64URL (RFC 4648) — safe for URLs and headers
    $base64Url = rtrim(strtr(base64_encode($randomBytes), '+/', '-_'), '=');

    return 'Bearer ' . $base64Url;
}

// Example usage
try {
    $token = generateBearerToken(32); // 32 bytes = 43-char Base64URL string
    echo "Generated Token: " . $token . PHP_EOL;
} catch (Exception $e) {
    echo "Error generating token: " . $e->getMessage() . PHP_EOL;
}
