<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../utils/dbConnect.util.php';

// Read and validate JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['mode'], $data['destination'], $data['departure_datetime'], $data['return_datetime'], $data['price'])) {
    echo json_encode(['success' => false, 'message' => 'Incomplete data']);
    exit;
}
    
$mode = $data['mode'];
$destination = strtoupper(trim($data['destination']));
$departure_datetime = $data['departure_datetime'];
$return_datetime = $data['return_datetime'];
$price = $data['price'];

try {
    $pdo = connectDB();

    // Get arrival_planet_id from destination name
    $stmt = $pdo->prepare("SELECT id FROM planets WHERE UPPER(name) = :name");
    $stmt->execute([':name' => $destination]);
    $planet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$planet) {
        echo json_encode(['success' => false, 'message' => 'Planet not found']);
        exit;
    }

    $arrival_planet_id = $planet['id'];

    // Get departure_planet_id (assuming Earth as the fixed departure point)
    $stmt = $pdo->prepare("SELECT id FROM planets WHERE UPPER(name) = 'EARTH'");
    $stmt->execute();
    $earth = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$earth) {
        echo json_encode(['success' => false, 'message' => 'Departure planet Earth not found']);
        exit;
    }

    $departure_planet_id = $earth['id'];

    if ($mode === 'create') {
        // Check for duplicate flight
        $check = $pdo->prepare("SELECT * FROM flights WHERE arrival_planet_id = :arrival AND departure_time = :departure_time");
        $check->execute([':arrival' => $arrival_planet_id, ':departure_time' => $departure_datetime]);

        if ($check->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Flight already exists']);
            exit;
        }

        // Insert new flight
        $stmt = $pdo->prepare("
            INSERT INTO flights (
                destination,
                departure_planet_id,
                arrival_planet_id,
                departure_time,
                return_time,
                price,
                flight_number
            ) VALUES (
                :destination,
                :departure,
                :arrival,
                :departure_time,
                :return_time,
                :price,
                CONCAT('FL', FLOOR(RANDOM() * 10000)::int)
            )
        ");

        $stmt->execute([
            ':destination' => $destination,
            ':departure' => $departure_planet_id,
            ':arrival' => $arrival_planet_id,
            ':departure_time' => $departure_datetime,
            ':return_time' => $return_datetime,
            ':price' => $price
        ]);

        echo json_encode(['success' => true, 'message' => 'Flight created successfully']);
    }

    else if ($mode === 'delete') {
        $stmt = $pdo->prepare("
            DELETE FROM flights
            WHERE arrival_planet_id = :arrival
              AND departure_time = :departure_time
              AND return_time = :return_time
        ");
        $stmt->execute([
            ':arrival' => $arrival_planet_id,
            ':departure_time' => $departure_datetime,
            ':return_time' => $return_datetime
        ]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Flight deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No matching flight found']);
        }
    }

    else if ($mode === 'update') {
        $stmt = $pdo->prepare("
            UPDATE flights
            SET price = :price,
                updated_at = CURRENT_TIMESTAMP
            WHERE arrival_planet_id = :arrival
              AND departure_time = :departure_time
              AND return_time = :return_time
        ");
        $stmt->execute([
            ':price' => $price,
            ':arrival' => $arrival_planet_id,
            ':departure_time' => $departure_datetime,
            ':return_time' => $return_datetime
        ]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Flight updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No matching flight to update']);
        }
    }

    else {
        echo json_encode(['success' => false, 'message' => 'Invalid mode']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
