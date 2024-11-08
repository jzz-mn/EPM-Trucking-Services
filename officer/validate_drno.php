<?php
session_start();
include '../includes/db_connection.php';

header('Content-Type: application/json');

// Ensure the user is logged in
if (!isset($_SESSION['UserID'])) {
    echo json_encode(['success' => false, 'valid' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $DRno = trim($_POST['DRno'] ?? '');

    if (empty($DRno)) {
        echo json_encode(['success' => false, 'valid' => false, 'message' => 'DR No cannot be empty.']);
        exit;
    }

    // Check if DRno exists
    $drnoQuery = "SELECT TransactionID FROM transactions WHERE DRno = ?";
    $drnoStmt = $conn->prepare($drnoQuery);
    if (!$drnoStmt) {
        echo json_encode(['success' => false, 'valid' => false, 'message' => 'Failed to prepare DR No validation query.']);
        exit;
    }
    $drnoStmt->bind_param("s", $DRno);
    $drnoStmt->execute();
    $drnoResult = $drnoStmt->get_result();

    if ($drnoResult->num_rows > 0) {
        echo json_encode(['success' => true, 'valid' => false, 'message' => 'DR No already exists.']);
    } else {
        echo json_encode(['success' => true, 'valid' => true]);
    }
    $drnoStmt->close();
    exit;
}
?>