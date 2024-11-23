<?php
include '../includes/db_connection.php';

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT MaintenanceID, Year, Month, Category, Description, Amount, LoggedBy 
          FROM truckmaintenance
          WHERE CONCAT(MaintenanceID, Year, Month, Category, Description, Amount, LoggedBy) LIKE '%$search%'
          ORDER BY MaintenanceID DESC
          LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Count total records for pagination
$countQuery = "SELECT COUNT(*) AS total FROM truckmaintenance
               WHERE CONCAT(MaintenanceID, Year, Month, Category, Description, Amount, LoggedBy) LIKE '%$search%'";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];

echo json_encode(['data' => $data, 'total' => $totalRecords]);
$conn->close();
?>
