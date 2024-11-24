<?php
include '../includes/db_connection.php';

header('Content-Type: application/json');

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cluster_id = intval($_POST['ClusterID'] ?? 0);
    $fuel_price = floatval($_POST['FuelPrice'] ?? 0);
    $tonner = intval($_POST['Tonner'] ?? 0);

    if ($cluster_id <= 0 || $fuel_price <= 0 || $tonner <= 0) {
        $response['success'] = false;
        $response['message'] = 'Invalid parameters provided.';
        echo json_encode($response);
        exit;
    }

    // Fetch RateAmount from clusters table
    $rate_query = "SELECT RateAmount FROM clusters WHERE ClusterID = ? AND FuelPrice = ? AND Tonner = ?";
    $stmt = $conn->prepare($rate_query);
    if ($stmt) {
        $stmt->bind_param("idd", $cluster_id, $fuel_price, $tonner);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $response['success'] = true;
            $response['RateAmount'] = $row['RateAmount'];
        } else {
            $response['success'] = false;
            $response['message'] = 'No RateAmount found for the provided parameters.';
        }
        $stmt->close();
    } else {
        $response['success'] = false;
        $response['message'] = 'Failed to prepare RateAmount query.';
    }
} else {
    $response['success'] = false;
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
$conn->close();
?>
