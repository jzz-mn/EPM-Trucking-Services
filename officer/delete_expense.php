<?php
include '../includes/db_connection.php';
header('Content-Type: application/json');

// Initialize the response array
$response = array('success' => false, 'message' => 'Unknown error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expenseID = $_POST['id'];

    if (!empty($expenseID)) {
        // Prepare the DELETE SQL query
        $stmt = $conn->prepare('DELETE FROM expenses WHERE ExpenseID = ?');
        $stmt->bind_param('i', $expenseID);

        if ($stmt->execute()) {
            // If delete is successful
            $response['success'] = true;
            $response['message'] = 'Expense deleted successfully.';
        } else {
            // If there's an error during deletion
            $response['message'] = 'Failed to delete expense.';
        }

        $stmt->close();
    } else {
        $response['message'] = 'Invalid expense ID.';
    }
}

$conn->close();
echo json_encode($response);
?>
