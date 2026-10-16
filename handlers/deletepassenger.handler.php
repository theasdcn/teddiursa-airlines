<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../utils/dbConnect.util.php';

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
