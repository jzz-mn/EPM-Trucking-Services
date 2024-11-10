<?php
include '../includes/db_connection.php';

$year = $_GET['year'] ?? date('Y'); // Default to current year if no year is provided

$query = "SELECT MONTH, SUM(Amount) AS TotalAmount FROM truckmaintenance WHERE YEAR = ? GROUP BY MONTH ORDER BY MONTH";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $year);
$stmt->execute();
$result = $stmt->get_result();

$months = [];
$amounts = [];
while ($row = $result->fetch_assoc()) {
    $months[] = date("F", mktime(0, 0, 0, $row['MONTH'], 10)); // Convert month number to month name
    $amounts[] = $row['TotalAmount'];
}

echo json_encode(['months' => $months, 'amounts' => $amounts]);
