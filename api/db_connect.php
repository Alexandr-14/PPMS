<?php
// Database connection
//
// SECURITY NOTE:
// - Do NOT hardcode production credentials in git.
// - For production, create a local file `php/db_connect.local.php` (ignored by git)
//   that defines $host, $user, $pass, $db.
// - Or set environment variables: DB_HOST, DB_USER, DB_PASS, DB_NAME.

// Lightweight .env loader for shared hosting (e.g., cPanel)
// Loads variables from the project root `.env` if present.
// - Does NOT override existing environment variables.
function ppms_loadDotEnvIfPresent(string $envPath): void {
    if (!is_file($envPath) || !is_readable($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $equalsPos = strpos($line, '=');
        if ($equalsPos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $equalsPos));
        $value = trim(substr($line, $equalsPos + 1));

        if ($key === '') {
            continue;
        }

        // Strip optional surrounding quotes
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        // Do not override real environment variables
        if (getenv($key) !== false) {
            continue;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

ppms_loadDotEnvIfPresent(dirname(__DIR__) . '/.env');

$localConfig = __DIR__ . '/db_connect.local.php';
if (file_exists($localConfig)) {
    require $localConfig;
} else {
    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $db = getenv('DB_NAME') ?: 'ppms_database';
}

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
