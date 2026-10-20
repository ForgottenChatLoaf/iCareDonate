<?php
/**
 * iCareDonate — Cross-Origin Resource Sharing (CORS) Handler
 * includes/cors.php
 *
 * Configures headers allowing cross-origin requests from frontend hosts
 * (such as Vercel) to the Render API backend.
 */

declare(strict_types=1);

if (!function_exists('handleCors')) {
    function handleCors(): void {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($origin !== '') {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
            header('Vary: Origin');
        } else {
            header('Access-Control-Allow-Origin: *');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');

        if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}

// Automatically apply CORS on include
handleCors();
