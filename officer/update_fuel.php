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
    $fuelID = isset($_POST['updateFuelID']) ? intval($_POST['updateFuelID']) : 0;
    $date = isset($_POST['updateDate']) ? trim($_POST['updateDate']) : '';
    $liters = isset($_POST['updateLiters']) ? floatval($_POST['updateLiters']) : 0;
    $unitPrice = isset($_POST['updateUnitPrice']) ? floatval($_POST['updateUnitPrice']) : 0;
    $fuelType = isset($_POST['updateFuelType']) ? trim($_POST['updateFuelType']) : '';
    $amount = $liters * $unitPrice;

    // Begin transaction
    mysqli_begin_transaction($conn);

    // Update the record in the database
    $sql = "UPDATE fuel 
                SET Date=?, Liters=?, UnitPrice=?, FuelType=?, Amount=?
                WHERE FuelID=?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
      throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
    }

    // Bind parameters (s = string, d = double, i = integer)
    mysqli_stmt_bind_param($stmt, "sddssi", $date, $liters, $unitPrice, $fuelType, $amount, $fuelID);

    // Execute the statement
    mysqli_stmt_execute($stmt);

    // Close the statement
    mysqli_stmt_close($stmt);

    // Insert activity log
    $currentUserID = $_SESSION['UserID'];
    $action = "Updated Fuel ID: " . $fuelID;
    $currentTimestamp = date("Y-m-d H:i:s");

    $sqlLog = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?,NOW())";
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

    // Redirect back to the finance page or refresh the page
    header("Location: finance.php");
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