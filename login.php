<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// existing code
require_once 'connect.php';
header('Content-Type: application/json');
require_once 'config.php';

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing email or password']);
    exit;
}

$email = sanitize($input['email']);
$password = $input['password'];

// Check if user exists
$stmt = $conn->prepare("SELECT id, password_hash, 'user' as role FROM users WHERE email = ? UNION SELECT username as id, password_hash, 'admin' as role FROM admins WHERE username = ?");
$stmt->bind_param('ss', $email, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

$user = $result->fetch_assoc();

// Verify password
if (!verifyPassword($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

// Start session
session_start();
$_SESSION['id'] = $user['id'];
$_SESSION['role'] = $user['role'];

// Set session timeout (1 hour)
$_SESSION['last_activity'] = time();
$_SESSION['expiry'] = time() + 3600;

// Set secure session cookie
session_regenerate_id(true);

// Return success response
echo json_encode([
    'success' => true,
    'role' => $user['role']
]);

$stmt->close();
$conn->close();
?>


