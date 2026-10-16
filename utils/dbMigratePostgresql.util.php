<?php
declare(strict_types=1);

// ✅ Load bootstrap to define BASE_PATH, UTILS_PATH, etc.
require_once 'bootstrap.php';

// ✅ Load dependencies and env
require_once BASE_PATH . '/vendor/autoload.php';
require_once UTILS_PATH . '/envSetter.util.php';

// ✅ Connect to PostgreSQL
$dsn = "pgsql:host={$pgConfig['host']};port={$pgConfig['port']};dbname={$pgConfig['db']}";
$sslmode = getenv('PG_SSLMODE') ?: ($_ENV['PG_SSLMODE'] ?? '');
if (!empty($sslmode)) {
  $dsn .= ";sslmode=$sslmode";
}
$pdo = new PDO($dsn, $pgConfig['user'], $pgConfig['pass'], [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

echo "✅ Connected to PostgreSQL via PDO\n";

// ✅ Drop old tables
echo "🧨 Dropping old tables…\n";
foreach (['booking', 'images', 'flights', 'planets', 'tickets', 'users'] as $table) {
  $pdo->exec("DROP TABLE IF EXISTS {$table} CASCADE;");
  echo "❌ Dropped table: {$table}\n";
}

// ✅ Apply updated schemas
$schemas = [
  'planets.model.sql',
  'flights.model.sql',
  'images.model.sql',
  'users.model.sql',
  'booking.model.sql',
  'tickets.model.sql',
];

foreach ($schemas as $file) {
  $path = BASE_PATH . '/database/' . $file;
  echo "📄 Applying schema from {$path}…\n";

  $sql = file_get_contents($path);
  if ($sql === false) {
    throw new RuntimeException("❌ Could not read {$path}");
  }

  try {
    $pdo->exec($sql);
    echo "✅ Creation Success from {$path}\n";
  } catch (PDOException $e) {
    echo "❌ ERROR while processing {$file}:\n" . $e->getMessage() . "\n";
    exit(1); // Stop the script after the first error
  }
}


echo "🎉 Migration complete!\n";
