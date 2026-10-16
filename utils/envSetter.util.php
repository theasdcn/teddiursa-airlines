<?php
require_once BASE_PATH . '/vendor/autoload.php';

$envPath = BASE_PATH . '/.env';
if (file_exists($envPath)) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
        $dotenv->safeLoad();
    } catch (\Throwable $e) {
        // Safe ignore if dotenv already loaded or parse error
    }
}

// If DATABASE_URL is provided (Render / Neon / Supabase), parse it
$dbUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? '');
if (!empty($dbUrl)) {
    $parts = parse_url($dbUrl);
    if (!empty($parts['host'])) $_ENV['PG_HOST'] = $parts['host'];
    if (!empty($parts['port'])) $_ENV['PG_PORT'] = (string)$parts['port'];
    if (!empty($parts['path'])) $_ENV['PG_DB'] = ltrim($parts['path'], '/');
    if (!empty($parts['user'])) $_ENV['PG_USER'] = $parts['user'];
    if (!empty($parts['pass'])) $_ENV['PG_PASS'] = $parts['pass'];
}

// Fallback lookup from getenv() into $_ENV if not populated
$keys = ['PG_HOST', 'PG_PORT', 'PG_DB', 'PG_USER', 'PG_PASS', 'PG_SSLMODE', 'MONGO_URI', 'MONGO_DB'];
foreach ($keys as $k) {
    if (!isset($_ENV[$k]) || $_ENV[$k] === '') {
        $val = getenv($k);
        if ($val !== false && $val !== '') {
            $_ENV[$k] = $val;
        }
    }
}

if (php_sapi_name() === 'cli' && empty($_ENV['PG_HOST'])) {
    $_ENV['PG_HOST'] = 'localhost';
}

$typeConfig = [
    'pg_host'    => $_ENV['PG_HOST'] ?? 'localhost',
    'pg_port'    => $_ENV['PG_PORT'] ?? '5432',
    'pg_db'      => $_ENV['PG_DB'] ?? 'finaldatabase',
    'pg_user'    => $_ENV['PG_USER'] ?? 'user',
    'pg_pass'    => $_ENV['PG_PASS'] ?? 'password',

    'mongo_uri'  => $_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017',
    'mongo_db'   => $_ENV['MONGO_DB'] ?? 'finaldatabase',
];

$pgConfig = [
    'host' => $typeConfig['pg_host'],
    'port' => $typeConfig['pg_port'],
    'db'   => $typeConfig['pg_db'],
    'user' => $typeConfig['pg_user'],
    'pass' => $typeConfig['pg_pass'],
];