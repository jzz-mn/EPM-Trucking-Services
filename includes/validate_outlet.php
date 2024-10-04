<?php
// validate_outlet.php
include '../includes/db_connection.php';

header('Content-Type: application/json');

if (isset($_GET['outlet_name'])) {
    $outlet_name = trim($_GET['outlet_name']);

    // Prepare and execute the query to check if the Outlet Name exists
    $query = "SELECT COUNT(*) AS count FROM customers WHERE CustomerName = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $outlet_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = 0;
        if ($row = $result->fetch_assoc()) {
            $count = $row['count'];
        }
        $stmt->close();
        echo json_encode(['exists' => $count > 0]);
    } else {
        // If query preparation fails, return false
        echo json_encode(['exists' => false]);
    }
} else {
    // If 'outlet_name' parameter is not set, return false
    echo json_encode(['exists' => false]);
}

$conn->close();
?>
