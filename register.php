<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to a file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

header('Content-Type: application/json');

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'connect.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        file_put_contents(__DIR__ . '/bad_input.log', $rawInput);
        throw new Exception('Invalid JSON input', 400);
    }

    // Validate required fields
    $required_fields = ['id', 'firstName', 'lastName', 'email', 'password', 'phoneNumber'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field", 400);
        }
    }

    // Sanitize input
    $id        = sanitize($input['id']);
    $firstName = sanitize($input['firstName']);
    $lastName  = sanitize($input['lastName']);
    $email     = sanitize($input['email']);
    $phone     = sanitize($input['phoneNumber']);
    $password  = $input['password'];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format', 400);
    }

    // Validate password length
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters', 400);
    }

    // Check for existing user ID or email
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE id = ? OR email = ?");
    $stmt->bind_param('ss', $id, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $conflict = ($row['id'] === $id) ? 'ID' : 'Email';
        throw new Exception("$conflict already exists", 409);
    }
    $stmt->close();

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    if (!$password_hash) {
        throw new Exception('Password hashing failed', 500);
    }

    // Insert into DB
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO users (id, firstName, lastName, email, phoneNumber, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error, 500);
        }

        $stmt->bind_param('ssssss', $id, $firstName, $lastName, $email, $phone, $password_hash);
        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $stmt->error, 500);
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'user' => [
                'id' => $id,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email
            ]
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}

// Close connection
if (isset($stmt)) $stmt->close();
if (isset($conn)) $conn->close();

// Sanitize helper
function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}
