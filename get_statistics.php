<?php
require_once 'connect.php';

$pending = $resolved = $rejected = 0;

$sql = "SELECT status, COUNT(*) as count FROM complaints GROUP BY status";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    switch (strtolower($row['status'])) {
        case 'pending': $pending = $row['count']; break;
        case 'resolved': $resolved = $row['count']; break;
        case 'rejected': $rejected = $row['count']; break;
    }
}

echo json_encode([
    'pending' => $pending,
    'resolved' => $resolved,
    'rejected' => $rejected
]);
?>
