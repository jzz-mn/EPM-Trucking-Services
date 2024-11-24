<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';

function round_up_kgs($kgs)
{
    if ($kgs <= 0) {
        return 0; // Handle as per your business logic
    }
    if ($kgs <= 1199) {
        return 1000;
    }
    if ($kgs <= 4199) {
        $rounded_kgs = ceil($kgs / 1000) * 1000;
        return $rounded_kgs > 4000 ? 4000 : $rounded_kgs; // Cap at 4000 if exceeded
    }
    return 4000; // Cap at 4000
}

// Initialize variables
$errors = [];
$success = false;

// Fetch necessary data for dropdowns
$truck_query = "SELECT TruckID, PlateNo, TruckBrand, TruckStatus 
              FROM trucksinfo 
              WHERE TruckStatus = 'Activated'";
$truck_result = $conn->query($truck_query);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle Transactions Data
    if (isset($_POST['transactions_json'])) {
        $_SESSION['transactions'] = json_decode($_POST['transactions_json'], true);
        if (empty($_SESSION['transactions'])) {
            $errors[] = "Please add at least one transaction.";
        }
    }

    // Handle Fuel Data
    if (isset($_POST['fuel_liters'])) {
        $_SESSION['fuel_data'] = [
            'liters' => $_POST['fuel_liters'],
            'unit_price' => $_POST['fuel_unit_price'],
            'fuel_type' => $_POST['fuel_type'],
            'amount' => $_POST['fuel_amount'],
        ];
    }

    // Handle Expenses Data
    if (isset($_POST['expenses_salary'])) {
        // Ensure fuel_data exists before adding fuel_amount to expenses_total
        $fuelAmount = isset($_SESSION['fuel_data']['amount']) ? floatval($_SESSION['fuel_data']['amount']) : 0;
        $expensesTotal = isset($_POST['expenses_total']) ? floatval($_POST['expenses_total']) : 0;

        // Sum expenses_total with fuel_amount
        $combinedExpensesTotal = $expensesTotal + $fuelAmount;

        $_SESSION['expenses_data'] = [
            'salary' => $_POST['expenses_salary'],
            'mobile_fee' => $_POST['expenses_mobile_fee'],
            'other_amount' => $_POST['expenses_other_amount'],
            'total' => $combinedExpensesTotal,
        ];
    }

    // Handle Toll Fee Amount
    if (isset($_POST['toll_fee_amount'])) {
        $_SESSION['toll_fee_amount'] = $_POST['toll_fee_amount'];
    }

    // Handle Final Submission
    if (isset($_POST['final_submit'])) {
        // Validate that all necessary data is present
        if (
            empty($_POST['transaction_date']) ||
            empty($_POST['truck_id']) ||
            empty($_SESSION['transactions']) ||
            empty($_SESSION['fuel_data']) ||
            empty($_SESSION['expenses_data']) ||
            !isset($_SESSION['toll_fee_amount']) // Changed from empty() to isset()
        ) {
            $errors[] = "Please complete all sections of the form.";
        } else {
            // Save transaction_date and truck_id in session
            $_SESSION['transaction_date'] = $_POST['transaction_date'];
            $_SESSION['truck_id'] = $_POST['truck_id'];

            // Begin database transaction
            $conn->begin_transaction();
            try {
                // Insert data into transaction_groups table
                $stmt = $conn->prepare("INSERT INTO transaction_groups (TruckID, TransactionDate, TollFeeAmount) VALUES (?, ?, ?)");
                $stmt->bind_param("isd", $_SESSION['truck_id'], $_SESSION['transaction_date'], $_SESSION['toll_fee_amount']);
                $stmt->execute();
                $transaction_group_id = $stmt->insert_id;
                $stmt->close();

                // Insert transactions
                $stmt = $conn->prepare("INSERT INTO transactions (TransactionGroupID, DRno, OutletName, Quantity, KGs) VALUES (?, ?, ?, ?, ?)");
                foreach ($_SESSION['transactions'] as $transaction) {
                    $stmt->bind_param(
                        "iissd",
                        $transaction_group_id,
                        $transaction['drNo'],
                        $transaction['outletName'],
                        $transaction['quantity'],
                        $transaction['kgs']
                    );
                    $stmt->execute();
                }
                $stmt->close();

                // Insert fuel data
                $stmt = $conn->prepare("INSERT INTO fuel_entries (TransactionGroupID, Liters, UnitPrice, FuelType, Amount) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    "iddsd",
                    $transaction_group_id,
                    $_SESSION['fuel_data']['liters'],
                    $_SESSION['fuel_data']['unit_price'],
                    $_SESSION['fuel_data']['fuel_type'],
                    $_SESSION['fuel_data']['amount']
                );
                $stmt->execute();
                $stmt->close();

                // Insert expenses data (already includes fuel_amount)
                $stmt = $conn->prepare("INSERT INTO expenses_entries (TransactionGroupID, SalaryAmount, MobileFeeAmount, OtherAmount, TotalExpense) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    "idddd",
                    $transaction_group_id,
                    $_SESSION['expenses_data']['salary'],
                    $_SESSION['expenses_data']['mobile_fee'],
                    $_SESSION['expenses_data']['other_amount'],
                    $_SESSION['expenses_data']['total']
                );
                $stmt->execute();
                $stmt->close();

                // Commit transaction
                $conn->commit();
                $success = true;

                // Clear session data
                session_unset();
                session_destroy();
            } catch (Exception $e) {
                // Rollback transaction if there is an error
                $conn->rollback();
                $errors[] = "Failed to save data: " . $e->getMessage();
            }
        }
    }
}

