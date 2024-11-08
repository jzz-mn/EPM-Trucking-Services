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
  $tgID = intval($_GET['TransactionGroupID'] ?? 0);
  if ($tgID <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Transaction Group ID.']);
    exit;
  }

  // Fetch transaction group details
  $tgQuery = "SELECT tg.*, t.TransactionID, t.DRno, t.OutletName, t.Qty, t.KGs
              FROM transactiongroup tg
              LEFT JOIN transactions t ON tg.TransactionGroupID = t.TransactionGroupID
              WHERE tg.TransactionGroupID = ?";
  $tgStmt = $conn->prepare($tgQuery);
  if (!$tgStmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare transaction group query.']);
    exit;
  }
  $tgStmt->bind_param("i", $tgID);
  $tgStmt->execute();
  $tgResult = $tgStmt->get_result();

  if ($tgResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Transaction group not found.']);
    exit;
  }

  $tg = $tgResult->fetch_assoc();
  $tgStmt->close();

  // Fetch associated transactions
  $transQuery = "SELECT * FROM transactions WHERE TransactionGroupID = ?";
  $transStmt = $conn->prepare($transQuery);
  if (!$transStmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare transactions query.']);
    exit;
  }
  $transStmt->bind_param("i", $tgID);
  $transStmt->execute();
  $transResult = $transStmt->get_result();

  $transactions = [];
  while ($trans = $transResult->fetch_assoc()) {
    $transactions[] = $trans;
  }
  $transStmt->close();

  echo json_encode([
    'success' => true,
    'data' => [
      'TransactionGroupID' => $tg['TransactionGroupID'],
      'TruckID' => $tg['TruckID'],
      'Date' => $tg['Date'],
      'TollFeeAmount' => $tg['TollFeeAmount'],
      'RateAmount' => $tg['RateAmount'],
      'TotalKGs' => $tg['TotalKGs'],
      'Transactions' => $transactions
    ]
  ]);
  exit;
}
?>
