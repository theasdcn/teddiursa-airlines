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
    } catch (\Throwable $e) {
        // Fallback: If cloud database is offline, authenticate with dummy data
        $dummyUsers = [
            [
                'username' => 'johndoe',
                'password' => 'Admin@123',
                'fullname' => 'John Doe',
                'email'    => 'john.doe@example.com',
                'role'     => 'admin',
                'id'       => 'admin-001'
            ],
            [
                'username' => 'alice',
                'password' => 'Alice@1234',
                'fullname' => 'Alice Reyes',
                'email'    => 'alice.reyes@example.com',
                'role'     => 'client',
                'id'       => 'client-001'
            ],
            [
                'username' => 'mark',
                'password' => 'Markie@456',
                'fullname' => 'Mark Fernandez',
                'email'    => 'mark.fernandez@example.com',
                'role'     => 'client',
                'id'       => 'client-002'
            ]
        ];

        foreach ($dummyUsers as $u) {
            if (strtolower($u['username']) === strtolower($username) && $u['password'] === $password) {
                $_SESSION['user'] = [
                    'id'       => $u['id'],
                    'fullname' => $u['fullname'],
                    'email'    => $u['email'],
                    'role'     => $u['role']
                ];

                echo json_encode([
                    'success'  => true,
                    'message'  => 'Login successful',
                    'role'     => $u['role'],
                    'fullname' => $u['fullname'],
                    'user_id'  => $u['id']
                ]);
                exit;
            }
        }

        echo json_encode([
            'success' => false,
            'message' => 'Invalid credentials'
        ]);
    }