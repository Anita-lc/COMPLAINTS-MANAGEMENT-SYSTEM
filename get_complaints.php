<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if a specific complaint ID is requested
if (isset($_GET['id'])) {
    $complaint_id = sanitize($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM complaints WHERE id = ? AND userid = ?");
    $stmt->bind_param('is', $complaint_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $complaint = $result->fetch_assoc();

    if ($complaint) {
        echo json_encode($complaint);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Complaint not found']);
    }
    $stmt->close();
} else {
    // Fetch all complaints for the user
    $stmt = $conn->prepare("SELECT * FROM complaints WHERE userid = ? ORDER BY created_at DESC");
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $complaints = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($complaints);
    $stmt->close();
}

$conn->close();
?>
