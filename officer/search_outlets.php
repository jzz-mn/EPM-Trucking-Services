<?php
include '../includes/db_connection.php';

header('Content-Type: application/json');

$outlets = [];

if (isset($_GET['query'])) {
    $query = $_GET['query'];
    $stmt = $conn->prepare("SELECT CustomerName FROM customers WHERE CustomerName LIKE ? LIMIT 10");
    $likeQuery = "%" . $query . "%";
    $stmt->bind_param("s", $likeQuery);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $outlets[] = $row;
        }
    } else {
        $outlets['error'] = 'Query execution failed.';
    }
} else {
    $outlets['error'] = 'Query parameter not provided.';
}

echo json_encode($outlets);
$conn->close();
?>