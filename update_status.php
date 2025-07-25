<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$complaint_id = (int)$input['id'];
$new_status = sanitize($input['status']);
$remarks = isset($input['remarks']) ? sanitize($input['remarks']) : null;

// Validate status
$valid_statuses = ['open', 'in progress', 'resolved'];
if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status']);
    exit;
}

// Update complaint status
$stmt = $conn->prepare("
    UPDATE complaints 
    SET status = ?, admin_remarks = ?, updated_at = CURRENT_TIMESTAMP 
    WHERE id = ?
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
}

$stmt->bind_param('sss', $new_status, $remarks, $complaint_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update status']);
}

$stmt->close();
$conn->close();
?>
