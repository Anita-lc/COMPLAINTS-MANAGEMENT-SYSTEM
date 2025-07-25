<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Please log in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get user info from session
$userid = $_SESSION['user_id'];

// Get user details from database
$user_stmt = $conn->prepare("SELECT firstName, lastName, email FROM users WHERE id = ?");
$user_stmt->bind_param('s', $userid);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'User not found']);
    exit;
}

$user_data = $user_result->fetch_assoc();
$user_stmt->close();

// Get form data
$category = sanitize($_POST['category']);
$description = sanitize($_POST['description']);

// Validate required fields
if (empty($category) || empty($description)) {
    http_response_code(400);
    echo json_encode(['error' => 'Category and description are required']);
    exit;
}

// Handle file upload
$attachment_path = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    
    if (!in_array(strtolower($ext), $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Allowed: jpg, jpeg, png, pdf, doc, docx']);
        exit;
    }

    // Check file size (5MB max)
    if ($_FILES['attachment']['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File too large. Maximum size is 5MB']);
        exit;
    }

    $file_name = uniqid() . '.' . $ext;
    $file_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path)) {
        $attachment_path = $file_name;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload file']);
        exit;
    }
}

// Insert complaint into database
$stmt = $conn->prepare("INSERT INTO complaints (userid, firstName, lastName, email, category, description, attachment, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'open', NOW(), NOW())");

$stmt->bind_param(
    "sssssss",
    $userid,
    $user_data['firstName'],
    $user_data['lastName'], 
    $user_data['email'],
    $category,
    $description,
    $attachment_path
);

if ($stmt->execute()) {
    $complaint_id = $conn->insert_id;
    echo json_encode([
        'success' => true, 
        'message' => 'Complaint submitted successfully',
        'complaint_id' => $complaint_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit complaint: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>