<?php
include '../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $response = ['status' => '', 'message' => ''];

    $uniqueClusterID = $_POST['uniqueClusterID'];
    $clusterSelection = $_POST['clusterSelection'];
    $tonner = $_POST['tonner'];
    $kmRadius = $_POST['kmRadius'];
    $fuelPrice = $_POST['fuelPrice'];
    $rateAmount = $_POST['rateAmount'];

    $clusterCategory = '';
    $locationsInCluster = '';

    if ($clusterSelection == 'existing') {
        $clusterCategory = $_POST['existingClusterCategory'];
        $locationsInCluster = $_POST['existingLocationsInCluster'];
        $clusterID = $_POST['existingClusterID'];
    } else {
        $clusterCategory = $_POST['newClusterCategory'];
        $locationsInCluster = $_POST['newLocationsInCluster'];
    }

    // Query to check for duplicates
    $checkQuery = "
        SELECT * FROM clusters 
        WHERE ClusterCategory = '$clusterCategory'
        AND LocationsInCluster = '$locationsInCluster'
        AND Tonner = '$tonner'
        AND KMRADIUS = '$kmRadius'
        AND FuelPrice = '$fuelPrice'
        AND RateAmount = '$rateAmount'
    ";
    $checkResult = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        $response['status'] = 'error';
        $response['message'] = 'The cluster you are trying to add already exists.';
    } else {
        if ($clusterSelection == 'existing') {
            $query = "INSERT INTO clusters (UniqueClusterID, ClusterID, ClusterCategory, LocationsInCluster, Tonner, KMRADIUS, FuelPrice, RateAmount) 
                      VALUES ('$uniqueClusterID', '$clusterID', '$clusterCategory', '$locationsInCluster', '$tonner', '$kmRadius', '$fuelPrice', '$rateAmount')";

            if (mysqli_query($conn, $query)) {
                $response['status'] = 'success';
                $response['message'] = 'Cluster updated successfully.';
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Error updating cluster: ' . mysqli_error($conn);
            }
        } else {
            $result = mysqli_query($conn, "SELECT MAX(ClusterID) AS maxID FROM clusters");
            $row = mysqli_fetch_assoc($result);
            $newClusterID = $row['maxID'] + 1;

            $query = "INSERT INTO clusters (UniqueClusterID, ClusterID, ClusterCategory, LocationsInCluster, Tonner, KMRADIUS, FuelPrice, RateAmount) 
                      VALUES ('$uniqueClusterID', '$newClusterID', '$clusterCategory', '$locationsInCluster', '$tonner', '$kmRadius', '$fuelPrice', '$rateAmount')";

            if (mysqli_query($conn, $query)) {
                $response['status'] = 'success';
                $response['message'] = 'New cluster added successfully.';
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Error adding new cluster: ' . mysqli_error($conn);
            }
        }
    }

    // Send response as JSON
    echo json_encode($response);
    mysqli_close($conn);
    exit;
}
?>
