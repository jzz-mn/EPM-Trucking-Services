<?php
include '../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get the updated data from the form
  $expenseId = $_POST['updateExpenseID'];
  $date = $_POST['updateDate'];
  $tollFee = $_POST['updateTollFee'];
  $rateAmount = $_POST['updateRateAmount'];
  $salaryAmount = $_POST['updateSalaryAmount'];
  $gasAmount = $_POST['updateGasAmount'];
  $allowanceAmount = $_POST['updateAllowanceAmount'];
  $extraMealAmount = $_POST['updateExtraMealAmount'];
  $mobile = $_POST['updateMobile'];
  $totalAmount = $tollFee + $rateAmount + $salaryAmount + $gasAmount + $allowanceAmount + $extraMealAmount + $mobile;

  // Update the record in the database
  $sql = "UPDATE expenses 
          SET Date='$date', TollFee='$tollFee', RateAmount='$rateAmount', SalaryAmount='$salaryAmount', 
              GasAmount='$gasAmount', AllowanceAmount='$allowanceAmount', ExtraMealAmount='$extraMealAmount', 
              Mobile='$mobile', TotalAmount='$totalAmount' 
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
