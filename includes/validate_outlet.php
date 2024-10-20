<?php
include '../includes/db_connection.php';

header('Content-Type: application/json');

$response = ['exists' => false];

if (isset($_GET['outlet_name'])) {
    $outlet_name = trim($_GET['outlet_name']);

    $query = "SELECT COUNT(*) AS count FROM customers WHERE CustomerName = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $outlet_name);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $count = 0;
            if ($row = $result->fetch_assoc()) {
                $count = $row['count'];
            }
            $stmt->close();
            $response['exists'] = $count > 0;
        } else {
            $response['error'] = 'Query execution failed.';
        }
    } else {
        $response['error'] = 'Query preparation failed.';
    }
} else {
    $response['error'] = 'Outlet name not provided.';
}

echo json_encode($response);
$conn->close();
?>