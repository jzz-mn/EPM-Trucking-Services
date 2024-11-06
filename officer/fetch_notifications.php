<?php
// fetch_notifications.php

session_start();
header('Content-Type: application/json');

// Include the database connection
require_once('../includes/db_connection.php');

// Ensure the user is logged in
if (!isset($_SESSION['Username'])) {
  echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
  exit();
}

$userID = intval($_SESSION['UserID']);

// Fetch LastSeenLogID from the database
$stmt = $conn->prepare("SELECT LastSeenLogID FROM useraccounts WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($last_seen_logid_db);
$stmt->fetch();
$stmt->close();

$last_seen_logid = $last_seen_logid_db ?? 0;

// Fetch new notifications
$stmt = $conn->prepare("
    SELECT al.LogID, al.Action, al.TimeStamp, ua.Username 
    FROM activitylogs al
    JOIN useraccounts ua ON al.UserID = ua.UserID
    WHERE al.UserID != ? 
      AND al.Action NOT IN ('Logged In', 'Logged Out') 
      AND al.LogID > ? 
      AND al.TimeStamp >= DATE_SUB(NOW(), INTERVAL 10 DAY)
    ORDER BY al.TimeStamp DESC
");
$stmt->bind_param("ii", $userID, $last_seen_logid);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$new_notification_count = count($notifications);
$max_logid = 0;
if ($new_notification_count > 0) {
  $max_logid = max(array_column($notifications, 'LogID'));
}
$stmt->close();

// Return as JSON
echo json_encode([
  'status' => 'success',
  'notifications' => $notifications,
  'new_count' => $new_notification_count,
  'max_logid' => $max_logid
]);
?>