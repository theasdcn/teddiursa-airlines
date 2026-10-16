<?php
declare(strict_types=1);

// 1) Composer autoload
require 'vendor/autoload.php';

// 2) Composer bootstrap
require 'bootstrap.php';

// 3) envSetter
require_once UTILS_PATH . '/envSetter.util.php';

// ——— Connect to PostgreSQL ———
$dsn = "pgsql:host={$pgConfig['host']};port={$pgConfig['port']};dbname={$pgConfig['db']}";
$sslmode = getenv('PG_SSLMODE') ?: ($_ENV['PG_SSLMODE'] ?? '');
if (!empty($sslmode)) {
  $dsn .= ";sslmode=$sslmode";
}
$pdo = new PDO($dsn, $pgConfig['user'], $pgConfig['pass'], [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

echo "✅ Connected to PostgreSQL via PDO\n";

$schemas = [
  'planets.model.sql',
  'flights.model.sql',
  'images.model.sql',
  'users.model.sql',
  'booking.model.sql',
  'tickets.model.sql',
];


foreach ($schemas as $file) {
  $path = __DIR__ . '/../database/' . $file;
  echo "📄 Applying schema from {$path}…\n";
  $sql = file_get_contents($path);
  if ($sql === false) {
    throw new RuntimeException("❌ Could not read {$path}");
  }
  $pdo->exec($sql);
  echo "✅ Successfully applied {$file}\n";
}

// ——— Then Truncate the Tables ———
echo "🧹 Truncating tables…\n";
// Truncate in FK-safe order (child to parent)
foreach (['images','tickets', 'bookings', 'flights', 'planets', 'users'] as $table) {
  $pdo->exec("TRUNCATE TABLE {$table} RESTART IDENTITY CASCADE;");
}
echo "✅ Tables truncated successfully.\n";

echo "🎉 All tables have been reset and recreated successfully.\n";
