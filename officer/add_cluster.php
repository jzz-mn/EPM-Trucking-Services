<?php
include '../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uniqueClusterID = $_POST['uniqueClusterID'];
    $clusterSelection = $_POST['clusterSelection'];
    $tonner = $_POST['tonner'];
    $kmRadius = $_POST['kmRadius'];
    $fuelPrice = $_POST['fuelPrice'];
    $rateAmount = $_POST['rateAmount'];

    if ($clusterSelection == 'existing') {
        // For existing clusters, get the selected ClusterID and ClusterCategory, LocationsInCluster
        $clusterID = $_POST['existingClusterID'];
        $clusterCategory = $_POST['existingClusterCategory'];
        $locationsInCluster = $_POST['existingLocationsInCluster'];

        // Insert the cluster data
        $query = "INSERT INTO clusters (UniqueClusterID, ClusterID, Tonner, KMRADIUS, FuelPrice, RateAmount) 
                  VALUES ('$uniqueClusterID', '$clusterID', '$tonner', '$kmRadius', '$fuelPrice', '$rateAmount')";

        // Execute the insert query
        if (mysqli_query($conn, $query)) {
            // Update the ClusterCategory and LocationsInCluster fields for the existing cluster
            $updateQuery = "UPDATE clusters SET ClusterCategory = '$clusterCategory', LocationsInCluster = '$locationsInCluster'
                            WHERE ClusterID = '$clusterID'";
            mysqli_query($conn, $updateQuery);
            $_SESSION['message'] = "Cluster updated successfully.";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        // For new clusters, generate a new ClusterID and include ClusterCategory and LocationsInCluster
        $newClusterCategory = $_POST['newClusterCategory'];
        $newLocationsInCluster = $_POST['newLocationsInCluster'];

        // Generate the next ClusterID by getting the last ClusterID and incrementing it
        $result = mysqli_query($conn, "SELECT MAX(ClusterID) AS maxID FROM clusters");
        $row = mysqli_fetch_assoc($result);
        $newClusterID = $row['maxID'] + 1;

        // Insert a new cluster with ClusterCategory and LocationsInCluster
        $query = "INSERT INTO clusters (UniqueClusterID, ClusterID, ClusterCategory, LocationsInCluster, Tonner, KMRADIUS, FuelPrice, RateAmount) 
                  VALUES ('$uniqueClusterID', '$newClusterID', '$newClusterCategory', '$newLocationsInCluster', '$tonner', '$kmRadius', '$fuelPrice', '$rateAmount')";

        // Execute the insert query
        if (mysqli_query($conn, $query)) {
            $_SESSION['message'] = "New cluster added successfully.";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }

    // Redirect after the operation
    header("Location: trucks.php");
    mysqli_close($conn);
}
