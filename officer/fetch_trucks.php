<?php
session_start();
include '../includes/db_connection.php';

header('Content-Type: application/json');

// Ensure the user is logged in
if (!isset($_SESSION['UserID'])) {
  echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $trucksQuery = "SELECT TruckID, PlateNo, TruckBrand FROM trucksinfo ORDER BY PlateNo ASC";
  $trucksResult = $conn->query($trucksQuery);

  if ($trucksResult) {
    $trucks = [];
    while ($row = $trucksResult->fetch_assoc()) {
      $trucks[] = $row;
    }
    echo json_encode(['success' => true, 'trucks' => $trucks]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch trucks.']);
  }
  exit;
}
?>
