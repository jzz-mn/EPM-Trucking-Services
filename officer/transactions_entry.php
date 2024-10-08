<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';

// Fetch the last DR No from transactions table
$last_dr_no_query = "SELECT COALESCE(MAX(DRno), 0) AS LastDRNo FROM transactions";
$last_dr_no_result = $conn->query($last_dr_no_query);
$last_dr_no = $last_dr_no_result->fetch_assoc()['LastDRNo'];

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and store form data
    $_SESSION['transaction_date'] = $_POST['transaction_date'];
    $_SESSION['truck_id'] = $_POST['truck_id'];

    // Fetch truck details for display
    $truck_query = "SELECT PlateNo, TruckBrand FROM trucksinfo WHERE TruckID = ?";
    $stmt = $conn->prepare($truck_query);
    $stmt->bind_param("i", $_SESSION['truck_id']);
    $stmt->execute();
    $truck_result = $stmt->get_result();
    $truck = $truck_result->fetch_assoc();
    $stmt->close();
} else {
    // Redirect back if accessed directly
    header("Location: add_data.php");
    exit();
}
?>

<div class="body-wrapper">
    <div class="container-fluid">
        <!-- Truck Details -->
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title">Transactions for Truck: <?php echo htmlspecialchars($truck['PlateNo'] . ' - ' . $truck['TruckBrand']); ?></h4>
                <p class="card-subtitle mb-4">Date: <?php echo htmlspecialchars($_SESSION['transaction_date']); ?></p>
            </div>
        </div>

        <!-- Add New Transaction Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Add New Transaction</h5>
                <form id="add-transaction-form">
                    <div class="row g-3 align-items-end">
                        <!-- Outlet Name with Autocomplete and Validation -->
                        <div class="col-md-4 position-relative">
                            <label for="input-outlet-name" class="form-label">Outlet Name</label>
                            <input type="text" class="form-control" id="input-outlet-name" placeholder="Search Outlet Name" autocomplete="off" required>
                            <div id="outlet-suggestions" class="list-group position-absolute w-100" style="z-index: 1000; background-color: white;"></div>
                            <!-- Validation Feedback -->
                            <div class="invalid-feedback" id="outlet-error">
                                Outlet Name does not exist.
                            </div>
                        </div>
                        <!-- Quantity -->
                        <div class="col-md-3">
                            <label for="input-quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="input-quantity" placeholder="Quantity" step="0.01" required>
                        </div>
                        <!-- KGs -->
                        <div class="col-md-3">
                            <label for="input-kgs" class="form-label">KGs</label>
                            <input type="number" class="form-control" id="input-kgs" placeholder="KGs" step="0.01" required>
                        </div>
                        <!-- Add Transaction Button -->
                        <div class="col-md-2">
                            <button type="button" class="btn btn-success w-100" id="add-transaction-btn">Add Transaction</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transaction List Table -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Transactions Entry</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered text-nowrap align-middle text-center" id="transactions-table">
                        <thead>
                            <tr>
                                <th>DR No</th>
                                <th>Outlet Name</th>
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
                <form id="transactions-form" method="POST" action="fuel_entry.php">
                    <input type="hidden" name="transactions_json" id="transactions-json">
                    <!-- Buttons Row -->
                    <div class="d-flex justify-content-between">
                        <a href="add_data.php" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary" id="next-button" disabled>Next</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-labelledby="editTransactionModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Edit Transaction Form -->
                <form id="edit-transaction-form">
                    <div class="mb-3 position-relative">
                        <label for="edit-outlet-name" class="form-label">Outlet Name</label>
                        <input type="text" class="form-control" id="edit-outlet-name" placeholder="Search Outlet Name" autocomplete="off" required>
                        <div id="edit-outlet-suggestions" class="list-group position-absolute w-100" style="z-index: 1000; background-color: white;"></div>
                        <!-- Validation Feedback -->
                        <div class="invalid-feedback" id="edit-outlet-error">
                            Outlet Name does not exist.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit-quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="edit-quantity" placeholder="Quantity" step="0.01" required>
                    </div>
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

