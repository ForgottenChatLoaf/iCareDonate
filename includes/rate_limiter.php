<?php
/**
 * iCareDonate — Lightweight Sliding-Window Rate Limiter
 * includes/rate_limiter.php
 *
 * Campus-friendly rate limiting combining IP address and authenticated user ID.
 * Prevents automated spam and rapid-fire prank submissions without blocking
 * normal users on shared networks.
 */

declare(strict_types=1);

/**
 * Checks and records an attempt against a rate-limit bucket.
 * Sends HTTP 429 JSON response and exits if the limit is exceeded.
 */
function icd_enforce_rate_limit(string $actionKey, int $maxAttempts = 10, int $windowSeconds = 900, ?int $userId = null): void {
    // Allow isolated CLI unit tests to bypass unless explicitly testing rate limits
    if (PHP_SAPI === 'cli' && getenv('ICD_TEST_RATE_LIMIT') !== '1') {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $identity = $userId !== null ? "u{$userId}_{$ip}" : "ip_{$ip}";
    $bucketKey = hash('sha256', $actionKey . '|' . $identity);

    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'icd_ratelimit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }

    $file = $dir . DIRECTORY_SEPARATOR . $bucketKey . '.json';
    $now = time();
    $cutoff = $now - $windowSeconds;
    $attempts = [];

    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $decoded = $raw ? json_decode($raw, true) : null;
        if (is_array($decoded)) {
            $attempts = array_values(array_filter($decoded, static fn($ts): bool => is_int($ts) && $ts > $cutoff));
        }
    }

    if (count($attempts) >= $maxAttempts) {
        $oldest = (int)min($attempts);
        $retryAfter = max(1, ($oldest + $windowSeconds) - $now);
        header('Retry-After: ' . $retryAfter);
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'code'    => 'RATE_LIMITED',
            'message' => 'Too many submissions in a short period. Please wait a few minutes before trying again.',
        ]);
        exit;
    }

    $attempts[] = $now;
    @file_put_contents($file, json_encode($attempts), LOCK_EX);
}

/**
 * Checks honeypot field and spam/repeated-character patterns.
 */
function icd_is_honeypot_triggered(array $payload): bool {
    $hp = trim((string)($payload['website_url'] ?? ($payload['hp_field'] ?? '')));
    return $hp !== '';
}

function icd_has_excessive_repetition(string $text): bool {
    return (bool)preg_match('/(.)\1{9,}/u', $text);
}
