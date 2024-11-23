<?php
include '../includes/db_connection.php';

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Adjust query for performance with indexed fields
$query = "SELECT TransactionID, TransactionDate, DRno, OutletName, Qty, KGs 
          FROM transactions
          WHERE CONCAT(TransactionID, TransactionDate, DRno, OutletName, Qty, KGs) LIKE '%$search%'
          ORDER BY TransactionID DESC
          LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Count total records
$countQuery = "SELECT COUNT(*) AS total 
               FROM transactions
               WHERE CONCAT(TransactionID, TransactionDate, DRno, OutletName, Qty, KGs) LIKE '%$search%'";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];

echo json_encode(['data' => $data, 'total' => $totalRecords]);
$conn->close();