<!-- JavaScript to handle adding transactions dynamically and validation -->
<script>
    let transactions = [];
    let drCounter = <?php echo json_encode($last_dr_no + 1); ?>;
    let editingIndex = -1;

    // Function to sanitize HTML to prevent XSS
    function sanitizeHTML(str) {
        const temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    }

    // Add Transaction Button Click Event
    document.getElementById('add-transaction-btn').addEventListener('click', function () {
        const outletInput = document.getElementById('input-outlet-name');
        const outletName = outletInput.value.trim();
        const quantityInput = document.getElementById('input-quantity');
        const quantity = parseFloat(quantityInput.value);
        const kgsInput = document.getElementById('input-kgs');
        const kgs = parseFloat(kgsInput.value);

        // Reset validation states
        outletInput.classList.remove('is-invalid');

        // Validation
        if (outletName === '' || isNaN(quantity) || isNaN(kgs)) {
            alert('Please enter valid details.');
            return;
        }

        // Disable the button to prevent multiple clicks
        const addBtn = this;
        addBtn.disabled = true;
        addBtn.textContent = 'Validating...';

        // Validate Outlet Name via AJAX
        fetch(`../includes/validate_outlet.php?outlet_name=${encodeURIComponent(outletName)}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    // Outlet exists, proceed to add the transaction
                    const transaction = { drNo: drCounter, outletName: outletName, quantity: quantity, kgs: kgs };
                    drCounter++;
                    transactions.push(transaction);
                    updateTransactionsTable();
                    clearInputs();
                } else {
                    // Outlet does not exist, show validation error
                    outletInput.classList.add('is-invalid');
                }
            })
            .catch(error => {
                console.error('Error validating outlet:', error);
                alert('An error occurred while validating the Outlet Name. Please try again.');
            })
            .finally(() => {
                // Re-enable the button
                addBtn.disabled = false;
                addBtn.textContent = 'Add Transaction';
            });
    });

    // Clear input fields after adding a transaction
    function clearInputs() {
        document.getElementById('add-transaction-form').reset();
        document.getElementById('outlet-suggestions').innerHTML = '';
        // Remove any validation errors
        const outletInput = document.getElementById('input-outlet-name');
        outletInput.classList.remove('is-invalid');
    }

    // Update Transactions Table
    function updateTransactionsTable() {
        const tbody = document.getElementById('transactions-body');
        tbody.innerHTML = '';

        transactions.forEach((txn, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${txn.drNo}</td>
                <td>${sanitizeHTML(txn.outletName)}</td>
                <td>${txn.quantity}</td>
                <td>${txn.kgs}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-warning me-1" onclick="editTransaction(${index})">Edit</button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteTransaction(${index})">Delete</button>
                </td>
            `;
            tbody.appendChild(row);
        });

        // Update hidden input field
        document.getElementById('transactions-json').value = JSON.stringify(transactions);
        document.getElementById('next-button').disabled = transactions.length === 0;
    }

    // Delete Transaction Function
    function deleteTransaction(index) {
        if (confirm('Are you sure you want to delete this transaction?')) {
            transactions.splice(index, 1);
            updateTransactionsTable();
        }
    }

    // Edit Transaction Function
    function editTransaction(index) {
        editingIndex = index;
        const transaction = transactions[index];
        const editOutletInput = document.getElementById('edit-outlet-name');
        const editQuantityInput = document.getElementById('edit-quantity');
        const editKgsInput = document.getElementById('edit-kgs');

        editOutletInput.value = transaction.outletName;
        editQuantityInput.value = transaction.quantity;
        editKgsInput.value = transaction.kgs;

        // Reset validation states
        editOutletInput.classList.remove('is-invalid');

        // Show the edit modal using Bootstrap 5
        var editModal = new bootstrap.Modal(document.getElementById('editTransactionModal'));
        editModal.show();
    }

    // Save Edited Transaction
    document.getElementById('save-edit-btn').addEventListener('click', function () {
        const editOutletInput = document.getElementById('edit-outlet-name');
        const outletName = editOutletInput.value.trim();
        const editQuantityInput = document.getElementById('edit-quantity');
        const quantity = parseFloat(editQuantityInput.value);
        const editKgsInput = document.getElementById('edit-kgs');
        const kgs = parseFloat(editKgsInput.value);

        // Reset validation states
        editOutletInput.classList.remove('is-invalid');

        // Validation
        if (outletName === '' || isNaN(quantity) || isNaN(kgs)) {
            alert('Please enter valid details.');
            return;
        }

        // Disable the button to prevent multiple clicks
        const saveBtn = this;
        saveBtn.disabled = true;
        saveBtn.textContent = 'Validating...';

        // Validate Outlet Name via AJAX
        fetch(`../includes/validate_outlet.php?outlet_name=${encodeURIComponent(outletName)}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    // Outlet exists, proceed to update the transaction
                    transactions[editingIndex] = { drNo: transactions[editingIndex].drNo, outletName: outletName, quantity: quantity, kgs: kgs };
                    updateTransactionsTable();
                    // Hide the edit modal
                    var editModal = bootstrap.Modal.getInstance(document.getElementById('editTransactionModal'));
                    editModal.hide();
                } else {
                    // Outlet does not exist, show validation error
                    editOutletInput.classList.add('is-invalid');
                }
            })
            .catch(error => {
                console.error('Error validating outlet:', error);
                alert('An error occurred while validating the Outlet Name. Please try again.');
            })
            .finally(() => {
                // Re-enable the button
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save changes';
            });
    });

    // Outlet name autocomplete for Add Transaction
    document.getElementById('input-outlet-name').addEventListener('input', function () {
        const searchQuery = this.value;
        if (searchQuery.length >= 1) {
            fetch(`search_outlets.php?query=${encodeURIComponent(searchQuery)}`)
                .then(response => response.json())
                .then(data => {
                    const suggestionsList = document.getElementById('outlet-suggestions');
                    suggestionsList.innerHTML = ''; // Clear previous suggestions

                    data.forEach(outlet => {
                        const suggestionItem = document.createElement('a');
                        suggestionItem.classList.add('list-group-item', 'list-group-item-action');
                        suggestionItem.textContent = outlet.CustomerName;
                        suggestionItem.href = '#';
                        suggestionItem.addEventListener('click', function (e) {
                            e.preventDefault();
                            document.getElementById('input-outlet-name').value = outlet.CustomerName;
                            suggestionsList.innerHTML = ''; // Clear suggestions after selection
                        });
                        suggestionsList.appendChild(suggestionItem);
                    });
                })
                .catch(error => console.error('Error fetching outlet suggestions:', error));
        } else {
            document.getElementById('outlet-suggestions').innerHTML = ''; // Clear if query is too short
        }
    });

    // Outlet name autocomplete for Edit Transaction
    document.getElementById('edit-outlet-name').addEventListener('input', function () {
        const searchQuery = this.value;
        if (searchQuery.length >= 1) {
            fetch(`search_outlets.php?query=${encodeURIComponent(searchQuery)}`)
                .then(response => response.json())
                .then(data => {
                    const suggestionsList = document.getElementById('edit-outlet-suggestions');
                    suggestionsList.innerHTML = ''; // Clear previous suggestions

                    data.forEach(outlet => {
                        const suggestionItem = document.createElement('a');
                        suggestionItem.classList.add('list-group-item', 'list-group-item-action');
                        suggestionItem.textContent = outlet.CustomerName;
                        suggestionItem.href = '#';
                        suggestionItem.addEventListener('click', function (e) {
                            e.preventDefault();
                            document.getElementById('edit-outlet-name').value = outlet.CustomerName;
                            suggestionsList.innerHTML = ''; // Clear suggestions after selection
                        });
                        suggestionsList.appendChild(suggestionItem);
                    });
                })
                .catch(error => console.error('Error fetching outlet suggestions:', error));
        } else {
            document.getElementById('edit-outlet-suggestions').innerHTML = ''; // Clear if query is too short
        }
    });

    // Hide suggestions when clicking outside for Add Transaction and Edit Transaction
    document.addEventListener('click', function (event) {
        const isClickInsideAdd = document.getElementById('input-outlet-name').contains(event.target) || document.getElementById('outlet-suggestions').contains(event.target);
        if (!isClickInsideAdd) {
            document.getElementById('outlet-suggestions').innerHTML = '';
        }

        const isClickInsideEdit = document.getElementById('edit-outlet-name').contains(event.target) || document.getElementById('edit-outlet-suggestions').contains(event.target);
        if (!isClickInsideEdit) {
            document.getElementById('edit-outlet-suggestions').innerHTML = '';
        }
    });
</script>

<?php
include '../officer/footer.php';
$conn->close();
?>
