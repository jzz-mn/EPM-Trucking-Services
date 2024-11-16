<?php
include '../includes/db_connection.php';

header('Content-Type: application/json'); // Ensure the response is JSON
$response = ['success' => false, 'message' => 'An unexpected error occurred'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $UniqueClusterID = $_POST['UniqueClusterID'];

        if (!empty($UniqueClusterID)) {
            $query = "DELETE FROM clusters WHERE UniqueClusterID = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $UniqueClusterID);

            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Cluster deleted successfully'];
            } else {
                $response['message'] = 'Failed to delete the cluster.';
            }

            $stmt->close();
        } else {
            $response['message'] = 'Invalid cluster ID.';
        }
    } else {
        $response['message'] = 'Invalid request method.';
    }
} catch (Exception $e) {
    $response['message'] = 'Exception occurred: ' . $e->getMessage();
}

echo json_encode($response); // Ensure only JSON is returned
exit();
?>
