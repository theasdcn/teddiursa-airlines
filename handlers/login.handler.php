<?php
// handlelogin.php

require_once __DIR__ . '/../utils/dbConnect.util.php';

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