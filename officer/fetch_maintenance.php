<?php
header('Content-Type: application/json');
include '../includes/db_connection.php';

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$whereClause = "";
if (!empty($search)) {
    $whereClause = "WHERE MaintenanceID LIKE '%$search%' OR Year LIKE '%$search%' OR Month LIKE '%$search%' OR Category LIKE '%$search%' OR Description LIKE '%$search%' OR Amount LIKE '%$search%' OR LoggedBy LIKE '%$search%'";
}

$totalQuery = "SELECT COUNT(*) as total FROM truckmaintenance $whereClause";
$totalResult = $conn->query($totalQuery);
$total = $totalResult->fetch_assoc()['total'];

$dataQuery = "SELECT MaintenanceID, Year, Month, Category, Description, Amount, LoggedBy FROM truckmaintenance $whereClause ORDER BY MaintenanceID DESC LIMIT $limit OFFSET $offset";
$dataResult = $conn->query($dataQuery);

$data = [];
if ($dataResult->num_rows > 0) {
    while ($row = $dataResult->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode(['data' => $data, 'total' => $total]);

$conn->close();
?>
