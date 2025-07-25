<?php
// Database configuration
$db_server = "localhost";  
$db_user = "root";
$db_pass = "";
$db_name = "login";

// Application configuration
define('APP_NAME', 'University Complaints System');
define('APP_VERSION', '1.0.0');
define('JWT_SECRET', 'your-secret-key-here'); // Change this in production
define('PASSWORD_COST', 10); // Cost parameter for password hashing

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create connection (renamed to $conn to match usage below)
    $conn = new mysqli($db_server, $db_user, $db_pass, $db_name);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset to utf8
    $conn->set_charset("utf8");

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection error']));
}

// Function to sanitize input
function sanitize($data) {
    global $conn;
    return htmlspecialchars($conn->real_escape_string(trim($data)));
}

// Helper function to hash passwords
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT, ['cost' => PASSWORD_COST]);
}

// Helper function to verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Helper function to generate JWT token
function generateToken($user) {
    $token = [
        'user_id' => $user['user_id'],
        'role' => $user['role'],
        'iat' => time(),
        'exp' => time() + (60 * 60) // 1 hour expiration
    ];
    return jwt_encode($token, JWT_SECRET);
}

// Helper function to decode JWT token
function decodeToken($token) {
    try {
        $decoded = jwt_decode($token, JWT_SECRET, ['HS256']);
        return $decoded;
    } catch (Exception $e) {
        return null;
    }
}

// Helper function to check if user is admin
function isAdmin($user) {
    return $user['role'] === 'admin';
}

// Helper function to log errors
function logError($message) {
    error_log(date('Y-m-d H:i:s') . " - " . $message . "\n", 3, __DIR__ . '/error.log');
}

// Function to generate unique ID
function generateUserID() {
    return 'U' . bin2hex(random_bytes(16));
}
?>
