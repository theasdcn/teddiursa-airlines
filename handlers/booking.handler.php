<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../utils/dbConnect.util.php';

try {
    $pdo = connectDB();

    $to = $_GET['to'] ?? null;
    $date = $_GET['date'] ?? null;

    $query = "
        SELECT f.*, 
               ap.name AS arrival_planet_name, 
               dp.name AS departure_planet_name
        FROM flights f
        LEFT JOIN planets ap ON f.arrival_planet_id = ap.id
        LEFT JOIN planets dp ON f.departure_planet_id = dp.id
        WHERE 1=1
    ";
    $params = [];

    if ($to) {
        $query .= " AND LOWER(ap.name) = LOWER(:to)";
        $params[':to'] = $to;
    }

    if ($date) {
        $query .= " AND DATE(f.departure_time) = :date";
        $params[':date'] = $date;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'flights' => $flights]);
} catch (\Throwable $e) {
    // Fallback: Return dummy flight data
    $dummyFile = __DIR__ . '/../staticDatas/dummies/flights.staticData.php';
    $flights = file_exists($dummyFile) ? require $dummyFile : [];
    
    // Normalize format
    $formattedFlights = [];
    foreach ($flights as $idx => $f) {
        $formattedFlights[] = [
            'id' => 'FL-' . ($idx + 1),
            'flight_number' => $f['flight_number'] ?? ('FX-00' . ($idx + 1)),
            'departure_planet_name' => $f['departure_planet'] ?? 'Earth',
            'arrival_planet_name' => $f['arrival_planet'] ?? 'Moon',
            'departure_time' => $f['departure_time'] ?? '2025-08-01 09:00:00',
            'return_time' => $f['return_time'] ?? '2025-08-10 17:00:00',
            'price' => $f['price'] ?? 15000.00,
            'destination' => $f['destination'] ?? 'Moon'
        ];
    }
    
    echo json_encode(['success' => true, 'flights' => $formattedFlights]);
}
