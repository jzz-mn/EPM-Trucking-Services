<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';

// Check if transactions data was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Store transactions data in session
    $_SESSION['transactions'] = json_decode($_POST['transactions_json'], true);
} else {
    // Redirect back if accessed directly
    header("Location: transactions_entry.php");
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
            <h5 class="border-bottom py-2 px-4 mb-4">Fuel Entry</h5>
            <div class="card w-100 border position-relative overflow-hidden mb-0">
                <div class="card-body p-4">
                    <h4 class="card-title">Fuel Details</h4>
                    <p class="card-subtitle mb-4">Enter fuel details for the transaction group.</p>
                    <form id="fuel-form" method="POST" action="expenses_entry.php">
                        <!-- Date Field -->
                        <div class="mb-3">
                            <label for="fuel-date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="fuel-date" name="fuel_date"
                                value="<?php echo $_SESSION['transaction_date']; ?>" required readonly>
                        </div>
                        <!-- Liters -->
                        <div class="mb-3">
                            <label for="fuel-liters" class="form-label">Liters</label>
                            <input type="number" class="form-control" id="fuel-liters" name="fuel_liters" step="0.01"
                                required>
                        </div>
                        <!-- Unit Price -->
                        <div class="mb-3">
                            <label for="fuel-unit-price" class="form-label">Unit Price</label>
                            <input type="number" class="form-control" id="fuel-unit-price" name="fuel_unit_price"
                                step="0.01" required>
                        </div>
                        <!-- Fuel Type -->
                        <div class="mb-3">
                            <label for="fuel-type" class="form-label">Fuel Type</label>
                            <select class="form-select" id="fuel-type" name="fuel_type" required>
                                <option value="" disabled selected>Select fuel type</option>
                                <option value="Diesel">Diesel</option>
                                <option value="Gasoline">Gasoline</option>
                                <!-- Add other options as needed -->
                            </select>
                        </div>
                        <!-- Amount (Calculated) -->
                        <div class="mb-3">
                            <label for="fuel-amount" class="form-label">Amount</label>
                            <input type="number" class="form-control" id="fuel-amount" name="fuel_amount" step="0.01"
                                readonly>
                        </div>
                        <!-- Next Button -->
                        <button type="submit" class="btn btn-primary">Next</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript to calculate amount -->
<script>
    function calculateFuelAmount() {
        const liters = parseFloat(document.getElementById('fuel-liters').value);
        const unitPrice = parseFloat(document.getElementById('fuel-unit-price').value);
        if (!isNaN(liters) && !isNaN(unitPrice)) {
            const amount = liters * unitPrice;
            document.getElementById('fuel-amount').value = amount.toFixed(2);
        } else {
            document.getElementById('fuel-amount').value = '';
        }
    }

    document.getElementById('fuel-liters').addEventListener('input', calculateFuelAmount);
    document.getElementById('fuel-unit-price').addEventListener('input', calculateFuelAmount);
</script>

<?php
include '../officer/footer.php';
$conn->close();
?>