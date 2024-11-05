<?php
// fetch_notifications.php

session_start();
require_once('../includes/db_connection.php');

if (!isset($_SESSION['UserID'])) {
  echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
  exit();
}

$userID = intval($_SESSION['UserID']);
$last_seen_logid = 0;

// Fetch LastSeenLogID from the database
$stmt = $conn->prepare("SELECT LastSeenLogID FROM useraccounts WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($last_seen_logid_db);
$stmt->fetch();
$stmt->close();

$last_seen_logid = $last_seen_logid_db ?? 0;

// Fetch new notifications for badge count
$stmt = $conn->prepare("
    SELECT COUNT(*) AS new_count
    FROM activitylogs al
    WHERE al.UserID != ? 
      AND al.Action NOT IN ('Logged In', 'Logged Out') 
      AND al.LogID > ? 
      AND al.TimeStamp >= DATE_SUB(NOW(), INTERVAL 10 DAY)
");
$stmt->bind_param("ii", $userID, $last_seen_logid);
$stmt->execute();
$stmt->bind_result($new_notification_count);
$stmt->fetch();
$stmt->close();

echo json_encode(['status' => 'success', 'new_count' => $new_notification_count]);
?>
