<?php
declare(strict_types=1);

/**
 * php/auth.php
 * Session & Access Control Helpers
 * Simplifies auth verification for beginner developers
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
