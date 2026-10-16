<?php
// utils/dbSeederPostgresql.util.php

declare(strict_types=1);

require 'vendor/autoload.php';
require 'bootstrap.php';
require_once UTILS_PATH . '/envSetter.util.php';

$users = require_once DUMMIES_PATH . '/users.staticData.php';
$planets = require_once DUMMIES_PATH . '/planets.staticData.php';
$flights = require_once DUMMIES_PATH . '/flights.staticData.php';
$booking = require_once DUMMIES_PATH . '/booking.staticData.php';
$tickets = require_once DUMMIES_PATH . '/tickets.staticData.php';

$dsn = "pgsql:host={$pgConfig['host']};port={$pgConfig['port']};dbname={$pgConfig['db']}";
$sslmode = getenv('PG_SSLMODE') ?: ($_ENV['PG_SSLMODE'] ?? '');
if (!empty($sslmode)) {
  $dsn .= ";sslmode=$sslmode";
}
$pdo = new PDO($dsn, $pgConfig['user'], $pgConfig['pass'], [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

echo "✅ Connected to PostgreSQL via PDO\n";

// Truncate tables to avoid duplicates
echo "🧹 Truncating tables...\n";
$pdo->exec('
  TRUNCATE TABLE public."bookings",
                  public."tickets",
                  public."users",
                  public."images",
                  public."flights",
                  public."planets"
  RESTART IDENTITY CASCADE
');

// Insert planets
$planetStmt = $pdo->prepare("INSERT INTO public.planets (name, description, distance_km, base_price) VALUES (:name, :description, :distance_km, :base_price) RETURNING id, name");
$planetMap = [];
echo "🚀 Seeding planets...\n";
foreach ($planets as $p) {
    $planetStmt->execute([
        ':name' => $p['name'],
        ':description' => $p['description'],
        ':distance_km' => $p['distance_km'],
        ':base_price' => $p['base_price']
    ]);

    $result = $planetStmt->fetch(PDO::FETCH_ASSOC);
    $planetMap[$result['name']] = $result['id'];
}


// 2. Seed flights and build flight map
$flightStmt = $pdo->prepare("
  INSERT INTO public.flights (
    departure_planet_id,
    arrival_planet_id,
    departure_time,
    return_time,
    price,
    flight_number,
    destination
  ) VALUES (
    :departure_planet_id,
    :arrival_planet_id,
    :departure_time,
    :return_time,
    :price,
    :flight_number,
    :destination
  ) RETURNING id, flight_number
");



$flightMap = [];
echo "✈️ Seeding flights...\n";
foreach ($flights as $f) {
    $departureName = $f['departure_planet'];
    $arrivalName = $f['arrival_planet'];

    // Check if the planets exist in planetMap
    if (!isset($planetMap[$departureName])) {
        throw new Exception("❌ Unknown departure planet: $departureName");
    }
    if (!isset($planetMap[$arrivalName])) {
        throw new Exception("❌ Unknown arrival planet: $arrivalName");
    }

    $flightStmt->execute([
    ':departure_planet_id' => $planetMap[$departureName],
    ':arrival_planet_id' => $planetMap[$arrivalName],
    ':departure_time' => $f['departure_time'],
    ':return_time' => $f['return_time'],
    ':price' => $f['price'],
    ':flight_number' => $f['flight_number'],
    ':destination' => $f['destination'],
]);


    $result = $flightStmt->fetch(PDO::FETCH_ASSOC);
    $flightMap[$result['flight_number']] = $result['id'];
}

// 3. Seed users and image associations
$userStmt = $pdo->prepare("INSERT INTO public.\"users\" (
  fullname, password, contact_num, username, role, flight_id,
  date_of_birth, gender, nationality, email, emergency_contact,
  passport_number, passport_expiry, passport_issuing_country
) VALUES (
  :fullname, :password, :contact_num, :username, :role, :flight_id,
  :date_of_birth, :gender, :nationality, :email, :emergency_contact,
  :passport_number, :passport_expiry, :passport_issuing_country
) RETURNING id, username");


$imageStmt = $pdo->prepare("INSERT INTO public.\"images\" (filename, filepath, mimetype, size_bytes) VALUES (:filename, :filepath, :mimetype, :size_bytes) RETURNING id");
$updateUserPassportStmt = $pdo->prepare("UPDATE public.\"users\" SET passport_image_id = :image_id WHERE id = :user_id");

$userMap = [];
echo "🌿 Seeding users and images...\n";
foreach ($users as $u) {
$flightId = $flightMap[array_rand($flightMap)];


  $userStmt->execute([
  ':fullname' => $u['fullname'],
  ':password' => password_hash($u['password'], PASSWORD_DEFAULT),
  ':contact_num' => $u['contact_num'],
  ':username' => $u['username'],
  ':role' => $u['role'],
  ':flight_id' => $flightId,
  ':date_of_birth' => $u['date_of_birth'],
  ':gender' => $u['gender'],
  ':nationality' => $u['nationality'],
  ':email' => $u['email'],
  ':emergency_contact' => $u['emergency_contact'],
  ':passport_number' => $u['passport_number'],
  ':passport_expiry' => $u['passport_expiry'],
  ':passport_issuing_country' => $u['passport_issuing_country'],
]);

  $userResult = $userStmt->fetch(PDO::FETCH_ASSOC);
  $userId = $userResult['id'];
  $userMap[$userResult['username']] = $userId;

  $passport = $u['passport_image'];
  $imageStmt->execute([
    ':filename' => $passport['filename'],
    ':filepath' => $passport['filepath'],
    ':mimetype' => $passport['mimetype'],
    ':size_bytes' => $passport['size_bytes'],
  ]);
  $imageId = $imageStmt->fetchColumn();

  $updateUserPassportStmt->execute([
    ':image_id' => $imageId,
    ':user_id' => $userId,
  ]);
}

// 4. Seed bookings
$bookingStmt = $pdo->prepare("INSERT INTO public.\"bookings\" (user_id, flight_id, travel_date, seat_number, ticket_code, status, feedback) VALUES (:user_id, :flight_id, :travel_date, :seat_number, :ticket_code, :status, :feedback) RETURNING id");
$bookingIds = [];
echo "🚊 Seeding bookings...\n";
$flightIds = array_values($flightMap);
$userIds = array_values($userMap);
foreach ($booking as $index => $b) {
  $userId = $userIds[$index % count($userIds)];
  $flightId = $flightIds[$index % count($flightIds)];
  $bookingStmt->execute([
    ':user_id' => $userId,
    ':flight_id' => $flightId,
    ':travel_date' => $b['travel_date'],
    ':seat_number' => $b['seat_number'],
    ':ticket_code' => $b['ticket_code'],
    ':status' => $b['status'],
    ':feedback' => $b['feedback'],
  ]);
  $bookingIds[] = $bookingStmt->fetchColumn();
}

// 5. Seed tickets for database
$ticketStmt = $pdo->prepare("
    INSERT INTO public.\"tickets\" (
        reference_number,
        passenger_name,
        gender,
        nationality,
        contact_number,
        passport_number,
        departure_location,
        destination,
        departure_date,
        departure_time,
        payment_method,
        amount_paid,
        change_given
    ) VALUES (
        :reference_number,
        :passenger_name,
        :gender,
        :nationality,
        :contact_number,
        :passport_number,
        :departure_location,
        :destination,
        :departure_date,
        :departure_time,
        :payment_method,
        :amount_paid,
        :change_given
    )
");

echo "🎟️ Seeding tickets...\n";

foreach ($bookingIds as $i => $bookingId) {
    $flightId = $flightIds[$i % count($flightIds)];

    $ticketStmt->execute([
        ':reference_number'  => strtoupper(bin2hex(random_bytes(4))), 
        ':passenger_name'    => "Test Passenger " . ($i + 1),
        ':gender'            => "Other",
        ':nationality'       => "Martian",
        ':contact_number'    => "09981234567",
        ':passport_number'   => "MP" . rand(100000, 999999),
        ':departure_location'=> "Earth Spaceport",
        ':destination'       => "Mars",
        ':departure_date'    => date('Y-m-d', strtotime("+$i days")),
        ':departure_time'    => "08:00:00",
        ':payment_method'    => "Cash",
        ':amount_paid'       => 1250.00,
        ':change_given'      => 0.00,
    ]);
}

echo "✅ PostgreSQL seeding complete!\n";
