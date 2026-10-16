<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/utils/dbConnect.util.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teddiursa Airlines - Database Setup</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 40px 20px; }
        .container { max-width: 650px; margin: 0 auto; background: #1e293b; border-radius: 12px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1 { color: #38bdf8; margin-top: 0; }
        .log { background: #090d16; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 13px; line-height: 1.6; margin: 20px 0; max-height: 350px; overflow-y: auto; }
        .success { color: #4ade80; }
        .error { color: #f87171; }
        .btn { display: inline-block; background: #38bdf8; color: #0f172a; font-weight: bold; text-decoration: none; padding: 12px 20px; border-radius: 8px; margin-top: 15px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🚀 Teddiursa Airlines DB Setup</h1>
    <p>Running schema migration and database initial setup...</p>
    <div class="log">
<?php
try {
    $pdo = connectDB();
    echo "<span class='success'>✅ Connected to PostgreSQL successfully!</span><br>";

    // Run table schemas
    $schemas = [
        'planets.model.sql',
        'flights.model.sql',
        'images.model.sql',
        'users.model.sql',
        'booking.model.sql',
        'tickets.model.sql',
    ];

    foreach ($schemas as $file) {
        $path = __DIR__ . '/database/' . $file;
        if (file_exists($path)) {
            $sql = file_get_contents($path);
            $pdo->exec($sql);
            echo "<span class='success'>✅ Applied schema: {$file}</span><br>";
        }
    }

    // Check if planets already seeded
    $stmt = $pdo->query("SELECT COUNT(*) FROM planets");
    $count = (int)$stmt->fetchColumn();

    if ($count === 0) {
        echo "🌱 Seeding initial planets and data...<br>";
        require_once __DIR__ . '/utils/envSetter.util.php';
        // Run seeder if available
        $seederScript = __DIR__ . '/utils/dbSeederPostgresql.util.php';
        if (file_exists($seederScript)) {
            include $seederScript;
            echo "<span class='success'>✅ Default data, flights, and admin users seeded!</span><br>";
        }
    } else {
        echo "<span class='success'>ℹ️ Database already contains data ({$count} planets found).</span><br>";
    }

    echo "<br><strong class='success'>🎉 Setup completed successfully!</strong><br>";
    echo "<br>Default Logins:<br>";
    echo "• <strong>Admin</strong>: username: <code>johndoe</code> | password: <code>Admin@123</code><br>";
    echo "• <strong>Client</strong>: username: <code>alice</code> | password: <code>Alice@1234</code><br>";
} catch (\Throwable $e) {
    echo "<span class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
    echo "<br>Please verify your database connection credentials on Render.";
}
?>
    </div>
    <a href="/" class="btn">Go to Login Page</a>
</div>
</body>
</html>
