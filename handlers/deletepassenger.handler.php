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

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID is required.']);
    exit;
}

try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = :id");
    $stmt->execute(['id' => $data['id']]);

    echo json_encode(['success' => true, 'message' => 'Passenger deleted successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error deleting passenger.', 'error' => $e->getMessage()]);
}
