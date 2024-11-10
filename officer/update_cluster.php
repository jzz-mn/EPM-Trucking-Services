<?php
include '../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uniqueClusterId = $_POST['uniqueClusterId'];
    $clusterCategory = $_POST['clusterCategory'];
    $locationsInCluster = $_POST['locationsInCluster'];
    $tonner = $_POST['tonner'];
    $kmRadius = $_POST['kmRadius'];
    $fuelPrice = $_POST['fuelPrice'];
    $rateAmount = $_POST['rateAmount'];

    $query = "UPDATE clusters SET ClusterCategory = ?, LocationsInCluster = ?, Tonner = ?, KMRADIUS = ?, FuelPrice = ?, RateAmount = ? WHERE UniqueClusterID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssiddii", $clusterCategory, $locationsInCluster, $tonner, $kmRadius, $fuelPrice, $rateAmount, $uniqueClusterId);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Cluster updated successfully.";
        header("Location: trucks.php");
    } else {
        $_SESSION['error'] = "Failed to update cluster.";
        header("Location: trucks.php");
    }

    $stmt->close();
}
$conn->close();
?>