// Fetch truck details for display
if (isset($_SESSION['truck_id']) && !isset($truck_display)) {
    $truck_query = "SELECT PlateNo, TruckBrand FROM trucksinfo WHERE TruckID = ?";
    $stmt = $conn->prepare($truck_query);
    $stmt->bind_param("i", $_SESSION['truck_id']);
    $stmt->execute();
    $truck_result_display = $stmt->get_result();
    $truck_display = $truck_result_display->fetch_assoc();
    $stmt->close();
}

?>
<!-- Custom Styles -->
<style>
    /* Optional: Adjust spacing or alignment if necessary */
    .error-message {
        font-size: 0.875em;
        /* Equivalent to Bootstrap's 'small' class */
        color: #dc3545;
        /* Bootstrap's 'text-danger' color */
        font-style: italic;
        /* Bootstrap's 'fst-italic' class */
        display: none;
        /* Initially hide the error message */
    }
</style>

<div class="body-wrapper">
    <div class="container-fluid">
        <!-- Header and Breadcrumb -->
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
                                        Add Data
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Display Errors -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger mt-3">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Display Success Message -->
        <?php if ($success): ?>
            <div class="alert alert-success mt-3">
                Data has been successfully saved.
            </div>
        <?php endif; ?>

        <!-- Combined Form for Steps 1 to 4 -->
        <form id="add-data-form" method="POST" action="save_transaction_group.php">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <!-- Step 1: Truck Selection -->
                    <section id="truck-selection" class="mb-5">
                        <h5 class="border-bottom py-2 mb-4">Truck Selection</h5>
                        <div class="row g-3">
                            <!-- Date Field -->
                            <div class="col-md-6">
                                <label for="transaction-date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="transaction-date" name="transaction_date"
                                    value="<?php echo $_SESSION['transaction_date'] ?? date('Y-m-d'); ?>" required>
                            </div>
                            <!-- Truck Selection -->
                            <div class="col-md-6">
                                <label for="truck-select" class="form-label">Select Truck</label>
                                <select class="form-select" id="truck-select" name="truck_id" required>
                                    <option value="" disabled <?php echo isset($_SESSION['truck_id']) ? '' : 'selected'; ?>>Select a truck</option>
                                    <?php
                                    if ($truck_result->num_rows > 0) {
                                        while ($truck = $truck_result->fetch_assoc()) {
                                            $selected = (isset($_SESSION['truck_id']) && $_SESSION['truck_id'] == $truck['TruckID']) ? 'selected' : '';
                                            echo '<option value="' . $truck['TruckID'] . '" ' . $selected . '>' . $truck['PlateNo'] . ' - ' . $truck['TruckBrand'] . '</option>';
                                        }
                                    } else {
                                        echo '<option value="">No trucks available</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- Step 2: Transactions Entry -->
                    <section id="transactions-entry" class="mb-5">
                        <h5 class="border-bottom py-2 mb-4">Transactions Entry</h5>
                        <div class="card-body p-0">
                            <div id="add-transaction-form">
                                <div class="row g-3 align-items-end">
                                    <!-- Outlet Name with Autocomplete and Validation -->
                                    <div class="col-md-4 position-relative">
                                        <div class="d-flex align-items-center mb-2">
                                            <label for="input-outlet-name" class="form-label me-2">Outlet Name</label>
                                            <span class="text-danger small fst-italic d-none" id="outlet-error">
                                                Outlet Name does not exist.
                                            </span>
                                        </div>
                                        <input type="text" class="form-control" id="input-outlet-name"
                                            placeholder="Search Outlet Name" autocomplete="off">
                                        <div id="outlet-suggestions" class="list-group position-absolute w-100"
                                            style="z-index: 1000; background-color: white;"></div>
                                    </div>
                                    <!-- DR No -->
                                    <div class="col-md-2">
                                        <div class="d-flex align-items-center mb-2">
                                            <label for="input-dr-no" class="form-label me-2">DR No</label>
                                            <span class="text-danger small fst-italic d-none" id="dr-no-error">
                                                DR No already exists.
                                            </span>
                                        </div>
                                        <input type="number" class="form-control" id="input-dr-no" placeholder="DR No"
                                            step="1" min="1">
                                    </div>
                                    <!-- Quantity -->
                                    <div class="col-md-2">
                                        <label for="input-quantity" class="form-label">Quantity</label>
                                        <input type="number" class="form-control" id="input-quantity"
                                            placeholder="Quantity" step="0.01" min="0">
                                    </div>
                                    <!-- KGs -->
                                    <div class="col-md-2">
                                        <label for="input-kgs" class="form-label">KGs</label>
                                        <input type="number" class="form-control" id="input-kgs" placeholder="KGs"
                                            step="0.01" min="0">
                                    </div>
                                    <!-- Add Transaction Button -->
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-primary w-100" id="add-transaction-btn">Add
                                            Transaction</button>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body px-0">
                                <div class="table-responsive">
                                    <table
                                        class="table table-striped table-bordered text-nowrap align-middle text-center"
                                        id="transactions-table">
                                        <thead>
                                            <tr>
                                                <th>Outlet Name</th>
                                                <th>DR No</th>
                                                <th>Quantity</th>
                                                <th>KGs</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="transactions-body">
                                            <!-- Transactions will be appended here -->
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Hidden input to store transactions data -->
                                <input type="hidden" name="transactions_json" id="transactions-json">
                            </div>
                        </div>
                    </section>

                    <!-- Step 3: Fuel Entry -->
                    <section id="fuel-entry" class="mb-5">
                        <h5 class="border-bottom py-2 mb-4">Fuel Entry</h5>
                        <div class="row g-3">
                            <!-- Liters -->
                            <div class="col-md-6">
                                <label for="fuel-liters" class="form-label">Liters</label>
                                <input type="number" class="form-control" id="fuel-liters" name="fuel_liters"
                                    placeholder="Enter liters" step="0.01" min="0">
                            </div>
                            <!-- Unit Price -->
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <label for="fuel-unit-price" class="form-label mb-0 me-2">Unit Price</label>
                                    <span class="text-danger small fst-italic d-none " id="fuel-unit-price-error">
                                        Enter a value between 50 and 100.
                                    </span>
                                </div>
                                <input type="number" class="form-control" id="fuel-unit-price" name="fuel_unit_price"
                                    placeholder="Enter price per liter" step="0.01" min="50" max="100" required>
                            </div>

                        </div>

                        <div class="row g-3 mt-3">
                            <!-- Fuel Type -->
                            <div class="col-md-6">
                                <label for="fuel-type" class="form-label">Fuel Type</label>
                                <select class="form-select" id="fuel-type" name="fuel_type">
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
                    </section>

                    <!-- Step 4: Expenses Entry -->
                    <section id="expenses-entry" class="mb-5">
                        <h5 class="border-bottom py-2 mb-4">Expenses Entry</h5>
                        <div class="row g-3">
                            <!-- Salary Amount -->
                            <div class="col-md-6">
                                <label for="expenses-salary" class="form-label">Salary Amount</label>
                                <input type="number" class="form-control" id="expenses-salary" name="expenses_salary"
                                    placeholder="Enter salary amount" step="0.01" min="0">
                            </div>

                            <!-- Mobile Fee Amount -->
                            <div class="col-md-6">
                                <label for="expenses-mobile-fee" class="form-label">Mobile Fee Amount</label>
                                <input type="number" class="form-control" id="expenses-mobile-fee"
                                    name="expenses_mobile_fee" placeholder="Enter mobile fee amount" step="0.01"
                                    min="0">
                            </div>

                            <!-- Other Amount -->
                            <div class="col-md-6">
                                <label for="expenses-other-amount" class="form-label">Other Amount</label>
                                <input type="number" class="form-control" id="expenses-other-amount"
                                    name="expenses_other_amount" placeholder="Enter other amount" step="0.01" min="0">
                            </div>

                            <!-- Total Expense (Calculated) -->
                            <div class="col-md-6">
                                <label for="expenses-total" class="form-label">Total Expenses (Including Fuel)</label>
                                <input type="number" class="form-control" id="expenses-total" name="expenses_total"
                                    placeholder="Calculated total expenses" step="0.01" readonly>
                            </div>
                        </div>
                    </section>

                    <!-- Modified Step 5: Toll Fee Entry -->
                    <section id="toll-fee-entry" class="mb-0">
                        <h5 class="border-bottom py-2 mb-4">Toll Fee Amount</h5>
                        <div class="row g-3 align-items-end">
                            <!-- Individual Toll Fee Input -->
                            <div class="col-md-10">
                                <label for="toll-fee-input" class="form-label">Toll Fee Amount</label>
                                <input type="number" class="form-control" id="toll-fee-input"
                                    placeholder="Enter toll fee" step="0.01" min="0">
                            </div>
                            <!-- Add Toll Button -->
                            <div class="col-md-2">
                                <button type="button" id="add-toll-btn" class="btn w-100 btn-primary">Add Toll</button>
                            </div>
                            <!-- Toll Fees Table -->
                            <div class="col-md-12">
                                <table class="table table-striped table-bordered text-nowrap align-middle text-center"
                                    id="toll-fees-table">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Toll Fee Amount</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Toll fee entries will be appended here -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="1"><strong>Total</strong></td>
                                            <td><strong id="toll-fees-total">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <!-- Hidden input to store the total toll fee amount -->
                        <input type="hidden" name="toll_fee_amount" id="toll_fee_amount" value="0">
                    </section>
                </div>
                <!-- Final Submission Button -->
                <div class="d-flex justify-content-end">
                    <button type="button" id="review-submit-btn" class="w-25 btn m-4 btn-success">Submit Data</button>
                </div>
            </div>

            <!-- Hidden Submit Button -->
            <button type="submit" id="final-submit-btn" name="final_submit" style="display: none;"></button>
        </form>
    </div>
</div>

<!-- Transaction Summary Modal -->
<div class="modal fade" id="transactionSummaryModal" tabindex="-1" aria-labelledby="transactionSummaryModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">Transaction Summary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="summary-content-modal">
                <!-- Summary will be generated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" id="confirm-submit-btn" class="btn btn-primary">Confirm Submission</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Review Again</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-labelledby="editTransactionModalTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Edit Transaction Form -->
                <form id="edit-transaction-form">
                    <!-- DR No -->
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <label for="edit-dr-no" class="form-label me-2">DR No</label>
                            <span class="text-danger small fst-italic d-none" id="edit-dr-no-error">
                                DR No already exists.
                            </span>
                        </div>
                        <input type="number" class="form-control" id="edit-dr-no" placeholder="DR No" step="1" min="1"
                            required>
                    </div>
                    <!-- Outlet Name with Autocomplete and Validation -->
                    <div class="mb-3 position-relative">
                        <div class="d-flex align-items-center mb-2">
                            <label for="edit-outlet-name" class="form-label me-2">Outlet Name</label>
                            <span class="text-danger small fst-italic d-none" id="edit-outlet-error">
                                Outlet Name does not exist.
                            </span>
                        </div>
                        <input type="text" class="form-control" id="edit-outlet-name" placeholder="Search Outlet Name"
                            autocomplete="off" required>
                        <div id="edit-outlet-suggestions" class="list-group position-absolute w-100"
                            style="z-index: 1000; background-color: white;"></div>
                    </div>
                    <!-- Quantity -->
                    <div class="mb-3">
                        <label for="edit-quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="edit-quantity" placeholder="Quantity" step="0.01"
                            required>
                    </div>
                    <!-- KGs -->
                    <div class="mb-3">
                        <label for="edit-kgs" class="form-label">KGs</label>
                        <input type="number" class="form-control" id="edit-kgs" placeholder="KGs" step="0.01" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="save-edit-btn">Save changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Include necessary scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Include Bootstrap JS for modal functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>


<script>
    $(document).ready(function () {
        // Variables to store data
        let transactions = <?php echo json_encode($_SESSION['transactions'] ?? []); ?>;
        let editingIndex = -1;

        // Toll Fees Array to store individual toll fee amounts
        let tollFees = [];

        // Function to sanitize HTML to prevent XSS
        function sanitizeHTML(str) {
            const temp = document.createElement('div');
            temp.textContent = str;
            return temp.innerHTML;
        }

        // Function to update transactions table
        function updateTransactionsTable() {
            const tbody = $('#transactions-body');
            tbody.empty();

            // Render transactions
            transactions.forEach((txn, index) => {
                const row = `
                    <tr>
                        <td>${sanitizeHTML(txn.outletName)}</td>
                        <td>${txn.drNo}</td>
                        <td>${txn.quantity}</td>
                        <td>${txn.kgs}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary me-1" onclick="editTransaction(${index})">Edit</button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteTransaction(${index})">Delete</button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });

            // Compute totals
            let totalTransactions = transactions.length;
            let totalQuantity = transactions.reduce((sum, txn) => sum + parseFloat(txn.quantity), 0);
            let totalKGs = transactions.reduce((sum, txn) => sum + parseFloat(txn.kgs), 0);

            // Append total row
            const totalRow = `
                <tr>
                    <td colspan="2"><strong>Total Transactions: ${totalTransactions}</strong></td>
                    <td><strong>${totalQuantity.toFixed(2)}</strong></td>
                    <td><strong>${totalKGs.toFixed(2)}</strong></td>
                    <td></td>
                </tr>
            `;
            tbody.append(totalRow);

            // Update hidden input field
            $('#transactions-json').val(JSON.stringify(transactions));

            // Update the summary
            generateSummary();
        }

        // Function to clear transaction input fields
        function clearTransactionInputs() {
            $('#input-outlet-name').val('');
            $('#input-dr-no').val('');
            $('#input-quantity').val('');
            $('#input-kgs').val('');
            $('#outlet-suggestions').empty();
            $('#input-outlet-name').removeClass('is-invalid').removeClass('is-valid');
            $('#input-dr-no').removeClass('is-invalid').removeClass('is-valid');
        }

        // Real-Time Validation for DR No
        $('#input-dr-no').on('blur', function () {
            const drNo = parseInt($(this).val());
            if (isNaN(drNo)) {
                $('#dr-no-error').removeClass('d-none').text('Enter a valid DR No.');
                $('#input-dr-no').removeClass('is-valid').addClass('is-invalid');
                return;
            }

            // AJAX call to validate DR No against the database
            $.getJSON(`validate_dr_no.php`, { dr_no: drNo }, function (drData) {
                if (drData.exists) {
                    // DR No exists in the database
                    $('#dr-no-error').removeClass('d-none').text('Already exists.');
                    $('#input-dr-no').removeClass('is-valid').addClass('is-invalid');
                } else {
                    // DR No is unique
                    $('#dr-no-error').addClass('d-none').text('');
                    $('#input-dr-no').removeClass('is-invalid').addClass('is-valid');
                }
            }).fail(function () {
                // Handle AJAX error
                $('#dr-no-error').removeClass('d-none').text('Error.');
                $('#input-dr-no').removeClass('is-valid').addClass('is-invalid');
            });
        });

        // Real-Time Validation for Outlet Name
        $('#input-outlet-name').on('blur', function () {
            const outletName = $(this).val().trim();
            if (outletName === '') {
                $('#outlet-error').removeClass('d-none').text('Outlet Name cannot be empty.');
                $('#input-outlet-name').removeClass('is-valid').addClass('is-invalid');
                return;
            }

            // AJAX call to validate Outlet Name
            $.getJSON(`../includes/validate_outlet.php`, { outlet_name: outletName }, function (data) {
                if (data.exists) {
                    // Outlet exists
                    $('#outlet-error').addClass('d-none').text('');
                    $('#input-outlet-name').removeClass('is-invalid').addClass('is-valid');
                } else {
                    // Outlet does not exist
                    $('#outlet-error').removeClass('d-none').text('Outlet Name does not exist.');
                    $('#input-outlet-name').removeClass('is-valid').addClass('is-invalid');
                }
            }).fail(function () {
                // Handle AJAX error
                $('#outlet-error').removeClass('d-none').text('Error validating Outlet Name.');
                $('#input-outlet-name').removeClass('is-valid').addClass('is-invalid');
            });
        });

        // Add Transaction Button Click Event
        $('#add-transaction-btn').click(function () {
            const outletName = $('#input-outlet-name').val().trim();
            const drNo = parseInt($('#input-dr-no').val());
            const quantity = parseFloat($('#input-quantity').val());
            const kgs = parseFloat($('#input-kgs').val());

            // Reset validation state
            $('#input-outlet-name').removeClass('is-invalid').removeClass('is-valid');
            $('#outlet-error').addClass('d-none').text('');
            $('#input-dr-no').removeClass('is-invalid').removeClass('is-valid');
            $('#dr-no-error').addClass('d-none').text('');

            // Validate inputs
            if (outletName === '' || isNaN(drNo) || isNaN(quantity) || isNaN(kgs)) {
                alert('Please enter valid transaction details.');
                return;
            }

            // Check for duplicate DR No within current transactions
            const duplicateDR = transactions.some(txn => txn.drNo === drNo);
            if (duplicateDR) {
                $('#dr-no-error').removeClass('d-none').text('DR No already exists in current transactions.');
                $('#input-dr-no').removeClass('is-valid').addClass('is-invalid');
                return;
            }

            // Disable button to prevent multiple clicks
            const addBtn = $(this);
            addBtn.prop('disabled', true).text('Validating...');

            // First, validate Outlet Name via AJAX
            $.getJSON(`../includes/validate_outlet.php`, { outlet_name: outletName }, function (data) {
                if (data.exists) {
                    // Outlet exists, now validate DR No against database
                    $.getJSON(`validate_dr_no.php`, { dr_no: drNo }, function (drData) {
                        if (drData.exists) {
                            // DR No already exists in database, show error
                            $('#dr-no-error').removeClass('d-none').text('Already exists.');
                            $('#input-dr-no').removeClass('is-valid').addClass('is-invalid');
                            addBtn.prop('disabled', false).text('Add Transaction');
                        } else {
                            // DR No is unique, proceed to add transaction
                            $('#dr-no-error').addClass('d-none').text('');
                            $('#input-dr-no').removeClass('is-invalid').addClass('is-valid');
                            const transaction = { drNo: drNo, outletName: outletName, quantity: quantity, kgs: kgs };
                            transactions.push(transaction);
                            updateTransactionsTable();
                            clearTransactionInputs();
                            addBtn.prop('disabled', false).text('Add Transaction');
                        }
                    }).fail(function () {
                        alert('Error validating DR No.');
                        addBtn.prop('disabled', false).text('Add Transaction');
                    });
                } else {
                    // Outlet does not exist, show error
                    $('#outlet-error').removeClass('d-none').text('Outlet Name does not exist.');
                    $('#input-outlet-name').removeClass('is-valid').addClass('is-invalid');
                    addBtn.prop('disabled', false).text('Add Transaction');
                }
            }).fail(function () {
                alert('Error validating outlet name.');
                addBtn.prop('disabled', false).text('Add Transaction');
            });
        });

        // Delete Transaction Function
        window.deleteTransaction = function (index) {
            if (confirm('Are you sure you want to delete this transaction?')) {
                transactions.splice(index, 1);
                updateTransactionsTable();
            }
        };

        // Edit Transaction Function
        window.editTransaction = function (index) {
            editingIndex = index;
            const transaction = transactions[index];
            $('#edit-dr-no').val(transaction.drNo);
            $('#edit-outlet-name').val(transaction.outletName);
            $('#edit-quantity').val(transaction.quantity);
            $('#edit-kgs').val(transaction.kgs);
            $('#edit-outlet-suggestions').empty();
            $('#edit-outlet-name').removeClass('is-invalid').removeClass('is-valid');
            $('#edit-dr-no').removeClass('is-invalid').removeClass('is-valid');
            $('#editTransactionModal').modal('show');
        };

        // Save Edited Transaction
        $('#save-edit-btn').click(function () {
            const drNo = parseInt($('#edit-dr-no').val());
            const outletName = $('#edit-outlet-name').val().trim();
            const quantity = parseFloat($('#edit-quantity').val());
            const kgs = parseFloat($('#edit-kgs').val());

            // Reset validation state
            $('#edit-outlet-name').removeClass('is-invalid').removeClass('is-valid');
            $('#edit-outlet-error').addClass('d-none').text('');
            $('#edit-dr-no').removeClass('is-invalid').removeClass('is-valid');
            $('#edit-dr-no-error').addClass('d-none').text('');

            // Validate inputs
            if (outletName === '' || isNaN(drNo) || isNaN(quantity) || isNaN(kgs)) {
                alert('Please enter valid transaction details.');
                return;
            }

            // Check for duplicate DR No within current transactions, excluding the one being edited
            const duplicateDR = transactions.some((txn, idx) => txn.drNo === drNo && idx !== editingIndex);
            if (duplicateDR) {
                $('#edit-dr-no-error').removeClass('d-none').text('DR No already exists in current transactions.');
                $('#edit-dr-no').removeClass('is-valid').addClass('is-invalid');
                return;
            }

            // Disable button to prevent multiple clicks
            const saveBtn = $(this);
            saveBtn.prop('disabled', true).text('Validating...');

            // Validate Outlet Name via AJAX
            $.getJSON(`../includes/validate_outlet.php`, { outlet_name: outletName }, function (data) {
                if (data.exists) {
                    // Outlet exists, now validate DR No against database if changed
                    const originalDrNo = transactions[editingIndex].drNo;
                    if (drNo !== originalDrNo) {
                        // DR No has changed, validate uniqueness in database
                        $.getJSON(`validate_dr_no.php`, { dr_no: drNo }, function (drData) {
                            if (drData.exists) {
                                // DR No already exists in database, show error
                                $('#edit-dr-no-error').removeClass('d-none').text('Already exists.');
                                $('#edit-dr-no').removeClass('is-valid').addClass('is-invalid');
                                saveBtn.prop('disabled', false).text('Save changes');
                            } else {
                                // DR No is unique, proceed to update transaction
                                $('#edit-dr-no-error').addClass('d-none').text('');
                                $('#edit-dr-no').removeClass('is-invalid').addClass('is-valid');
                                transactions[editingIndex] = {
                                    drNo: drNo,
                                    outletName: outletName,
                                    quantity: quantity,
                                    kgs: kgs
                                };
                                updateTransactionsTable();
                                $('#editTransactionModal').modal('hide');
                                saveBtn.prop('disabled', false).text('Save changes');
                            }
                        }).fail(function () {
                            alert('Error validating DR No.');
                            saveBtn.prop('disabled', false).text('Save changes');
                        });
                    } else {
                        // DR No hasn't changed, proceed to update
                        $('#edit-dr-no-error').addClass('d-none').text('');
                        $('#edit-dr-no').removeClass('is-invalid').addClass('is-valid');
                        transactions[editingIndex] = {
                            drNo: drNo,
                            outletName: outletName,
                            quantity: quantity,
                            kgs: kgs
                        };
                        updateTransactionsTable();
                        $('#editTransactionModal').modal('hide');
                        saveBtn.prop('disabled', false).text('Save changes');
                    }
                } else {
                    // Outlet does not exist, show error
                    $('#edit-outlet-error').removeClass('d-none').text('Outlet Name does not exist.');
                    $('#edit-outlet-name').removeClass('is-valid').addClass('is-invalid');
                    saveBtn.prop('disabled', false).text('Save changes');
                }
            }).fail(function () {
                alert('Error validating outlet name.');
                saveBtn.prop('disabled', false).text('Save changes');
            });
        });

        // Outlet Name Autocomplete for Add Transaction
        $('#input-outlet-name').on('input', function () {
            const searchQuery = $(this).val();
            if (searchQuery.length >= 1) {
                $.getJSON('search_outlets.php', { query: searchQuery }, function (data) {
                    const suggestionsList = $('#outlet-suggestions');
                    suggestionsList.empty();
                    data.forEach(outlet => {
                        const suggestionItem = $('<a></a>')
                            .addClass('list-group-item list-group-item-action')
                            .text(outlet.CustomerName)
                            .attr('href', '#')
                            .click(function (e) {
                                e.preventDefault();
                                $('#input-outlet-name').val(outlet.CustomerName);
                                suggestionsList.empty();
                                // Trigger validation after selecting a suggestion
                                $('#input-outlet-name').trigger('blur');
                            });
                        suggestionsList.append(suggestionItem);
                    });
                });
            } else {
                $('#outlet-suggestions').empty();
            }
        });

        // Outlet Name Autocomplete for Edit Transaction
        $('#edit-outlet-name').on('input', function () {
            const searchQuery = $(this).val();
            if (searchQuery.length >= 1) {
                $.getJSON('search_outlets.php', { query: searchQuery }, function (data) {
                    const suggestionsList = $('#edit-outlet-suggestions');
                    suggestionsList.empty();
                    data.forEach(outlet => {
                        const suggestionItem = $('<a></a>')
                            .addClass('list-group-item list-group-item-action')
                            .text(outlet.CustomerName)
                            .attr('href', '#')
                            .click(function (e) {
                                e.preventDefault();
                                $('#edit-outlet-name').val(outlet.CustomerName);
                                suggestionsList.empty();
                                // Trigger validation after selecting a suggestion
                                $('#edit-outlet-name').trigger('blur');
                            });
                        suggestionsList.append(suggestionItem);
                    });
                });
            } else {
                $('#edit-outlet-suggestions').empty();
            }
        });

        // Hide suggestions when clicking outside for Add Transaction and Edit Transaction
        $(document).on('click', function (event) {
            const isClickInsideAdd = $('#input-outlet-name').is(event.target) || $('#outlet-suggestions').has(event.target).length > 0;
            if (!isClickInsideAdd) {
                $('#outlet-suggestions').empty();
            }

            const isClickInsideEdit = $('#edit-outlet-name').is(event.target) || $('#edit-outlet-suggestions').has(event.target).length > 0;
            if (!isClickInsideEdit) {
                $('#edit-outlet-suggestions').empty();
            }
        });

        // Calculate Fuel Amount and Total Expenses
        function calculateFuelAmount() {
            if (!validateFuelUnitPrice()) {
                $('#fuel-amount').val('');
                return;
            }

            const liters = parseFloat($('#fuel-liters').val());
            const unitPrice = parseFloat($('#fuel-unit-price').val());
            if (!isNaN(liters) && !isNaN(unitPrice)) {
                const amount = liters * unitPrice;
                $('#fuel-amount').val(amount.toFixed(2));
            } else {
                $('#fuel-amount').val('');
            }
            // After calculating fuel amount, recalculate total expenses
            calculateTotalExpense();
        }

        $('#fuel-liters, #fuel-unit-price').on('input', calculateFuelAmount);

        // Calculate Total Expense (Including Fuel)
        function calculateTotalExpense() {
            const salary = parseFloat($('#expenses-salary').val()) || 0;
            const mobileFee = parseFloat($('#expenses-mobile-fee').val()) || 0;
            const otherAmount = parseFloat($('#expenses-other-amount').val()) || 0;
            const fuelAmount = parseFloat($('#fuel-amount').val()) || 0;

            const combinedExpensesTotal = salary + mobileFee + otherAmount + fuelAmount;

            $('#expenses-total').val(combinedExpensesTotal.toFixed(2));

            // Update the summary
            generateSummary();
        }

        $('#expenses-salary, #expenses-mobile-fee, #expenses-other-amount').on('input', calculateTotalExpense);

        // Function to add Toll Fee to the table
        $('#add-toll-btn').click(function () {
            const tollFee = parseFloat($('#toll-fee-input').val());

            // Validate input
            if (isNaN(tollFee) || tollFee < 0) {
                alert('Please enter a valid toll fee amount.');
                return;
            }

            // Add toll fee to the tollFees array
            tollFees.push(tollFee);

            // Update the toll fees table
            updateTollFeesTable();

            // Clear the toll fee input field
            $('#toll-fee-input').val('');
        });

        // Function to update the Toll Fees Table and Total
        function updateTollFeesTable() {
            const tbody = $('#toll-fees-table tbody');
            tbody.empty();

            // Render toll fees
            tollFees.forEach((fee, index) => {
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${fee.toFixed(2)}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteTollFee(${index})">Delete</button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });

            // Calculate total toll fees
            const totalTollFees = tollFees.reduce((sum, fee) => sum + fee, 0);
            $('#toll-fees-total').text(totalTollFees.toFixed(2));

            // Set the total in the hidden input field
            $('#toll_fee_amount').val(totalTollFees.toFixed(2));
        }

        // Function to delete a Toll Fee from the table
        window.deleteTollFee = function (index) {
            if (confirm('Are you sure you want to delete this toll fee?')) {
                tollFees.splice(index, 1);
                updateTollFeesTable();
            }
        };

        // Generate Summary Function
        function generateSummary() {
            let summaryHtml = '<h5>Summary</h5>';

            let truckInfo = $('#truck-select option:selected').text() || 'No truck selected';
            let transactionDate = $('#transaction-date').val() || 'No date selected';

            // Start Row
            summaryHtml += '<div class="row">';

            // Truck Column
            summaryHtml += '<div class="col">';
            summaryHtml += '<p><strong>Truck:</strong> ' + truckInfo + '</p>';
            summaryHtml += '</div>'; // Close Truck Column

            // Date Column
            summaryHtml += '<div class="col">';
            summaryHtml += '<p><strong>Date:</strong> ' + transactionDate + '</p>';
            summaryHtml += '</div>'; // Close Date Column

            // End Row
            summaryHtml += '</div>'; // Close Row


            // Transactions Summary
            summaryHtml += '<h6>Transactions</h6>';
            summaryHtml += '<table class="table table-bordered">';
            summaryHtml += '<thead><tr><th>DR No</th><th>Outlet Name</th><th>Quantity</th><th>KGs</th></tr></thead><tbody>';
            let total_kgs = 0;
            transactions.forEach(txn => {
                summaryHtml += '<tr>';
                summaryHtml += '<td>' + txn.drNo + '</td>';
                summaryHtml += '<td>' + sanitizeHTML(txn.outletName) + '</td>';
                summaryHtml += '<td>' + txn.quantity + '</td>';
                summaryHtml += '<td>' + txn.kgs + '</td>';
                summaryHtml += '</tr>';
                total_kgs += parseFloat(txn.kgs);
            });
            // Total row in summary
            summaryHtml += '<tr>';
            summaryHtml += '<td colspan="2"><strong>Total Transactions: ' + transactions.length + '</strong></td>';
            let totalQuantity = transactions.reduce((sum, txn) => sum + parseFloat(txn.quantity), 0);
            summaryHtml += '<td><strong>' + totalQuantity.toFixed(2) + '</strong></td>';
            summaryHtml += '<td><strong>' + total_kgs.toFixed(2) + '</strong></td>';
            summaryHtml += '</tr>';

            summaryHtml += '</tbody></table>';

            // Round up Total KGs
            let rounded_total_kgs = 0;
            if (total_kgs > 0) {
                if (total_kgs <= 1199) {
                    rounded_total_kgs = 1000;
                } else if (total_kgs <= 4199) {
                    rounded_total_kgs = Math.ceil(total_kgs / 1000) * 1000;
                    if (rounded_total_kgs > 4000) {  // Ensure it doesn’t exceed 4000
                        rounded_total_kgs = 4000;
                    }
                } else {
                    rounded_total_kgs = 4000;
                }
            }

            // Get expenses total (already includes fuel_amount)
            const expensesTotal = parseFloat($('#expenses-total').val()) || 0;
            // Start Row
            summaryHtml += '<div class="row">';

            // Fuel Details Column
            summaryHtml += '<div class="col">';
            // Fuel Details
            const fuelAmount = parseFloat($('#fuel-amount').val()) || 0;
            summaryHtml += '<h6>Fuel Details</h6>';
            summaryHtml += '<p><strong>Liters:</strong> ' + $('#fuel-liters').val() + '</p>';
            summaryHtml += '<p><strong>Unit Price:</strong> ' + $('#fuel-unit-price').val() + '</p>';
            summaryHtml += '<p><strong>Fuel Type:</strong> ' + $('#fuel-type').val() + '</p>';
            summaryHtml += '<p><strong>Fuel Amount:</strong> ' + fuelAmount.toFixed(2) + '</p>';
            summaryHtml += '</div>'; // Close Fuel Details Column

            // Expenses Column
            summaryHtml += '<div class="col">';
            // Expenses
            summaryHtml += '<h6>Expenses</h6>';
            summaryHtml += '<p><strong>Salary Amount:</strong> ' + $('#expenses-salary').val() + '</p>';
            summaryHtml += '<p><strong>Mobile Fee:</strong> ' + $('#expenses-mobile-fee').val() + '</p>';
            summaryHtml += '<p><strong>Other Amount:</strong> ' + $('#expenses-other-amount').val() + '</p>';
            summaryHtml += '<p><strong>Total Expenses (Including Fuel):</strong> ' + expensesTotal.toFixed(2) + '</p>';
            summaryHtml += '</div>'; // Close Expenses Column

            // End Row
            summaryHtml += '</div>'; // Close Row

            // Toll Fee Amount
            const tollFeeAmount = parseFloat($('#toll_fee_amount').val()) || 0;

            // Calculated Totals
            summaryHtml += '<h6>Calculated Totals</h6>';

            // Start Row
            summaryHtml += '<div class="row">';

            // Column 1: Original and Rounded Total KGs
            summaryHtml += '<div class="col">';
            summaryHtml += '<p><strong>Original Total KGs:</strong> ' + total_kgs.toFixed(2) + '</p>';
            summaryHtml += '<p><strong>Rounded Total KGs:</strong> ' + rounded_total_kgs.toFixed(0) + '</p>';
            summaryHtml += '</div>'; // Close Column 1

            // Fetch Cluster ID and Rate Amount via AJAX
            if (transactions.length > 0) {
                const firstOutletName = transactions[0].outletName;
                const fuelUnitPrice = parseFloat($('#fuel-unit-price').val()) || 0;

                // AJAX call to get ClusterID and RateAmount
                $.ajax({
                    url: 'get_cluster_rate.php',
                    method: 'POST',
                    data: {
                        outlet_name: firstOutletName,
                        fuel_price: fuelUnitPrice,
                        tonner: rounded_total_kgs
                    },
                    dataType: 'json',
                    success: function (data) {
                        if (data.success) {
                            // Column 2: Rate Amount and Total Amount
                            summaryHtml += '<div class="col">';
                            summaryHtml += '<p><strong>Rate Amount:</strong> ₱' + parseFloat(data.rate_amount).toFixed(2) + '</p>';

                            // Final Amount
                            const finalAmount = parseFloat(data.rate_amount) + tollFeeAmount;
                            summaryHtml += '<p><strong>Total Amount:</strong> ₱' + finalAmount.toFixed(2) + '</p>';
                            summaryHtml += '</div>'; // Close Column 2

                            // End Row
                            summaryHtml += '</div>'; // Close Row

                            // Display the summary
                            $('#summary-content-modal').html(summaryHtml);
                        } else {
                            summaryHtml += '<p><strong>Error:</strong> ' + data.message + '</p>';
                            $('#summary-content-modal').html(summaryHtml);
                        }
                    },
                    error: function () {
                        summaryHtml += '<p><strong>Error fetching Cluster ID and Rate Amount.</strong></p>';
                        $('#summary-content-modal').html(summaryHtml);
                    }
                });
            } else {
                summaryHtml += '<p><strong>No transactions to calculate Cluster ID and Rate Amount.</strong></p>';
                $('#summary-content-modal').html(summaryHtml);
            }
        }

        // Form submission validation and modal display
        $('#review-submit-btn').on('click', function (event) {
            // Perform final validations before submission

            // Ensure that at least one transaction is added
            if (transactions.length === 0) {
                alert('Please add at least one transaction.');
                return;
            }

            // Ensure that all required fields are filled
            const fuelLiters = $('#fuel-liters').val();
            const fuelUnitPrice = $('#fuel-unit-price').val();
            const fuelType = $('#fuel-type').val();
            const fuelAmount = $('#fuel-amount').val();

            if (fuelLiters === '' || fuelUnitPrice === '' || fuelType === null || fuelAmount === '') {
                alert('Please fill in all fuel details.');
                return;
            }

            const expensesSalary = $('#expenses-salary').val();
            const expensesMobileFee = $('#expenses-mobile-fee').val();
            const expensesOtherAmount = $('#expenses-other-amount').val();
            const expensesTotal = $('#expenses-total').val();

            if (expensesSalary === '' || expensesMobileFee === '' || expensesOtherAmount === '' || expensesTotal === '') {
                alert('Please fill in all expenses details.');
                return;
            }

            const tollFeeAmount = $('#toll_fee_amount').val();
            if (tollFeeAmount === '') {
                alert('Please add at least one toll fee amount.');
                return;
            }

            // Ensure that date and truck are selected
            const transactionDate = $('#transaction-date').val();
            const truckId = $('#truck-select').val();

            if (transactionDate === '' || truckId === null) {
                alert('Please select a date and truck.');
                return;
            }

            // All validations passed, generate summary and show modal
            generateSummary();
            $('#transactionSummaryModal').modal('show');
        });

        // Confirm submission button in modal
        $('#confirm-submit-btn').on('click', function () {
            // Submit the form
            $('#final-submit-btn').click();
        });

        // Function to validate Fuel Unit Price
        function validateFuelUnitPrice() {
            const fuelUnitPriceInput = $('#fuel-unit-price');
            const fuelUnitPriceError = $('#fuel-unit-price-error');
            const value = parseFloat(fuelUnitPriceInput.val());

            if (isNaN(value) || value < 50 || value > 100) {
                fuelUnitPriceInput.addClass('is-invalid');
                fuelUnitPriceError.removeClass('d-none').text('Enter a value between 50 and 100.');
                return false;
            } else {
                fuelUnitPriceInput.removeClass('is-invalid').addClass('is-valid');
                fuelUnitPriceError.addClass('d-none').text('');
                return true;
            }
        }

        // Call validation on input change
        $('#fuel-unit-price').on('input', function () {
            validateFuelUnitPrice();
        });

        // Integrate validation before calculating fuel amount
        function calculateFuelAmount() {
            if (!validateFuelUnitPrice()) {
                $('#fuel-amount').val('');
                return;
            }

            const liters = parseFloat($('#fuel-liters').val());
            const unitPrice = parseFloat($('#fuel-unit-price').val());
            if (!isNaN(liters) && !isNaN(unitPrice)) {
                const amount = liters * unitPrice;
                $('#fuel-amount').val(amount.toFixed(2));
            } else {
                $('#fuel-amount').val('');
            }
            // After calculating fuel amount, recalculate total expenses
            calculateTotalExpense();
        }

    });

    // Function to edit a transaction (Global Scope)
    function editTransaction(index) {
        // The function is already defined within $(document).ready in the above script
        // No action needed here
    }
</script>
<?php
include '../officer/footer.php';
$conn->close();
?>