<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';

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
        <!-- Existing card and breadcrumb code -->
        <div class="card card-body py-3">
            <!-- ... -->
        </div>
        <div class="widget-content searchable-container list">
            <h5 class="border-bottom py-2 px-4 mb-4">Transactions Entry</h5>
            <div class="card w-100 border position-relative overflow-hidden mb-0">
                <div class="card-body p-4">
                    <h4 class="card-title">Transactions for Truck:
                        <?php echo $truck['PlateNo'] . ' - ' . $truck['TruckBrand']; ?></h4>
                    <p class="card-subtitle mb-4">Enter transaction details for the date:
                        <?php echo $_SESSION['transaction_date']; ?></p>
                    <form id="transactions-form" method="POST" action="fuel_entry.php">
                        <!-- Transaction List Table -->
                        <table class="table" id="transactions-table">
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
                        <!-- Add New Transaction -->
                        <h5 class="mt-4">Add New Transaction</h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="input-outlet-name" placeholder="Outlet Name"
                                    required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" class="form-control" id="input-quantity" placeholder="Quantity"
                                    step="0.01" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" class="form-control" id="input-kgs" placeholder="KGs" step="0.01"
                                    required>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-success" id="add-transaction-btn">Add
                                    Transaction</button>
                            </div>
                        </div>
                        <!-- Hidden input to store transactions data -->
                        <input type="hidden" name="transactions_json" id="transactions-json">
                        <!-- Next Button -->
                        <button type="submit" class="btn btn-primary mt-4" id="next-button" disabled>Next</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript to handle adding transactions dynamically -->
<script>
    let transactions = [];
    let drCounter = 1;

    document.getElementById('add-transaction-btn').addEventListener('click', function () {
        const outletName = document.getElementById('input-outlet-name').value.trim();
        const quantity = parseFloat(document.getElementById('input-quantity').value);
        const kgs = parseFloat(document.getElementById('input-kgs').value);

        if (outletName === '' || isNaN(quantity) || isNaN(kgs)) {
            alert('All fields are required and must be valid numbers.');
            return;
        }

        // Create a new transaction object
        const transaction = {
            drNo: drCounter,
            outletName: outletName,
            quantity: quantity,
            kgs: kgs
        };
        drCounter++;

        // Add transaction to the array
        transactions.push(transaction);

        // Update the transaction table
        updateTransactionsTable();

        // Clear input fields
        document.getElementById('input-outlet-name').value = '';
        document.getElementById('input-quantity').value = '';
        document.getElementById('input-kgs').value = '';

        // Enable Next button
        document.getElementById('next-button').disabled = false;
    });

    function updateTransactionsTable() {
        const tbody = document.getElementById('transactions-body');
        tbody.innerHTML = ''; // Clear existing rows

        transactions.forEach((txn, index) => {
            const row = document.createElement('tr');

            row.innerHTML = `
                <td>${txn.drNo}</td>
                <td>${txn.outletName}</td>
                <td>${txn.quantity}</td>
                <td>${txn.kgs}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteTransaction(${index})">Delete</button>
                </td>
            `;
            tbody.appendChild(row);
        });

        // Update hidden input field
        document.getElementById('transactions-json').value = JSON.stringify(transactions);
    }

    function deleteTransaction(index) {
        transactions.splice(index, 1);
        updateTransactionsTable();

        // Disable Next button if no transactions
        if (transactions.length === 0) {
            document.getElementById('next-button').disabled = true;
        }
    }
</script>

<?php
include '../officer/footer.php';
$conn->close();
?>