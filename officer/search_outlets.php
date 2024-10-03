<?php
include '../includes/db_connection.php';

if (isset($_GET['query'])) {
    $query = $_GET['query'];
    $stmt = $conn->prepare("SELECT CustomerName FROM customers WHERE CustomerName LIKE ?");
    $likeQuery = "%" . $query . "%";
    $stmt->bind_param("s", $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    $outlets = [];

    while ($row = $result->fetch_assoc()) {
        $outlets[] = $row;
    }

    echo json_encode($outlets);
}
$conn->close();
?>