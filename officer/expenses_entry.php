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
// Fetch truck details from session
$transaction_date = isset($_SESSION['transaction_date']) ? $_SESSION['transaction_date'] : '';
$truck_id = isset($_SESSION['truck_id']) ? $_SESSION['truck_id'] : '';

if ($truck_id) {
    // Fetch truck details for display
    $truck_query = "SELECT PlateNo, TruckBrand FROM trucksinfo WHERE TruckID = ?";
    $stmt = $conn->prepare($truck_query);
    $stmt->bind_param("i", $truck_id);
    $stmt->execute();
    $truck_result = $stmt->get_result();
    $truck = $truck_result->fetch_assoc();
    $stmt->close();
} else {
    // Redirect back if truck_id is not set
    header("Location: add_data.php");
    exit();
}
?>
<div class="body-wrapper">
    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-space-between">
                        <h4 class="mb-4 mb-sm-0 card-title">Add Data</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="breadcrumb-item" aria-current="page">
                                    <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                        Expenses Entry
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <h5 class="border-bottom py-2 px-4 mb-4">
            Transactions for Truck:
            <?php echo htmlspecialchars($truck['PlateNo'] . ' - ' . $truck['TruckBrand']); ?>
            Date: <?php echo htmlspecialchars($_SESSION['transaction_date']); ?>
        </h5>
        <!-- Expenses Entry Card -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header">
                <h4 class="card-title">Expenses Entry</h4>
                <p class="card-subtitle">Enter expense details for the transaction group.</p>
            </div>
            <div class="card-body">
                <form id="expenses-form" method="POST" action="transaction_summary.php">
                    <!-- Date Field (Hidden) -->
                    <input type="hidden" name="expenses_date"
                        value="<?php echo htmlspecialchars($_SESSION['transaction_date']); ?>">
                    <div class="row g-3">
                        <div class="col-md-6"">
                            <label for=" expenses-salary" class="form-label">Salary Amount</label>
                            <input type="number" class="form-control" id="expenses-salary" name="expenses_salary"
                                step="0.01" min="0" required>
                        </div>

                        <!-- Mobile Fee Amount -->
                        <div class="col-md-6"">
                            <label for=" expenses-mobile-fee" class="form-label">Mobile Fee Amount</label>
                            <input type="number" class="form-control" id="expenses-mobile-fee"
                                name="expenses_mobile_fee" step="0.01" min="0" required>
                        </div>

                        <!-- Other Amount -->
                        <div class="col-md-6"">
                            <label for=" expenses-other-amount" class="form-label">Other Amount</label>
                            <input type="number" class="form-control" id="expenses-other-amount"
                                name="expenses_other_amount" step="0.01" min="0">
                        </div>

                        <!-- Total Expense (Calculated) -->
                        <div class="col-md-6"">
                            <label for=" expenses-total" class="form-label">Total Expense</label>
                            <input type="number" class="form-control" id="expenses-total" name="expenses_total"
                                step="0.01" readonly>
                        </div>
                    </div>
                    <!-- Salary Amount -->

                    <!-- Buttons Row -->
                    <div class="d-flex justify-content-end mt-4">
                        <a href="fuel_entry.php" class="btn btn-secondary me-1">Back</a>
                        <button type="submit" class="btn btn-primary ms-1" id="next-button" disabled>Next</button>
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