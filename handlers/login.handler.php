<?php
// handlelogin.php

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

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
}

session_start();
header('Content-Type: application/json');

    $data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username'], $data['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Username and password are required'
    ]);
    exit;
}

$username = trim($data['username']);
$password = $data['password'];
$isAdmin = strpos($username, '#') !== false;
$expectedRole = $isAdmin ? 'admin' : 'client';

try {
    $pdo = connectDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid credentials'
        ]);
        exit;
    }

if ($user['role'] !== $expectedRole) {
        echo json_encode([
            'success' => false,
            'message' => 'Role mismatch'
        ]);
        exit;
    }

    // ✅ Store all required data in session
    $_SESSION['user'] = [
        'id'       => $user['id'],
        'fullname' => $user['fullname'] ?? '',
        'email'    => $user['email'] ?? '',
        'role'     => $user['role']
    ];

    echo json_encode([
        'success'  => true,
        'message'  => 'Login successful',
        'role'     => $user['role'],
        'fullname' => $user['fullname'],
        'user_id'  => $user['id']
    ]);
    } catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
    }