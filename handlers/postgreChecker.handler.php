<?php
require_once UTILS_PATH . '/envSetter.util.php';

$host = $typeConfig['pg_host'];
$port = $typeConfig['pg_port'];
$username = $typeConfig['pg_user'];
$password = $typeConfig['pg_pass'];
$dbname = $typeConfig['pg_db'];

$conn_string = "host=$host port=$port dbname=$dbname user=$username password=$password";
$sslmode = getenv('PG_SSLMODE') ?: ($_ENV['PG_SSLMODE'] ?? '');
if (!empty($sslmode)) {
    $conn_string .= " sslmode=$sslmode";
}

$dbconn = pg_connect($conn_string);

if (!$dbconn) {
    echo "❌ Connection Failed: ", pg_last_error() . "  <br>";
    exit();
} else {
    echo "✔️ PostgreSQL Connection  <br>";
    pg_close($dbconn);
}