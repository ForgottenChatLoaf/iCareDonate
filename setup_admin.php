<?php
/**
 * iCareDonate — Local Administrator Setup Utility
 * setup_admin.php
 *
 * Safe development CLI and local-only web script to create or reset an administrator account.
 * Passwords are encrypted using PHP password_hash(PASSWORD_DEFAULT).
 *
 * Usage via CLI:
 *   php setup_admin.php
 *   php setup_admin.php "Officer Name" admin@icaredonate.org "SecurePass@123"
 *
 * Usage via Browser (Localhost only):
 *   http://localhost/iCareDonate/setup_admin.php
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/database.php';

$isCli = (php_sapi_name() === 'cli');

// If accessed via web, enforce strict localhost-only access
if (!$isCli) {
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remoteAddr, ['127.0.0.1', '::1', 'localhost'], true)) {
        http_response_code(403);
        echo "<h1 style='color:red;'>403 Forbidden: Localhost access only.</h1>";
        exit;
    }
}

$pdo = getDB();
if ($pdo === null) {
    $msg = "Error: Database connection could not be established. Ensure MySQL is running in XAMPP.";
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
        exit(1);
    }
    die("<p style='color:red;'>$msg</p>");
}

// Default or CLI arguments
if ($isCli && isset($argv[1], $argv[2], $argv[3])) {
    $adminName  = trim($argv[1]);
    $adminEmail = strtolower(trim($argv[2]));
    $adminPass  = (string)$argv[3];
} elseif (!$isCli && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminName  = trim($_POST['name'] ?? 'System Administrator');
    $adminEmail = strtolower(trim($_POST['email'] ?? 'admin@icaredonate.org'));
    $adminPass  = (string)($_POST['password'] ?? 'Admin@1234');
} else {
    // Standard default development admin
    $adminName  = 'System Administrator';
    $adminEmail = 'admin@icaredonate.org';
    $adminPass  = 'Admin@1234';
}

if ($adminName === '' || $adminEmail === '' || strlen($adminPass) < 8) {
    $msg = "Error: Please supply valid parameters. Password must be >= 8 characters.";
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
        exit(1);
    }
}

try {
    $hash = password_hash($adminPass, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('
        INSERT INTO users (name, email, password_hash, role, created_at)
        VALUES (:name, :email, :hash, "admin", NOW())
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            password_hash = VALUES(password_hash),
            role = "admin"
    ');

    $stmt->execute([
        ':name'  => $adminName,
        ':email' => $adminEmail,
        ':hash'  => $hash,
    ]);

    if ($isCli) {
        echo "========================================================\n";
        echo "iCareDonate — Administrator Account Configured\n";
        echo "========================================================\n";
        echo "Name:     {$adminName}\n";
        echo "Email:    {$adminEmail}\n";
        echo "Role:     admin\n";
        echo "Password: (hashed successfully with PASSWORD_DEFAULT)\n";
        echo "Database: users table updated\n";
        echo "========================================================\n";
        exit(0);
    }

} catch (PDOException $e) {
    $err = "Database error: " . $e->getMessage();
    if ($isCli) {
        fwrite(STDERR, $err . PHP_EOL);
        exit(1);
    }
    die("<p style='color:red;'>$err</p>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>iCareDonate — Admin Setup Tool</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; padding: 40px 20px; }
    .card { max-width: 520px; margin: 0 auto; background: #fff; padding: 32px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
    h1 { font-size: 20px; margin-top: 0; color: #1F4FA3; }
    .form-group { margin-bottom: 16px; }
    label { display: block; font-weight: 600; font-size: 13.5px; margin-bottom: 6px; }
    input { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; }
    .btn { background: #1F4FA3; color: #fff; border: none; padding: 10px 18px; border-radius: 6px; font-weight: 600; cursor: pointer; }
    .success { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
  </style>
</head>
<body>
  <div class="card">
    <h1>iCareDonate Admin Setup</h1>
    <p style="font-size: 14px; color: #64748B;">Use this development utility to create or reset the local administrator credentials in MySQL.</p>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
      <div class="success">
        <strong>Administrator Configured Successfully!</strong><br>
        Email: <strong><?= htmlspecialchars($adminEmail) ?></strong><br>
        Password: <strong>(Updated & Hashed)</strong>
      </div>
      <p><a href="login.php" style="color: #1F4FA3; font-weight: 600;">&rarr; Go to Login Page</a></p>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Admin Display Name</label>
        <input type="text" name="name" value="System Administrator" required>
      </div>
      <div class="form-group">
        <label>Admin Email</label>
        <input type="email" name="email" value="admin@icaredonate.org" required>
      </div>
      <div class="form-group">
        <label>Admin Password (min 8 characters)</label>
        <input type="password" name="password" value="Admin@1234" required minlength="8">
      </div>
      <button type="submit" class="btn">Save Administrator</button>
      <a href="login.php" style="margin-left: 12px; color: #64748B; font-size: 14px;">Cancel</a>
    </form>
  </div>
</body>
</html>
