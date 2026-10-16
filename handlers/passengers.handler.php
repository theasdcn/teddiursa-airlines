<?php
header('Content-Type: application/json');

function connectDB(): PDO {
    $host     = getenv('PG_HOST') ?: ($_ENV['PG_HOST'] ?? 'host.docker.internal');
    $port     = getenv('PG_PORT') ?: ($_ENV['PG_PORT'] ?? '5112');
    $dbname   = getenv('PG_DB')   ?: ($_ENV['PG_DB'] ?? 'finaldatabase');
    $user     = getenv('PG_USER') ?: ($_ENV['PG_USER'] ?? 'user');
    $password = getenv('PG_PASS') ?: ($_ENV['PG_PASS'] ?? 'password');
    $sslmode  = getenv('PG_SSLMODE') ?: ($_ENV['PG_SSLMODE'] ?? '');

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    if (!empty($sslmode)) {
        $dsn .= ";sslmode=$sslmode";
    }

    return new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

try {
    $pdo = connectDB();

    $stmt = $pdo->query("SELECT id, passenger_name, gender, nationality, contact_number, passport_number FROM tickets ORDER BY generated_at DESC");
    $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $passengers
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load passengers.',
        'error' => $e->getMessage()
    ]);
}
