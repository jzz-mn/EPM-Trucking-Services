<?php
include '../includes/db_connection.php';

if (isset($_GET['dr_no'])) {
    $dr_no = intval($_GET['dr_no']);

    // Prepare and execute the SQL statement safely
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE DRno = ?");
    $stmt->bind_param("i", $dr_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();

    if ($count > 0) {
        echo json_encode(['exists' => true]);
    } else {
        echo json_encode(['exists' => false]);
    }
} else {
    echo json_encode(['exists' => false]);
}

$conn->close();
?>