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
    $flightId = $_GET['flight_id'] ?? null;
    if (!$flightId) {
        throw new Exception('Flight ID is required');
    }

    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT f.price, f.departure_time, f.return_time, 
               f.departure_planet_id, f.arrival_planet_id, 
               p.name AS destination
        FROM flights f
        JOIN planets p ON f.arrival_planet_id = p.id
        WHERE f.id = :flight_id
    ");
    $stmt->execute([':flight_id' => $flightId]);
    $flight = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$flight) {
        throw new Exception('Flight not found');
    }

    echo json_encode([
        'success' => true,
        'price' => $flight['price'],
        'departure_time' => $flight['departure_time'],
        'destination' => $flight['destination']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
