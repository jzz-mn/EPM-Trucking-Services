<?php
include('../includes/db_connection.php');

$field = isset($_GET['field']) ? $_GET['field'] : '';
$value = isset($_GET['value']) ? trim($_GET['value']) : '';

if ($field && $value) {
    $sql = "SELECT COUNT(*) AS count FROM useraccounts WHERE $field = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $value);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();

    echo json_encode(['exists' => $count > 0]);
} else {
    echo json_encode(['exists' => false]);
}
?>
