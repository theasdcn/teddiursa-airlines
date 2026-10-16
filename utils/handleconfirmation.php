<?php
/* ============================================================
   CONNECT TO POSTGRES  (inline helper ‑ no external include)
   ============================================================ */
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

    return new PDO(
        $dsn,
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

/* ============================================================
   SESSION & HEADERS
   ============================================================ */
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$userSession = $_SESSION['user']       ?? null;
$flightId    = $_SESSION['flight_id']   ?? null;
$travelDate  = $_SESSION['travel_date'] ?? date('Y-m-d');

if (!$userSession || !$flightId) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Session expired or missing flight data.']);
    exit;
}

/* ============================================================
   READ & VALIDATE JSON PAYLOAD
   ============================================================ */
$payload = json_decode(file_get_contents('php://input'), true) ?: [];

$required = [
    'fullName','dob','gender','nationality','email','phone',
    'emergencyName','emergencyPhone','passport','expiry','country','amountPaid'
];
foreach ($required as $f) {
    if (empty($payload[$f])) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>"$f is required."]);
        exit;
    }
}

/* ============================================================
   DB TRANSACTIONS
   ============================================================ */
try {
    $pdo = connectDB();
    $pdo->beginTransaction();

    // First get the flight price
    $flightStmt = $pdo->prepare("SELECT price FROM flights WHERE id = :fid");
    $flightStmt->execute([':fid' => $flightId]);
    $flight = $flightStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$flight) {
        throw new Exception('Flight not found.');
    }
    
    $totalPrice = $flight['price'];

    /* 1)  Update user profile (optional fields) */
    $update = $pdo->prepare("
        UPDATE users SET
            date_of_birth            = :dob,
            gender                   = :gender,
            nationality              = :nat,
            email                    = :email,
            contact_num              = :phone,
            emergency_contact        = :emergency,
            passport_number          = :passport,
            passport_expiry          = :expiry,
            passport_issuing_country = :country
        WHERE id = :uid
    ");
    $update->execute([
        ':dob'       => $payload['dob'],
        ':gender'    => $payload['gender'],
        ':nat'       => $payload['nationality'],
        ':email'     => $payload['email'],
        ':phone'     => $payload['phone'],
        ':emergency' => $payload['emergencyName'].' - '.$payload['emergencyPhone'],
        ':passport'  => $payload['passport'],
        ':expiry'    => $payload['expiry'],
        ':country'   => $payload['country'],
        ':uid'       => $userSession['id']
    ]);

    /* 2)  Insert booking */
    $seat      = 'A-' . rand(1, 99);
    $ticketRef = strtoupper(bin2hex(random_bytes(4)));          // e.g. 8‑char code

    $insertBk = $pdo->prepare("
        INSERT INTO bookings (user_id, flight_id, travel_date, seat_number, ticket_code)
        VALUES (:uid, :fid, :tdate, :seat, :tcode)
        RETURNING id
    ");
    $insertBk->execute([
        ':uid'   => $userSession['id'],
        ':fid'   => $flightId,
        ':tdate' => $travelDate,
        ':seat'  => $seat,
        ':tcode' => $ticketRef
    ]);
    $bookingId = $insertBk->fetchColumn();

    /* 3)  Insert ticket */
    $flight = $pdo->prepare("SELECT flight_number, launch_pad, gate FROM flights WHERE id = :fid");
    $flight->execute([':fid'=>$flightId]);
    $flight = $flight->fetch(PDO::FETCH_ASSOC);

    if (!$flight) throw new Exception('Flight not found.');

    $insertTk = $pdo->prepare("
        INSERT INTO tickets (booking_id, flight_id, flight_number, launch_pad, gate, qr_code)
        VALUES (:bid, :fid, :fnum, :lpad, :gate, :qr)
    ");
    $insertTk->execute([
        ':bid'  => $bookingId,
        ':fid'  => $flightId,
        ':fnum' => $flight['flight_number'],
        ':lpad' => $flight['launch_pad'],
        ':gate' => $flight['gate'],
        ':qr'   => 'QR-'.$ticketRef
    ]);

    $pdo->commit();

    /* SUCCESS RESPONSE */
    $change = $payload['amountPaid'] - $totalPrice;

    echo json_encode([
        'success'    => true,
        'ticketCode' => $ticketRef,
        'seatNumber' => $seat,
        'amountPaid' => number_format($payload['amountPaid'],2),
        'change'     => number_format($change,2),
        'qr'         => 'QR-'.$ticketRef,
        'totalPrice' => $totalPrice
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error: '.$e->getMessage()]);
}