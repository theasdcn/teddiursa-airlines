<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../utils/dbConnect.util.php';

try {
    $pdo = connectDB();

    $stmt = $pdo->query("SELECT id, passenger_name, gender, nationality, contact_number, passport_number FROM tickets ORDER BY generated_at DESC");
    $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $passengers
    ]);
} catch (\Throwable $e) {
    // Fallback: Return dummy passengers
    $dummyFile = __DIR__ . '/../staticDatas/dummies/tickets.staticData.php';
    $rawTickets = file_exists($dummyFile) ? require $dummyFile : [];
    
    $passengers = [];
    foreach ($rawTickets as $idx => $t) {
        $passengers[] = [
            'id' => 'TICK-' . ($idx + 1),
            'passenger_name' => $t['passenger_name'] ?? 'Passenger',
            'gender' => $t['gender'] ?? 'Not specified',
            'nationality' => $t['nationality'] ?? 'Global',
            'contact_number' => $t['contact_number'] ?? '09123456789',
            'passport_number' => $t['passport_number'] ?? 'PASS12345'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $passengers
    ]);
}
