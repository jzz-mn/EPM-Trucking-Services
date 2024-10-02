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
        <!-- Existing card and breadcrumb code -->
        <div class="card card-body py-3">
            <!-- ... -->
        </div>
        <div class="widget-content searchable-container list">
            <h5 class="border-bottom py-2 px-4 mb-4">Expenses Entry</h5>
            <div class="card w-100 border position-relative overflow-hidden mb-0">
                <div class="card-body p-4">
                    <h4 class="card-title">Expenses Details</h4>
                    <p class="card-subtitle mb-4">Enter expense details for the transaction group.</p>
                    <form id="expenses-form" method="POST" action="transaction_summary.php">
                        <!-- Date Field -->
                        <div class="mb-3">
                            <label for="expenses-date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="expenses-date" name="expenses_date" value="<?php echo $_SESSION['transaction_date']; ?>" required readonly>
                        </div>
                        <!-- Salary Amount -->
                        <div class="mb-3">
                            <label for="expenses-salary" class="form-label">Salary Amount</label>
                            <input type="number" class="form-control" id="expenses-salary" name="expenses_salary" step="0.01" required>
                        </div>
                        <!-- Toll Fee Amount -->
                        <div class="mb-3">
                            <label for="expenses-toll-fee" class="form-label">Toll Fee Amount</label>
                            <input type="number" class="form-control" id="expenses-toll-fee" name="expenses_toll_fee" step="0.01" required>
                        </div>
                        <!-- Mobile Fee Amount -->
                        <div class="mb-3">
                            <label for="expenses-mobile-fee" class="form-label">Mobile Fee Amount</label>
                            <input type="number" class="form-control" id="expenses-mobile-fee" name="expenses_mobile_fee" step="0.01" required>
                        </div>
                        <!-- Other Amount -->
                        <div class="mb-3">
                            <label for="expenses-other-amount" class="form-label">Other Amount</label>
                            <input type="number" class="form-control" id="expenses-other-amount" name="expenses_other_amount" step="0.01">
                        </div>
                        <!-- Total Expense (Calculated) -->
                        <div class="mb-3">
                            <label for="expenses-total" class="form-label">Total Expense</label>
                            <input type="number" class="form-control" id="expenses-total" name="expenses_total" step="0.01" readonly>
                        </div>
                        <!-- Next Button -->
                        <button type="submit" class="btn btn-primary">Next</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript to calculate total expense -->
<script>
    function calculateTotalExpense() {
        const salary = parseFloat(document.getElementById('expenses-salary').value) || 0;
        const tollFee = parseFloat(document.getElementById('expenses-toll-fee').value) || 0;
        const mobileFee = parseFloat(document.getElementById('expenses-mobile-fee').value) || 0;
        const otherAmount = parseFloat(document.getElementById('expenses-other-amount').value) || 0;

        const totalExpense = salary + tollFee + mobileFee + otherAmount;
        document.getElementById('expenses-total').value = totalExpense.toFixed(2);
    }

    document.getElementById('expenses-salary').addEventListener('input', calculateTotalExpense);
    document.getElementById('expenses-toll-fee').addEventListener('input', calculateTotalExpense);
    document.getElementById('expenses-mobile-fee').addEventListener('input', calculateTotalExpense);
    document.getElementById('expenses-other-amount').addEventListener('input', calculateTotalExpense);
</script>

<?php
include '../officer/footer.php';
$conn->close();
?>
