<?php
session_start();
include '../includes/db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $outlet_name = $_POST['outlet_name'] ?? '';
    $fuel_price = floatval($_POST['fuel_price'] ?? 0);
    $tonner = intval($_POST['tonner'] ?? 0);

    if (empty($outlet_name) || $fuel_price <= 0 || $tonner <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        exit();
    }

    // Retrieve ClusterID using the OutletName
    $customer_query = "SELECT CustomerID, ClusterID FROM customers WHERE LOWER(CustomerName) = LOWER(?)";
    $stmt = $conn->prepare($customer_query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => "Database error: " . $conn->error]);
        exit();
    }
    $stmt->bind_param("s", $outlet_name);
    $stmt->execute();
    $customer_result = $stmt->get_result();

    if ($customer_result->num_rows > 0) {
        $customer = $customer_result->fetch_assoc();
        $cluster_id = $customer['ClusterID'];
    } else {
        echo json_encode(['success' => false, 'message' => "The Outlet Name '{$outlet_name}' does not exist in the Customers table."]);
        exit();
    }
    $stmt->close();

    // Fetch RateAmount from clusters based on ClusterID, Tonner, and FuelPrice
    $cluster_query = "SELECT RateAmount FROM clusters 
                      WHERE ClusterID = ? 
                        AND Tonner = ?";
    $stmt = $conn->prepare($cluster_query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => "Database error: " . $conn->error]);
        exit();
    }
    $stmt->bind_param("ii", $cluster_id, $tonner);
    $stmt->execute();
    $cluster_result = $stmt->get_result();

    if ($cluster_result->num_rows > 0) {
        $cluster = $cluster_result->fetch_assoc();
        $rate_amount = $cluster['RateAmount'];
        echo json_encode(['success' => true, 'cluster_id' => $cluster_id, 'rate_amount' => $rate_amount]);
    } else {
        echo json_encode(['success' => false, 'message' => "No RateAmount found for ClusterID '{$cluster_id}' and Tonner '{$tonner}'"]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
$conn->close();
?>