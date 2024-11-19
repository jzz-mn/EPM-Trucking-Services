<?php
session_start();
include '../includes/db_connection.php';
// Check if the user is logged in (redundant if already handled in header.php)
if (!isset($_SESSION['UserID'])) {
    header('Location: ../index.php');
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize POST data
    $maintenanceDate = $_POST['maintenanceDate'];
    $truckId = $_POST['truck_id'];
    $category = $_POST['maintenanceCategory'];
    $description = $_POST['maintenanceDescription'];
    $loggedBy = $_SESSION['UserID'];

    // Validate required fields
    if (empty($maintenanceDate) || empty($truckId) || empty($category) || empty($description)) {
        echo "All fields are required.";
        exit;
    }

    // Extract Year and Month from the maintenanceDate
    $timestamp = strtotime($maintenanceDate);
    if ($timestamp === false) {
        echo "Invalid date format.";
        exit;
    }
    $year = date('Y', $timestamp); // e.g., 2024
    $month = date('F', $timestamp); // e.g., "November"

    // Begin transaction
    $conn->begin_transaction();

    try {
        // 1. Insert into truckmaintenance
        $sql = "INSERT INTO truckmaintenance (TruckID, Year, Month, Category, Description, LoggedBy) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Preparation failed: " . $conn->error);
        }

        $stmt->bind_param("iisssi", $truckId, $year, $month, $category, $description, $loggedBy);

        if (!$stmt->execute()) {
            throw new Exception("Execution failed: " . $stmt->error);
        }

        // Retrieve the last inserted MaintenanceID
        $maintenanceId = $conn->insert_id;
        $stmt->close();

        // 2. Insert into activitylogs
        $action = "Added Maintenance Record ID " . $maintenanceId;
        $sql_log = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, NOW())";
        $stmt_log = $conn->prepare($sql_log);
        if (!$stmt_log) {
            throw new Exception("Preparation for activity log failed: " . $conn->error);
        }

        $stmt_log->bind_param("is", $loggedBy, $action);

        if (!$stmt_log->execute()) {
            throw new Exception("Execution of activity log failed: " . $stmt_log->error);
        }

        $stmt_log->close();

        // Commit transaction
        $conn->commit();

        // Redirect to maintenance page with success message
        header("Location: maintenance.php?success=1");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }

    // Close the database connection
    $conn->close();
}
?>