<?php
require_once 'config.php';

// Test database connection
echo "Testing database connection...\n";
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
echo "✓ Database connection successful\n\n";

// Test user registration
echo "Testing user registration...\n";
$test_user = [
    'id' => 'TEST123',
    'firstName' => 'Test',
    'lastName' => 'User',
    'email' => 'test@example.com',
    'password' => 'password123'
];

// Insert test user
$stmt = $conn->prepare("INSERT INTO users (id, firstName, lastName, email, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssssss', 
    $test_user['id'],
    $test_user['firstName'],
    $test_user['lastName'],
    $test_user['email'],
    password_hash($test_user['password'], PASSWORD_DEFAULT),
    'user'
);

if ($stmt->execute()) {
    echo "✓ User registration successful\n\n";
} else {
    echo "✗ User registration failed\n\n";
}

// Test complaint submission
echo "Testing complaint submission...\n";
$test_complaint = [
    'user_id' => $test_user['id'],
    'first_name' => $test_user['firstName'],
    'last_name' => $test_user['lastName'],
    'email' => $test_user['email'],
    'category' => 'facilities',
    'description' => 'Test complaint description'
    
];

// Insert test complaint
$stmt = $conn->prepare("INSERT INTO complaints (user_id, first_name, last_name, email, category, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('sssssss',
    $test_complaint['user_id'],
    $test_complaint['first_name'],
    $test_complaint['last_name'],
    $test_complaint['email'],
    $test_complaint['category'],
    $test_complaint['description'],
    'open'
);

if ($stmt->execute()) {
    $complaint_id = $conn->insert_id;
    echo "✓ Complaint submission successful\n\n";
} else {
    echo "✗ Complaint submission failed\n\n";
}

// Test complaint tracking
echo "Testing complaint tracking...\n";
$stmt = $conn->prepare("SELECT * FROM complaints WHERE id = ?");
$stmt->bind_param('i', $complaint_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "✓ Complaint tracking successful\n\n";
} else {
    echo "✗ Complaint tracking failed\n\n";
}

// Test admin login
echo "Testing admin login...\n";
$test_admin = [
    'username' => 'admin',
    'password' => 'password123' // Default password from schema.sql
];

$stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
$stmt->bind_param('s', $test_admin['username']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    if (password_verify($test_admin['password'], $admin['password_hash'])) {
        echo "✓ Admin login successful\n\n";
    } else {
        echo "✗ Admin login failed (password mismatch)\n\n";
    }
} else {
    echo "✗ Admin login failed (user not found)\n\n";
}

// Test complaint status update
echo "Testing complaint status update...\n";
$new_status = 'in progress';
$admin_remarks = 'Test remarks';

$stmt = $conn->prepare("UPDATE complaints SET status = ?, admin_remarks = ? WHERE id = ?");
$stmt->bind_param('ssi', $new_status, $admin_remarks, $complaint_id);

if ($stmt->execute()) {
    echo "✓ Complaint status update successful\n\n";
} else {
    echo "✗ Complaint status update failed\n\n";
}

// Cleanup test data
echo "Cleaning up test data...\n";
$stmt = $conn->prepare("DELETE FROM complaints WHERE user_id = ?");
$stmt->bind_param('s', $test_user['id']);
$stmt->execute();

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param('s', $test_user['id']);
$stmt->execute();

echo "✓ Test data cleanup successful\n\n";

$conn->close();
?>

