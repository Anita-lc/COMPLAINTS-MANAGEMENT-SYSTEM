<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if complaint ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Complaint ID is required']);
    exit;
}

$complaint_id = (int)$_GET['id'];

// Get complaint details
$stmt = $conn->prepare("
    SELECT c.*, u.email 
    FROM complaints c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
}

$stmt->bind_param('i', $complaint_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Complaint not found']);
    exit;
}

$complaint = $result->fetch_assoc();

// Format response
$response = [
    'success' => true,
    'status' => $complaint['status'],
    'admin_remarks' => $complaint['admin_remarks']
];

// Add additional details if user is logged in and owns the complaint
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $complaint['user_id']) {
    $response['details'] = [
        'category' => $complaint['category'],
        'description' => $complaint['description'],
        'submitted_at' => $complaint['created_at']
    ];
}

echo json_encode($response);

$stmt->close();
$conn->close();
?>
