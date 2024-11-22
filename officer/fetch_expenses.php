<?php
include '../includes/db_connection.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Rows per page
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0; // Starting row
$search = isset($_GET['search']) ? $_GET['search'] : ''; // Search term

// Build query with filtering (if search is provided)
$filterQuery = "";
if (!empty($search)) {
    $filterQuery = "WHERE ExpenseID LIKE '%$search%' OR Date LIKE '%$search%' 
        OR SalaryAmount LIKE '%$search%' OR MobileAmount LIKE '%$search%' 
        OR OtherAmount LIKE '%$search%' OR TotalExpense LIKE '%$search%'";
}

$query = "SELECT * FROM expenses $filterQuery ORDER BY ExpenseID DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

$data = [];
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}

// Get total count for pagination
$totalQuery = "SELECT COUNT(*) as total FROM expenses $filterQuery";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalRecords = $totalRow['total'];

header('Content-Type: application/json');
echo json_encode(['data' => $data, 'total' => $totalRecords]);
?>
