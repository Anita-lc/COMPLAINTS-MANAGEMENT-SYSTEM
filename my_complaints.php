<?php
require_once 'config.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}


// Form data
$userid = $_SESSION['user_id'];
$firstName = sanitize($_POST['firstName']);
$lastName = sanitize($_POST['lastName']);
$email = sanitize($_POST['email']);
$category = sanitize($_POST['category']);
$description = sanitize($_POST['description']);
$status = 'open';
$admin_remarks = '';
$created_at = date('Y-m-d H:i:s');
$updated_at = $created_at;

// File upload
$attachment_path = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = _DIR_ . '/uploads/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

    $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    if (!in_array(strtolower($ext), $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }

    $file_name = uniqid() . '.' . $ext;
    $file_path = $upload_dir . $file_name;
    move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path);
    $attachment_path = 'uploads/' . $file_name;
}

// Save to database
$stmt = $db->prepare("INSERT INTO complaints 
(userid, firstName, lastName, email, category, description, attachment, status, admin_remarks, created_at, updated_at)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "sssssssssss",
    $userid, $firstName, $lastName, $email, $category, $description,
    $attachment_path, $status, $admin_remarks, $created_at, $updated_at
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Complaint submitted']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit complaint', 'details' => $stmt->error]);
}

$stmt->close();
$db->close();
?>