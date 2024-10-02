<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php'; // Include the database connection

// Fetch the list of trucks from the database
$truck_query = "SELECT TruckID, PlateNo, TruckBrand FROM trucksinfo";
$truck_result = $conn->query($truck_query);
?>
<div class="body-wrapper">
    <div class="container-fluid">
        <!-- Existing card and breadcrumb code -->
        <div class="card card-body py-3">
            <!-- ... -->
        </div>
        <div class="widget-content searchable-container list">
            <h5 class="border-bottom py-2 px-4 mb-4">Truck Selection</h5>
            <div class="card w-100 border position-relative overflow-hidden mb-0">
                <div class="card-body p-4">
                    <h4 class="card-title">Select Truck and Create Transaction Group</h4>
                    <p class="card-subtitle mb-4">Choose a truck and set the date for your transaction group.</p>
                    <form id="truck-selection-form" method="POST" action="transactions_entry.php">
                        <!-- Date Field -->
                        <div class="mb-3">
                            <label for="transaction-date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="transaction-date" name="transaction_date"
                                value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <!-- Truck Selection -->
                        <div class="mb-3">
                            <label for="truck-select" class="form-label">Select Truck</label>
                            <select class="form-select" id="truck-select" name="truck_id" required>
                                <option value="" disabled selected>Select a truck</option>
                                <?php
                                if ($truck_result->num_rows > 0) {
                                    while ($truck = $truck_result->fetch_assoc()) {
                                        echo '<option value="' . $truck['TruckID'] . '">' . $truck['PlateNo'] . ' - ' . $truck['TruckBrand'] . '</option>';
                                    }
                                } else {
                                    echo '<option value="">No trucks available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <!-- Next Button -->
                        <button type="submit" class="btn btn-primary">Next</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include '../officer/footer.php';
$conn->close(); // Close the database connection
?>