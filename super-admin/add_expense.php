<?php
// Include the database connection
include '../includes/db_connection.php';

// Get the last ExpenseID
$query = "SELECT ExpenseID FROM expenses ORDER BY ExpenseID DESC LIMIT 1";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$nextExpenseId = isset($row['ExpenseID']) ? $row['ExpenseID'] + 1 : 1;
?>

<script>
    document.getElementById("expenseId").value = "<?php echo $nextExpenseId; ?>";
</script>
<?php
include('db_connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $expenseId = $_POST['expenseId'];
    $expenseDate = $_POST['expenseDate'];
    $tollFee = $_POST['tollFee'];
    $rateAmount = $_POST['rateAmount'];
    $salaryAmount = $_POST['salaryAmount'];
    $gasAmount = $_POST['gasAmount'];
    $allowanceAmount = $_POST['allowanceAmount'];
    $extraMealAmount = $_POST['extraMealAmount'];
    $mobileFee = $_POST['mobileFee'];
    $totalAmount = $_POST['totalAmount'];

    // Insert data into the expenses table
    $sql = "INSERT INTO expenses (ExpenseID, Date, TollFee, RateAmount, TotalAmount, SalaryAmount, GasAmount, AllowanceAmount, ExtraMealAmount, Mobile)
          VALUES ('$expenseId', '$expenseDate', '$tollFee', '$rateAmount', '$totalAmount', '$salaryAmount', '$gasAmount', '$allowanceAmount', '$extraMealAmount', '$mobileFee')";

    if (mysqli_query($conn, $sql)) {
        // Redirect to finance.php upon successful insertion
        header("Location: finance.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

    // Close the database connection
    mysqli_close($conn);
}
?>