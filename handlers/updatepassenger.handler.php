<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

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

    $stmt = $pdo->prepare("
        UPDATE tickets
        SET
            passenger_name = :passenger_name,
            gender = :gender,
            contact_number = :contact_number,
            nationality = :nationality,
            passport_number = :passport_number
        WHERE id = :id
    ");

    $success = $stmt->execute([
        ':id' => $data['id'],
        ':passenger_name' => trim($data['passenger_name']),
        ':gender' => $data['gender'],
        ':contact_number' => $data['contact_number'],
        ':nationality' => $data['nationality'],
        ':passport_number' => $data['passport_number'],
    ]);

    echo json_encode(['success' => $success]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
