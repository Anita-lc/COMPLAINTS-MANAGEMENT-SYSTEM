<?php
// Database configuration
$db_server = "localhost";  
$db_user = "root";
$db_pass = "";
$db_name = "login";  // updated database name

$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}
?>