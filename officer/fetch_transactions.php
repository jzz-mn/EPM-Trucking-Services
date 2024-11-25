<?php
include '../includes/db_connection.php';

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query to fetch transactions
$query = "SELECT TransactionID, TransactionDate, DRno, OutletName, Qty, KGs 
          FROM transactions 
          WHERE CONCAT(TransactionID, TransactionDate, DRno, OutletName, Qty, KGs) LIKE ?
          ORDER BY TransactionID DESC 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$searchTerm = "%$search%";
$stmt->bind_param("sii", $searchTerm, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Fetch rows
$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

// Get total count of matching rows
$countQuery = "SELECT COUNT(*) as total 
               FROM transactions 
               WHERE CONCAT(TransactionID, TransactionDate, DRno, OutletName, Qty, KGs) LIKE ?";
$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param("s", $searchTerm);
$countStmt->execute();
$countResult = $countStmt->get_result();
$total = $countResult->fetch_assoc()['total'];

// Output JSON response
echo json_encode([
    'data' => $transactions,
    'total' => $total
]);

$conn->close();
