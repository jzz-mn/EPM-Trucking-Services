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
        <!-- Header Card and Breadcrumb -->
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
                                        Fuel Entry
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
        <!-- Fuel Entry Card -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header">
                <h4 class="card-title ">Fuel Entry</h4>
                <p class="card-subtitle">Enter fuel details for the transaction group below.</p>
            </div>
            <div class="card-body">
                <!-- Fuel Entry Form -->
                <form id="fuel-form" method="POST" action="expenses_entry.php">
                    <div class="row g-3">
                        <!-- Hidden Date Field -->
                        <div class="col-md-4 d-none">
                            <label for="fuel-date" class="form-label">Date</label>
                            <input type="hidden" class="form-control" id="fuel-date" name="fuel_date"
                                value="<?php echo htmlspecialchars($transaction_date); ?>" required readonly>
                        </div>

                        <!-- Liters -->
                        <div class="col-md-6">
                            <label for="fuel-liters" class="form-label">Liters</label>
                            <input type="number" class="form-control" id="fuel-liters" name="fuel_liters"
                                placeholder="Enter liters" step="0.01" required>
                        </div>
                        <!-- Unit Price -->
                        <div class="col-md-6">
                            <label for="fuel-unit-price" class="form-label">Unit Price</label>
                            <input type="number" class="form-control" id="fuel-unit-price" name="fuel_unit_price"
                                placeholder="Enter price per liter" step="0.01" required>
                        </div>
                    </div>

                    <div class="row g-3 mt-3">
                        <!-- Fuel Type -->
                        <div class="col-md-6">
                            <label for="fuel-type" class="form-label">Fuel Type</label>
                            <select class="form-select" id="fuel-type" name="fuel_type" required>
                                <option value="" disabled selected>Select fuel type</option>
                                <option value="Diesel">Diesel</option>
                                <option value="Gasoline">Gasoline</option>
                            </select>
                        </div>
                        <!-- Amount (Calculated) -->
                        <div class="col-md-6">
                            <label for="fuel-amount" class="form-label">Amount</label>
                            <input type="number" class="form-control" id="fuel-amount" name="fuel_amount"
                                placeholder="Calculated amount" step="0.01" readonly>
                        </div>
                    </div>

                    <!-- Buttons Row -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="transactions_entry.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary" id="next-button" disabled>
                            Next <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </form>
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
            document.getElementById('next-button').disabled = false;
        } else {
            document.getElementById('fuel-amount').value = '';
            document.getElementById('next-button').disabled = true;
        }
    }

    document.getElementById('fuel-liters').addEventListener('input', calculateFuelAmount);
    document.getElementById('fuel-unit-price').addEventListener('input', calculateFuelAmount);
</script>

<?php
include '../officer/footer.php';
$conn->close();
?>