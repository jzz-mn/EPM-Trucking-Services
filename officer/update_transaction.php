<?php
// Start the session and verify user authentication
session_start();

// Check if the user is logged in
if (!isset($_SESSION['UserID'])) {
  // Redirect to login page if not logged in
  header("Location: ../login/login.php");
  exit();
}

// Include the database connection file
include '../includes/db_connection.php';

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Enable mysqli exceptions for better error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Initialize response array (optional)
$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  try {
    // Retrieve and sanitize form data
    $transactionId = isset($_POST['transactionId']) ? intval($_POST['transactionId']) : 0;
    $transactionDate = isset($_POST['transactionDate']) ? trim($_POST['transactionDate']) : '';
    $drNo = isset($_POST['drNo']) ? trim($_POST['drNo']) : '';
    $outletName = isset($_POST['outletName']) ? trim($_POST['outletName']) : '';
    $qty = isset($_POST['qty']) ? floatval($_POST['qty']) : 0;
    $kgs = isset($_POST['kgs']) ? floatval($_POST['kgs']) : 0;

    // Begin transaction
    mysqli_begin_transaction($conn);

    // Update the record in the database
    $sql = "UPDATE transactions 
                SET TransactionDate=?, DRno=?, OutletName=?, Qty=?, KGs=?
                WHERE TransactionID=?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
      throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
    }

    // Bind parameters (s = string, d = double, i = integer)
    mysqli_stmt_bind_param($stmt, "sssdii", $transactionDate, $drNo, $outletName, $qty, $kgs, $transactionId);

    // Execute the statement
    mysqli_stmt_execute($stmt);

    // Close the statement
    mysqli_stmt_close($stmt);

    // Insert activity log
    $currentUserID = $_SESSION['UserID'];
    $action = "Updated Transaction ID: " . $transactionId;
    $currentTimestamp = date("Y-m-d H:i:s");

    $sqlLog = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, NOW())";
    $stmtLog = mysqli_prepare($conn, $sqlLog);
    if (!$stmtLog) {
      throw new Exception("Failed to prepare activity log insertion: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmtLog, "is", $currentUserID, $action);

    // Execute the activity log insertion
    mysqli_stmt_execute($stmtLog);

    // Close the statement
    mysqli_stmt_close($stmtLog);

    // Commit the transaction
    mysqli_commit($conn);

    // Redirect back to the trucks page or show success message
    header("Location: trucks.php");
    exit();
  } catch (Exception $e) {
    // Rollback the transaction on error
    mysqli_rollback($conn);

    // Return error message
    echo "Error: " . $e->getMessage();
  }
} else {
  echo "Invalid request.";
}

// Close the database connection
mysqli_close($conn);
?>