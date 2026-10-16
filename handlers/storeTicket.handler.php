<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../utils/dbConnect.util.php';

// Read and decode JSON
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

// Required fields
$requiredFields = [
    'reference', 'passenger_name', 'gender', 'nationality',
    'contact_number', 'passport_number', 'departure_location',
    'destination', 'departure_date', 'departure_time',
    'payment_method', 'price_paid', 'change_given'
];

// Check for empty fields
foreach ($requiredFields as $field) {
    if (empty($data[$field]) && $data[$field] !== 0 && $data[$field] !== "0") {
        echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
        exit;
    }
}

// Basic server-side validations
if (!preg_match('/^\+?\d{10,15}$/', $data['contact_number'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid contact number']);
    exit;
}

if (!in_array($data['gender'], ['male', 'female', 'other'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid gender']);
    exit;
}

if (!strtotime($data['departure_date']) || !strtotime($data['departure_time'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid departure date or time']);
    exit;
}

try {
    $pdo = connectDB();

    $stmt = $pdo->prepare("
        INSERT INTO public.tickets (
            reference_number, 
            passenger_name, gender, nationality, contact_number, passport_number, 
            departure_location, destination, departure_date, departure_time, 
            payment_method, amount_paid, change_given
        ) VALUES (
            :reference_number, 
            :passenger_name, :gender, :nationality, :contact_number, :passport_number, 
            :departure_location, :destination, :departure_date, :departure_time, 
            :payment_method, :amount_paid, :change_given
        )
    ");

    $stmt->execute([
        ':reference_number'   => $data['reference'],
        ':passenger_name'     => ucwords(trim($data['passenger_name'])),
        ':gender'             => $data['gender'],
        ':nationality'        => $data['nationality'],
        ':contact_number'     => $data['contact_number'],
        ':passport_number'    => $data['passport_number'],
        ':departure_location' => $data['departure_location'],
        ':destination'        => $data['destination'],
        ':departure_date'     => $data['departure_date'],
        ':departure_time'     => $data['departure_time'],
        ':payment_method'     => $data['payment_method'],
        ':amount_paid'        => floatval($data['price_paid']),
        ':change_given'       => floatval($data['change_given'])
    ]);

    echo json_encode([
        'success' => true,
        'message' => '🎟️ Ticket saved successfully!'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '❌ DB Error: ' . $e->getMessage()
    ]);
}
