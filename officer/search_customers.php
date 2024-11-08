<?php
session_start();
include '../includes/db_connection.php';

header('Content-Type: application/json');

// Ensure the user is logged in
if (!isset($_SESSION['UserID'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = $_GET['query'] ?? '';
    $query = '%' . strtolower(trim($query)) . '%';

    $customerQuery = "SELECT CustomerName FROM customers WHERE LOWER(CustomerName) LIKE ? LIMIT 10";
    $customerStmt = $conn->prepare($customerQuery);
    if (!$customerStmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare customer query.']);
        exit;
    }
    $customerStmt->bind_param("s", $query);
    $customerStmt->execute();
    $customerResult = $customerStmt->get_result();

    $customers = [];
    while ($row = $customerResult->fetch_assoc()) {
        $customers[] = $row;
    }
    $customerStmt->close();

    echo json_encode(['success' => true, 'customers' => $customers]);
    exit;
}
?>