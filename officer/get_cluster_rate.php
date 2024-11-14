<?php
include '../includes/db_connection.php';

header('Content-Type: application/json');

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $outlet_name = trim($_POST['outlet_name'] ?? '');
    $fuel_price = floatval($_POST['fuel_price'] ?? 0);
    $tonner = floatval($_POST['tonner'] ?? 0);

    if (empty($outlet_name) || $fuel_price <= 0 || $tonner <= 0) {
        $response['success'] = false;
        $response['message'] = 'Outlet Name, Fuel Price, and Tonner are required and must be positive.';
        echo json_encode($response);
        exit();
    }

    // Retrieve ClusterID using the OutletName
    $customer_query = "SELECT ClusterID FROM customers WHERE LOWER(CustomerName) = LOWER(?) LIMIT 1";
    $stmt = $conn->prepare($customer_query);
    $stmt->bind_param("s", $outlet_name);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            $cluster_id = $customer['ClusterID'];
        } else {
            $response['success'] = false;
            $response['message'] = "Outlet Name '{$outlet_name}' not found.";
            echo json_encode($response);
            exit();
        }
    } else {
        $response['success'] = false;
        $response['message'] = "Failed to execute customer query: " . $stmt->error;
        echo json_encode($response);
        exit();
    }
    $stmt->close();

    // Fetch RateAmount from clusters based on ClusterID, FuelPrice, and Tonner
    $cluster_query = "SELECT RateAmount FROM clusters 
                      WHERE ClusterID = ? 
                        AND FuelPrice = ? 
                        AND Tonner = ?";
    $stmt = $conn->prepare($cluster_query);
    $stmt->bind_param("idd", $cluster_id, $fuel_price, $tonner);
    if ($stmt->execute()) {
        $cluster_result = $stmt->get_result();
        if ($cluster_result->num_rows > 0) {
            $cluster = $cluster_result->fetch_assoc();
            $rate_amount = $cluster['RateAmount'];
            $response['success'] = true;
            $response['rate_amount'] = $rate_amount;
        } else {
            $response['success'] = false;
            $response['message'] = "No RateAmount found for ClusterID '{$cluster_id}', FuelPrice '{$fuel_price}', and Tonner '{$tonner}'.";
        }
    } else {
        $response['success'] = false;
        $response['message'] = "Failed to execute cluster query: " . $stmt->error;
    }
    $stmt->close();
} else {
    $response['success'] = false;
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
$conn->close();
?>
