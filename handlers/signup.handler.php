<?php
/* ============================================================
   DATABASE CONNECTION
   ============================================================ */
require_once __DIR__ . '/../utils/dbConnect.util.php';

/* ============================================================
   SIGN‑UP HANDLER
   ============================================================ */
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

/* ---------- Required fields ---------- */
foreach (['fullname','username','contact','password'] as $f) {
    if (empty($data[$f])) {
        echo json_encode([
            'success' => false,
            'message' => ucfirst($f) . ' is required'
        ]);
        exit;
    }
}

// Normalize full name
$fullnameRaw     = trim($data['fullname']);
$fullnameCleaned = preg_replace('/\s+/', '', strtolower($fullnameRaw)); // "Yuki Emit" -> "yukiemit"
$fullname        = ucwords(strtolower(preg_replace('/\s+/', ' ', $fullnameRaw))); // "yuki     emit" -> "Yuki Emit"

$username = trim($data['username']);
$contact  = trim($data['contact']);
$password = $data['password'];

/* ---------- Extra server‑side validation ---------- */
if (!preg_match('/^\d{11}$/', $contact)) {
    echo json_encode(['success' => false,
                      'message' => 'Contact number must be exactly 11 digits.']);
    exit;
}
if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
    echo json_encode(['success' => false,
                      'message' => 'Weak password. Use at least 1 uppercase letter, 1 number, 1 special character, and min 8 characters.']);
    exit;
}

/* ---------- Determine role ---------- */
$role = (strpos($username, '#') !== false) ? 'admin' : 'client';

try {
    $pdo = connectDB();

    // Check if normalized full name already exists (ignore spaces & case)
    $checkStmt = $pdo->prepare('SELECT 1 FROM users WHERE REPLACE(LOWER(fullname), \' \', \'\') = :cleaned');
    $checkStmt->execute(['cleaned' => $fullnameCleaned]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Name already exists.']);
        exit;
    }

    // Check for other duplicate fields (username, contact)
    $checkDupStmt = $pdo->prepare('
        SELECT 
            CASE 
                WHEN LOWER(username) = LOWER(:username) THEN \'username\'
                WHEN contact_num = :contact THEN \'contact\'
                ELSE NULL 
            END AS field
        FROM users 
        WHERE LOWER(username) = LOWER(:username) OR contact_num = :contact
        LIMIT 1
    ');
    $checkDupStmt->execute(['username' => $username, 'contact' => $contact]);
    $dup = $checkDupStmt->fetchColumn();
    if ($dup) {
        $field = $dup === 'username' ? 'Username' : 'Contact number';
        echo json_encode(['success' => false, 'message' => "$field already exists."]);
        exit;
    }

    // Insert new user
    $stmt = $pdo->prepare(
        'INSERT INTO users (fullname, password, contact_num, username, role)
         VALUES (:fullname, :password, :contact, :username, :role)'
    );
    $stmt->execute([
        'fullname'  => $fullname,
        'password'  => password_hash($password, PASSWORD_DEFAULT),
        'contact'   => $contact,
        'username'  => $username,
        'role'      => $role
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Account created! Please log in.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false,
                      'message' => 'DB Error: ' . $e->getMessage()]);
}
