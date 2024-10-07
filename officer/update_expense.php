<?php
include '../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get the updated data from the form
  $expenseId = $_POST['updateExpenseID'];
  $date = $_POST['updateDate'];
  $salaryAmount = $_POST['updateSalaryAmount'];
  $mobileAmount = $_POST['updateMobileAmount'];
  $otherAmount = $_POST['updateOtherAmount'];
  $totalExpense = $salaryAmount + $mobileAmount + $otherAmount;

  // Update the record in the database
  $sql = "UPDATE expenses 
          SET Date='$date', SalaryAmount='$salaryAmount', 
              MobileAmount='$mobileAmount', OtherAmount='$otherAmount' , TotalExpense ='$totalExpense'
          WHERE ExpenseID='$expenseId'";

  if (mysqli_query($conn, $sql)) {
    // Redirect back to the finance page or refresh the page
    header("Location: finance.php");
    exit();
  } else {
    echo "Error: " . mysqli_error($conn);
  }

  // Close the database connection
  mysqli_close($conn);
}
?>
