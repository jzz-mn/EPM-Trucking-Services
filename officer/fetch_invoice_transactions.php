<?php
session_start();
include '../includes/db_connection.php';

header('Content-Type: application/json');

// Ensure the user is logged in
if (!isset($_SESSION['UserID'])) {
  echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $billingInvoiceNo = intval($_POST['BillingInvoiceNo'] ?? 0);
  $billingStartDate = $_POST['BillingStartDate'] ?? '';
  $billingEndDate = $_POST['BillingEndDate'] ?? '';

  if ($billingInvoiceNo <= 0 || empty($billingStartDate) || empty($billingEndDate)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
  }

  if ($billingStartDate > $billingEndDate) {
    echo json_encode(['success' => false, 'message' => 'Billing Start Date cannot be after Billing End Date.']);
    exit;
  }

  $tgQuery = "SELECT tg.*, t.TransactionID, t.DRno, t.OutletName, t.Qty, t.KGs
              FROM transactiongroup tg
              LEFT JOIN transactions t ON tg.TransactionGroupID = t.TransactionGroupID
              WHERE tg.BillingInvoiceNo = ? AND tg.Date BETWEEN ? AND ?";
  $tgStmt = $conn->prepare($tgQuery);
  if (!$tgStmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare transaction group query.']);
    exit;
  }
  $tgStmt->bind_param("iss", $billingInvoiceNo, $billingStartDate, $billingEndDate);
  $tgStmt->execute();
  $tgResult = $tgStmt->get_result();

  // Organize transactions by TransactionGroupID
  $transactionGroups = [];
  while ($row = $tgResult->fetch_assoc()) {
    $tgID = $row['TransactionGroupID'];
    if (!isset($transactionGroups[$tgID])) {
      $transactionGroups[$tgID] = [
        'TransactionGroupID' => $row['TransactionGroupID'],
        'TruckID' => $row['TruckID'],
        'Date' => $row['Date'],
        'TollFeeAmount' => $row['TollFeeAmount'],
        'RateAmount' => $row['RateAmount'],
        'TotalKGs' => $row['TotalKGs']
      ];
    }
  }
  $tgStmt->close();

  // Fetch HTML for transactions table
  $html = '';
  foreach ($transactionGroups as $tg) {
    $html .= '<tr data-tg-id="' . htmlspecialchars($tg['TransactionGroupID']) . '">';
    $html .= '<td>' . htmlspecialchars($tg['TransactionGroupID']) . '</td>';
    $html .= '<td>' . htmlspecialchars($tg['TruckID']) . '</td>';
    $html .= '<td>' . htmlspecialchars($tg['Date']) . '</td>';
    $html .= '<td class="tollFeeAmount">' . number_format($tg['TollFeeAmount'], 2) . '</td>';
    $html .= '<td class="rateAmount">' . number_format($tg['RateAmount'], 2) . '</td>';
    $html .= '<td class="totalKGs">' . number_format($tg['TotalKGs'], 2) . '</td>';
    $html .= '<td>';
    $html .= '<button type="button" class="btn btn-sm btn-primary editTransactionBtn">Edit</button> ';
    $html .= '<button type="button" class="btn btn-sm btn-danger deleteTransactionBtn">Delete</button>';
    $html .= '</td>';
    $html .= '</tr>';
  }

  echo json_encode(['success' => true, 'html' => $html]);
  exit;
}
?>
