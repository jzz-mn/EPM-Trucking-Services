<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';

// Check if fuel data was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Store fuel data in session
    $_SESSION['fuel_date'] = $_POST['fuel_date'];
    $_SESSION['fuel_liters'] = $_POST['fuel_liters'];
    $_SESSION['fuel_unit_price'] = $_POST['fuel_unit_price'];
    $_SESSION['fuel_type'] = $_POST['fuel_type'];
    $_SESSION['fuel_amount'] = $_POST['fuel_amount'];
} else {
    // Redirect back if accessed directly
    header("Location: fuel_entry.php");
    exit();
}
?>
<div class="body-wrapper">
    <div class="container-fluid">
        <!-- Expenses Entry Card -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header">
                <h4 class="card-title">Expenses Entry</h4>
                <p class="card-subtitle">Enter expense details for the transaction group.</p>
            </div>
            <div class="card-body">
                <!-- Transaction Information -->
                <div class="mb-4">
                    <h5>Transaction Information</h5>
                    <p><strong>Truck ID:</strong> <?php echo htmlspecialchars($_SESSION['truck_id']); ?></p>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($_SESSION['transaction_date']); ?></p>
                </div>

                <!-- Expenses Entry Form -->
                <form id="expenses-form" method="POST" action="transaction_summary.php">
                    <!-- Date Field (Hidden) -->
                    <input type="hidden" name="expenses_date" value="<?php echo htmlspecialchars($_SESSION['transaction_date']); ?>">

                    <!-- Salary Amount -->
                    <div class="mb-3">
                        <label for="expenses-salary" class="form-label">Salary Amount</label>
                        <input type="number" class="form-control" id="expenses-salary" name="expenses_salary" step="0.01" min="0" required>
                    </div>

                    <!-- Mobile Fee Amount -->
                    <div class="mb-3">
                        <label for="expenses-mobile-fee" class="form-label">Mobile Fee Amount</label>
                        <input type="number" class="form-control" id="expenses-mobile-fee" name="expenses_mobile_fee" step="0.01" min="0" required>
                    </div>

                    <!-- Other Amount -->
                    <div class="mb-3">
                        <label for="expenses-other-amount" class="form-label">Other Amount</label>
                        <input type="number" class="form-control" id="expenses-other-amount" name="expenses_other_amount" step="0.01" min="0">
                    </div>

                    <!-- Total Expense (Calculated) -->
                    <div class="mb-3">
                        <label for="expenses-total" class="form-label">Total Expense</label>
                        <input type="number" class="form-control" id="expenses-total" name="expenses_total" step="0.01" readonly>
                    </div>

                    <!-- Buttons Row -->
                    <div class="d-flex justify-content-between">
                        <a href="fuel_entry.php" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary" id="next-button" disabled>Next</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript to calculate total expense -->
<script>
    function calculateTotalExpense() {
        const salary = parseFloat(document.getElementById('expenses-salary').value) || 0;
        const mobileFee = parseFloat(document.getElementById('expenses-mobile-fee').value) || 0;
        const otherAmount = parseFloat(document.getElementById('expenses-other-amount').value) || 0;

        const totalExpense = salary + mobileFee + otherAmount;
        document.getElementById('expenses-total').value = totalExpense.toFixed(2);

        // Enable "Next" button only if total expense is greater than 0
        document.getElementById('next-button').disabled = totalExpense <= 0;
    }

    // Event listeners for real-time calculation
    document.getElementById('expenses-salary').addEventListener('input', calculateTotalExpense);
    document.getElementById('expenses-mobile-fee').addEventListener('input', calculateTotalExpense);
    document.getElementById('expenses-other-amount').addEventListener('input', calculateTotalExpense);
</script>

<?php
include '../officer/footer.php';
$conn->close();
?>
    