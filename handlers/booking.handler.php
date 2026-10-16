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
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
}
