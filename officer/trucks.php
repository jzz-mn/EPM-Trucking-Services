<?php
session_start();
include '../officer/header.php';
?>

<div class="body-wrapper">
  <div class="container-fluid">
    <div class="card card-body py-3">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="d-sm-flex align-items-center justify-space-between">
            <h4 class="mb-4 mb-sm-0 card-title">Records</h4>
            <nav aria-label="breadcrumb" class="ms-auto">
              <ol class="breadcrumb">
                <li class="breadcrumb-item d-flex align-items-center">
                  <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                  </a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                  <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                    Trucks
                  </span>
                </li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Transaction Modal -->
    <div class="modal fade" id="updateTransactionModal" tabindex="-1" role="dialog"
      aria-labelledby="updateTransactionModalTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header d-flex align-items-center bg-primary">
            <h5 class="modal-title text-white fs-4">Edit Transaction</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-12">
                <div class="card w-100 border position-relative overflow-hidden mb-0">
                  <div class="card-body p-4">
                    <h4 class="card-title">Edit Transaction</h4>
                    <p class="card-subtitle mb-4">Fill out the details to update the transaction.</p>
                    <form action="update_transaction.php" method="POST">
                      <div class="row">
                        <!-- Transaction ID (Read-only) -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateTransactionID" class="form-label">Transaction ID</label>
                            <input type="text" class="form-control" id="updateTransactionID" name="transactionID"
                              readonly>
                          </div>
                        </div>


                        <!-- Date -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateTransactionDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="updateTransactionDate" name="transactionDate">
                          </div>
                        </div>

                        <!-- Billing Invoice Number (Dropdown) -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateInvoiceID" class="form-label">Billing Invoice Number</label>
                            <select class="form-select" id="updateInvoiceID" name="invoiceID">
                              <option value="" disabled selected>Select Billing Invoice Number</option>
                              <?php
                              // Populate the dropdown as before
                              include '../includes/db_connection.php';
                              $invoiceQuery = "SELECT DISTINCT BillingInvoiceNo FROM invoices ORDER BY BillingInvoiceNo DESC";
                              $invoiceResult = mysqli_query($conn, $invoiceQuery);
                              while ($row = mysqli_fetch_assoc($invoiceResult)) {
                                echo "<option value='{$row['BillingInvoiceNo']}'>{$row['BillingInvoiceNo']}</option>";
                              }
                              ?>
                            </select>
                          </div>
                        </div>

                        <!-- Expense ID (Dropdown) -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateExpenseID" class="form-label">Expense ID</label>
                            <select class="form-select" id="updateExpenseID" name="expenseID">
                              <option value="" disabled selected>Select Expense ID</option>
                              <?php
                              $expenseQuery = "SELECT ExpenseID FROM expenses ORDER BY ExpenseID DESC";
                              $expenseResult = mysqli_query($conn, $expenseQuery);
                              while ($row = mysqli_fetch_assoc($expenseResult)) {
                                echo "<option value='{$row['ExpenseID']}'>{$row['ExpenseID']}</option>";
                              }
                              ?>
                            </select>
                          </div>
                        </div>

                        <!-- Plate Number -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updatePlateNumber" class="form-label">Plate Number</label>
                            <input type="text" class="form-control" id="updatePlateNumber" name="plateNumber"
                              placeholder="Enter Plate Number">
                          </div>
                        </div>

                        <!-- DR Number -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateDRNumber" class="form-label">DR Number</label>
                            <input type="text" class="form-control" id="updateDRNumber" name="drNumber"
                              placeholder="Enter DR Number">
                          </div>
                        </div>

                        <!-- Source Customer Code -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateSourceCustomerCode" class="form-label">Source Customer Code</label>
                            <input type="text" class="form-control" id="updateSourceCustomerCode"
                              name="sourceCustomerCode" placeholder="Enter Source Customer Code">
                          </div>
                        </div>

                        <!-- Customer Name -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateCustomerName" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="updateCustomerName" name="customerName"
                              placeholder="Enter Customer Name">
                          </div>
                        </div>

                        <!-- Destination Customer Code -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateDestinationCustomerCode" class="form-label">Destination Customer
                              Code</label>
                            <input type="text" class="form-control" id="updateDestinationCustomerCode"
                              name="destinationCustomerCode" placeholder="Enter Destination Customer Code">
                          </div>
                        </div>

                        <!-- Quantity -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateQuantityQtl" class="form-label">Quantity (Qtl)</label>
                            <input type="number" class="form-control" id="updateQuantityQtl" name="quantityQtl"
                              step="0.01" placeholder="Enter Quantity">
                          </div>
                        </div>

                        <!-- Weight (Kgs) -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateWeightKgs" class="form-label">Weight (Kgs)</label>
                            <input type="number" class="form-control" id="updateWeightKgs" name="weightKgs" step="0.01"
                              placeholder="Enter Weight">

                          </div>
                        </div>

                        <!-- Submit and Cancel Buttons -->
                        <div class="col-12">
                          <div class="d-flex align-items-center justify-content-end mt-4 gap-6">
                            <button type="button" class="btn bg-danger-subtle text-danger"
                              data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                          </div>
                        </div>
                      </div> <!-- End of row -->
                    </form> <!-- End of form -->
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Edit Maintenance Modal -->
    <div class="modal fade" id="updateMaintenanceRecordModal" tabindex="-1" role="dialog"
      aria-labelledby="updateMaintenanceRecordModalTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header d-flex align-items-center bg-primary">
            <h5 class="modal-title text-white fs-4">Edit Maintenance Record</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-12">
                <div class="card w-100 border position-relative overflow-hidden mb-0">
                  <div class="card-body p-4">
                    <h4 class="card-title">Edit Maintenance Record</h4>
                    <p class="card-subtitle mb-4">Fill out the form to record a maintenance expense.</p>

                    <!-- Place the updated form here -->
                    <form method="POST" action="update_maintenance.php">
                      <div class="row">
                        <!-- Maintenance ID -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="maintenanceId" class="form-label">Maintenance ID</label>
                            <input type="text" class="form-control" id="maintenanceId" name="maintenanceId" readonly>
                          </div>
                        </div>

                        <!-- Year -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="maintenanceYear" class="form-label">Year</label>
                            <input type="number" class="form-control" id="maintenanceYear" name="maintenanceYear"
                              placeholder="Enter Year" min="1900" max="2100" step="1">
                          </div>
                        </div>

                        <!-- Month -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="maintenanceMonth" class="form-label">Month</label>
                            <select class="form-select" id="maintenanceMonth" name="maintenanceMonth">
                              <option value="">Select Month</option>
                              <option value="1">January</option>
                              <option value="2">February</option>
                              <option value="3">March</option>
                              <option value="4">April</option>
                              <option value="5">May</option>
                              <option value="6">June</option>
                              <option value="7">July</option>
                              <option value="8">August</option>
                              <option value="9">September</option>
                              <option value="10">October</option>
                              <option value="11">November</option>
                              <option value="12">December</option>
                            </select>
                          </div>
                        </div>

                        <!-- Category -->
                        <div class="col-lg-4">
                          <div class="mb-3">
                            <label for="maintenanceCategory" class="form-label">Category</label>
                            <input type="text" class="form-control" id="maintenanceCategory" name="maintenanceCategory"
                              placeholder="Enter Category">
                          </div>
                        </div>

                        <!-- Description -->
                        <div class="col-lg-4">
                          <div class="mb-3">
                            <label for="maintenanceDescription" class="form-label">Description</label>
                            <input type="text" class="form-control" id="maintenanceDescription"
                              name="maintenanceDescription" placeholder="Enter Description">
                          </div>
                        </div>

                        <!-- Amount -->
                        <div class="col-lg-4">
                          <div class="mb-3">
                            <label for="maintenanceAmount" class="form-label">Amount</label>
                            <input type="number" class="form-control" id="maintenanceAmount" name="maintenanceAmount"
                              placeholder="Enter Amount" step="0.01" min="0" max="1000000000000">
                          </div>
                        </div>

                        <div class="col-12">
                          <div class="d-flex align-items-center justify-content-end mt-4 gap-6">
                            <button type="button" class="btn bg-danger-subtle text-danger"
                              data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                          </div>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Transaction Modal -->
    <div class="modal fade" id="editTransactionModal" tabindex="-1" role="dialog"
      aria-labelledby="editTransactionModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header d-flex align-items-center bg-primary">
            <h5 class="modal-title text-white fs-4" id="editTransactionModalLabel">Edit Transaction</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-12">
                <div class="card w-100 border position-relative overflow-hidden mb-0">
                  <div class="card-body p-4">
                    <h4 class="card-title">Edit Transaction</h4>
                    <p class="card-subtitle mb-4">Fill out the form to update the transaction.</p>

                    <form id="updateTransactionForm" method="POST" action="update_transaction.php">
                      <div class="row">

                        <!-- Transaction ID (hidden) -->
                        <input type="hidden" id="transactionId" name="transactionId">

                        <!-- Transaction Date -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="transactionDate" class="form-label">Transaction Date</label>
                            <input type="date" class="form-control" id="transactionDate" name="transactionDate" required>
                          </div>
                        </div>

                        <!-- DR No -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="drNo" class="form-label">DR No</label>
                            <input type="text" class="form-control" id="drNo" name="drNo" readonly>
                          </div>
                        </div>

                        <!-- Outlet Name -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="outletName" class="form-label">Outlet Name</label>
                            <input type="text" class="form-control" id="outletName" name="outletName" required>
                          </div>
                        </div>

                        <!-- Quantity -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="qty" class="form-label">Qty</label>
                            <input type="number" class="form-control" id="qty" name="qty" required>
                          </div>
                        </div>

                        <!-- KGs -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="kgs" class="form-label">KGs</label>
                            <input type="number" class="form-control" id="kgs" name="kgs" required>
                          </div>
                        </div>

                        <!-- Submit and Cancel Buttons -->
                        <div class="col-12">
                          <div class="d-flex justify-content-end mt-4 gap-3">
                            <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                          </div>
                        </div>
                      </div> <!-- End of row -->
                    </form> <!-- End of form -->

                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="updateTrucksRecordModal" tabindex="-1" role="dialog"
      aria-labelledby="updateTrucksRecordModalTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header d-flex align-items-center bg-primary">
            <h5 class="modal-title text-white fs-4">Edit Trucks Record</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-12">
                <div class="card w-100 border position-relative overflow-hidden mb-0">
                  <div class="card-body p-4">
                    <h4 class="card-title">Edit Trucks Record</h4>
                    <p class="card-subtitle mb-4">Fill out the form to record a truck information.</p>

                    <!-- Place the updated form here -->
                    <form method="POST" action="edit_truck.php">
                      <div class="row">
                        <!-- Maintenance ID -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="maintenanceId" class="form-label">Truck ID</label>
                            <input type="text" class="form-control" id="truckId" name="truckId" readonly>
                          </div>
                        </div>

                        <!-- Plate Number -->
                        <div class="col-lg-4">
                          <div class="mb-3">
                            <label for="plateNumber" class="form-label">Plate Number</label>
                            <input type="text" class="form-control" id="plateNumber" name="plateNumber"
                              placeholder="Enter Plate Number">
                          </div>
                        </div>

                        <!-- Truck Brand -->
                        <div class="col-lg-4">
                          <div class="mb-3">
                            <label for="truckBrand" class="form-label">Truck Brand</label>
                            <input type="text" class="form-control" id="truckBrand"
                              name="truckBrand" placeholder="Enter Truck Brand">
                          </div>
                        </div>
                        <!-- Submit and Cancel Buttons -->
                        <div class="col-12">
                          <div class="d-flex justify-content-end mt-4 gap-3">
                            <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                          </div>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>





    <h5 class="border-bottom py-2 px-4 mb-4">Trucks</h5>
    <div class="card">
      <div class="card-body p-0">
        <div class>
          <!-- Nav tabs -->
          <ul class="nav nav-tabs p-4 border-bottom" role="tablist">
            <li class="nav-item">
              <a class="nav-link active me-3" data-bs-toggle="tab" href="#maintenance" role="tab">
                <span>Maintenance</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="tab" href="#transactions" role="tab">
                <span>Transactions</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="tab" href="#trucks" role="tab">
                <span>Trucks</span>
              </a>
            </li>
          </ul>

          <!-- Transactions Tab -->


          <!-- Single Tab Content Wrapper -->
          <div class="tab-content p-4">
            <!-- Maintenance Tab -->
            <div class="tab-pane active" id="maintenance" role="tabpanel">
              <div class="table-controls mb-3">
                <div class="row align-items-center">
                  <div class="col-md-4">
                    <input type="text" id="maintenanceSearchBar" class="form-control" placeholder="Search..." onkeyup="filterMaintenanceTable()">
                  </div>
                  <div class="col-md-4 offset-md-4 text-end">
                    <select id="maintenanceRowsPerPage" class="form-select w-auto d-inline" onchange="changeMaintenanceRowsPerPage()">
                      <option value="5">5 rows</option>
                      <option value="10">10 rows</option>
                      <option value="20">20 rows</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="py-3">
                <!-- Maintenance Table -->
                <?php
                include '../includes/db_connection.php';
                $query = "SELECT MaintenanceID, Year, Month, Category, Description, Amount FROM truckmaintenance";
                $result = $conn->query($query);
                ?>
                <div class="table-responsive">
                  <table id="maintenanceTable" class="table text-center table-striped table-bordered display text-nowrap">
                    <thead>
                      <tr>
                        <th onclick="sortMaintenanceTable(0)">Maintenance ID</th>
                        <th onclick="sortMaintenanceTable(1)">Year</th>
                        <th onclick="sortMaintenanceTable(2)">Month</th>
                        <th onclick="sortMaintenanceTable(3)">Category</th>
                        <th onclick="sortMaintenanceTable(4)">Description</th>
                        <th onclick="sortMaintenanceTable(5)">Amount</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody id="maintenanceTableBody">
                      <?php
                      if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                          $maintenanceData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                          echo "<tr>";
                          echo "<td>" . $row['MaintenanceID'] . "</td>";
                          echo "<td>" . $row['Year'] . "</td>";
                          echo "<td>" . $row['Month'] . "</td>";
                          echo "<td>" . $row['Category'] . "</td>";
                          echo "<td>" . $row['Description'] . "</td>";
                          echo "<td>" . $row['Amount'] . "</td>";
                          echo "<td>";
                          echo "<a href='#' class='me-3 text-primary edit-maintenance-btn' data-bs-toggle='modal' data-maintenance='" . $maintenanceData . "'>";
                          echo "<i class='fs-4 ti ti-edit'></i></a>";
                          echo "</td>";
                          echo "</tr>";
                        }
                      } else {
                        echo "<tr id='noMaintenanceDataRow'><td colspan='7' class='text-center'>No maintenance records found</td></tr>";
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
                <div class="pagination-controls d-flex justify-content-end align-items-center mt-3">
                  <button id="maintenancePrevBtn" class="btn btn-primary me-2" onclick="prevMaintenancePage()">Previous</button>

                  <!-- Pagination Numbers -->
                  <nav>
                    <ul class="pagination mb-0" id="maintenancePaginationNumbers"></ul>
                  </nav>

                  <button id="maintenanceNextBtn" class="btn btn-primary ms-2" onclick="nextMaintenancePage()">Next</button>
                </div>

                <?php $conn->close(); ?>
              </div>
            </div>


            <!-- Transactions Tab -->
            <div class="tab-pane" id="transactions" role="tabpanel">
              <div class="table-controls mb-3">
                <div class="row align-items-center">
                  <div class="col-md-4">
                    <input type="text" id="transactionsSearchBar" class="form-control" placeholder="Search..." onkeyup="filterTransactionsTable()" />
                  </div>
                  <div class="col-md-4 offset-md-4 text-end">
                    <select id="transactionsRowsPerPage" class="form-select w-auto d-inline" onchange="changeTransactionsRowsPerPage()">
                      <option value="5">5 rows</option>
                      <option value="10">10 rows</option>
                      <option value="20">20 rows</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="py-3">
                <!-- Transactions Table -->
                <?php
                include '../includes/db_connection.php';
                $query = "SELECT TransactionID, TransactionGroupID, TransactionDate, DRno, OutletName, Qty, KGs FROM transactions";
                $result = $conn->query($query);
                ?>
                <div class="table-responsive">
                  <table id="transactionsTable" class="table text-center table-striped table-bordered display text-nowrap">
                    <thead>
                      <tr>
                        <th onclick="sortTransactionsTable(0)">Transaction ID</th>
                        <th onclick="sortTransactionsTable(1)">Transaction Group ID</th>
                        <th onclick="sortTransactionsTable(2)">Transaction Date</th>
                        <th onclick="sortTransactionsTable(3)">DR No</th>
                        <th onclick="sortTransactionsTable(4)">Outlet Name</th>
                        <th onclick="sortTransactionsTable(5)">Qty</th>
                        <th onclick="sortTransactionsTable(6)">KGs</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody id="transactionsTableBody">
                      <?php
                      while ($row = $result->fetch_assoc()) {
                        $transactionData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        echo "<tr>";
                        echo "<td>" . $row['TransactionID'] . "</td>";
                        echo "<td>" . $row['TransactionGroupID'] . "</td>";
                        echo "<td>" . $row['TransactionDate'] . "</td>";
                        echo "<td>" . $row['DRno'] . "</td>";
                        echo "<td>" . $row['OutletName'] . "</td>";
                        echo "<td>" . $row['Qty'] . "</td>";
                        echo "<td>" . $row['KGs'] . "</td>";
                        echo "<td>";
                        echo "<a href='#' class='me-3 text-primary edit-transaction-btn' data-bs-toggle='modal' data-transaction='" . $transactionData . "'>";
                        echo "<i class='fs-4 ti ti-edit'></i></a>";
                        echo "</td>";
                        echo "</tr>";
                      }

                      ?>
                    </tbody>
                  </table>
                </div>

                <div class="pagination-controls d-flex justify-content-end align-items-center mt-3">
                  <button id="transactionsPrevBtn" class="btn btn-primary me-2" onclick="prevTransactionsPage()">Previous</button>

                  <!-- Pagination Numbers -->
                  <nav>
                    <ul class="pagination mb-0" id="transactionsPaginationNumbers"></ul>
                  </nav>

                  <button id="transactionsNextBtn" class="btn btn-primary ms-2" onclick="nextTransactionsPage()">Next</button>
                </div>

              </div>
            </div>

            <div class="tab-pane" id="trucks" role="tabpanel">
              <div class="table-controls mb-3">
                <div class="row align-items-center">
                  <div class="col-md-4">
                    <!-- Search Bar -->
                    <input type="text" id="trucksSearchBar" class="form-control" placeholder="Search..." onkeyup="filterTrucksTable()" />
                  </div>
                  <div class="col-md-4 offset-md-4 text-end">
                  </div>
                </div>
              </div>

              <!-- Trucks Table -->
              <?php
              include '../includes/db_connection.php';
              $query = "SELECT TruckID, PlateNo, TruckBrand FROM trucksinfo";
              $result = $conn->query($query);
              ?>
              <div class="py-3">
              <div class="table-responsive">
                <table id="trucksTable" class="table text-center table-striped table-bordered display text-nowrap">
                  <thead>
                    <tr>
                      <th onclick="sortTrucksTable(0)">Truck ID</th>
                      <th onclick="sortTrucksTable(1)">Plate Number</th>
                      <th onclick="sortTrucksTable(2)">Truck Brand</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody id="trucksTableBody">
                    <?php
                    if ($result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                        $truckData = json_encode($row);  // Prepare the row data as a JSON object
                        echo "<tr>";
                        echo "<td>" . $row['TruckID'] . "</td>";
                        echo "<td>" . $row['PlateNo'] . "</td>";
                        echo "<td>" . $row['TruckBrand'] . "</td>";
                        echo "<td>";
                        // Add a data-attribute to store the row data and attach the edit button
                        echo "<a href='#' class='me-3 text-primary edit-truck-btn' data-bs-toggle='modal' data-truck='" . htmlspecialchars($truckData) . "'>";
                        echo "<i class='fs-4 ti ti-edit'></i></a>";
                        echo "</td>";
                        echo "</tr>";
                      }
                    } else {
                      echo "<tr><td colspan='4' class='text-center'>No data available</td></tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        </div>

      </div>
    </div>
  </div>
  <script>
    // Function to populate the Edit Maintenance modal with the selected record data
    function populateEditMaintenanceForm(maintenance) {
      // Map month names to numbers
      const monthMap = {
        'January': '1',
        'February': '2',
        'March': '3',
        'April': '4',
        'May': '5',
        'June': '6',
        'July': '7',
        'August': '8',
        'September': '9',
        'October': '10',
        'November': '11',
        'December': '12'
      };

      let monthValue = maintenance.Month;

      // If the month is a name, map it to its numeric value
      if (isNaN(monthValue)) {
        monthValue = monthMap[monthValue];
      }

      // Set values in the modal based on the selected maintenance row
      document.getElementById("maintenanceId").value = maintenance.MaintenanceID;
      document.getElementById("maintenanceYear").value = maintenance.Year;
      document.getElementById("maintenanceMonth").value = monthValue; // Set month
      document.getElementById("maintenanceCategory").value = maintenance.Category;
      document.getElementById("maintenanceDescription").value = maintenance.Description;
      document.getElementById("maintenanceAmount").value = maintenance.Amount;
    }

    // Attach the 'populateEditMaintenanceForm' function to the edit button in your table
    function attachMaintenanceEditButtons() {
      const editButtons = document.querySelectorAll('.edit-maintenance-btn');
      editButtons.forEach(button => {
        button.addEventListener('click', function() {
          const maintenanceData = JSON.parse(this.dataset.maintenance); // Get data from data-attribute
          populateEditMaintenanceForm(maintenanceData); // Populate modal with maintenance data
          $('#updateMaintenanceRecordModal').modal('show'); // Show the modal
        });
      });
    }

    // Execute this function when the document is fully loaded
    document.addEventListener('DOMContentLoaded', attachMaintenanceEditButtons);
  </script>
  <script>
    // Function to populate the Edit Transaction modal with the selected record data1
    function populateEditTransactionForm(transaction) {
      // Set values in the modal based on the selected transaction
      document.getElementById("transactionId").value = transaction.TransactionID;
      document.getElementById("transactionDate").value = transaction.TransactionDate;
      document.getElementById("drNo").value = transaction.DRno;
      document.getElementById("outletName").value = transaction.OutletName;
      document.getElementById("qty").value = transaction.Qty;
      document.getElementById("kgs").value = transaction.KGs;
    }

    // Attach the 'populateEditTransactionForm' function to the edit button in your table
    function attachTransactionEditButtons() {
      const editButtons = document.querySelectorAll('.edit-transaction-btn');
      editButtons.forEach(button => {
        button.addEventListener('click', function() {
          const transactionData = JSON.parse(this.dataset.transaction); // Get data from the data attribute
          populateEditTransactionForm(transactionData); // Populate modal with transaction data
          $('#editTransactionModal').modal('show'); // Show the modal
        });
      });
    }

    // Execute this function when the document is fully loaded
    document.addEventListener('DOMContentLoaded', attachTransactionEditButtons);
  </script>
</div>
</div>

<script>
  // Listen for clicks on edit buttons
  document.querySelectorAll('.edit-truck-btn').forEach(button => {
    button.addEventListener('click', function(event) {
      event.preventDefault();

      // Get TruckID from the data-transaction attribute
      const truckId = JSON.parse(this.getAttribute('data-truck')).TruckID;

      // Send an AJAX request to fetch truck data
      fetch(`fetch_truck.php?truckId=${truckId}`)
        .then(response => response.json())
        .then(data => {
          if (!data.error) {
            // Prefill the form with the fetched data
            document.getElementById('truckId').value = data.TruckID;
            document.getElementById('plateNumber').value = data.PlateNo;
            document.getElementById('truckBrand').value = data.TruckBrand;

            // Open the modal
            const updateModal = new bootstrap.Modal(document.getElementById('updateTrucksRecordModal'));
            updateModal.show();
          } else {
            alert(data.error);
          }
        })
        .catch(error => {
          console.error('Error fetching truck data:', error);
        });
    });
  });
</script>


<!-- Maintenance Table -->
<script>
  // Sorting logic for Maintenance Table
  let maintenanceSortColumn = -1;
  let maintenanceSortAscending = true;

  function sortMaintenanceTable(columnIndex) {
    let table = document.getElementById("maintenanceTable");
    let rows = Array.from(table.getElementsByTagName("tr")).slice(1); // Skip header row
    maintenanceSortAscending = maintenanceSortColumn === columnIndex ? !maintenanceSortAscending : true; // Toggle sorting direction
    maintenanceSortColumn = columnIndex;

    // Determine if the column contains numeric values (e.g., Maintenance ID, Year, Amount)
    let isNumericColumn = (columnIndex === 0 || columnIndex === 1 || columnIndex === 5); // Numeric columns: MaintenanceID, Year, Amount

    let sortedRows = rows.sort((a, b) => {
      let aValue = a.getElementsByTagName("td")[columnIndex].innerText.trim();
      let bValue = b.getElementsByTagName("td")[columnIndex].innerText.trim();

      // Sort numerically if it's a numeric column
      if (isNumericColumn) {
        aValue = parseFloat(aValue) || 0;
        bValue = parseFloat(bValue) || 0;
        return maintenanceSortAscending ? aValue - bValue : bValue - aValue;
      }

      // Otherwise, sort alphabetically
      return maintenanceSortAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
    });

    // Clear and append sorted rows
    let tableBody = document.getElementById("maintenanceTableBody");
    tableBody.innerHTML = "";
    sortedRows.forEach(row => tableBody.appendChild(row));

    // Update the sorting icons
    updateMaintenanceSortIcons(columnIndex, maintenanceSortAscending);
  }

  function updateMaintenanceSortIcons(columnIndex, ascending) {
    const headers = document.querySelectorAll("#maintenanceTable th");
    headers.forEach((header, index) => {
      header.classList.remove('ascending', 'descending'); // Remove previous sorting class
      if (index === columnIndex) {
        header.classList.add(ascending ? 'ascending' : 'descending'); // Add ascending or descending class
      }
    });
  }
</script>

<!-- Transactions Table -->
<script>
  // Sorting logic for Transactions Table
  let transactionsSortColumn = -1;
  let transactionsSortAscending = true;

  function sortTransactionsTable(columnIndex) {
    let table = document.getElementById("transactionsTable");
    let rows = Array.from(table.getElementsByTagName("tr")).slice(1); // Skip header row
    transactionsSortAscending = transactionsSortColumn === columnIndex ? !transactionsSortAscending : true; // Toggle sorting direction
    transactionsSortColumn = columnIndex;

    // Determine if the column contains numeric values (e.g., TransactionID, TransactionGroupID, Qty, KGs)
    let isNumericColumn = (columnIndex === 0 || columnIndex === 1 || columnIndex === 5 || columnIndex === 6); // Numeric columns: TransactionID, TransactionGroupID, Qty, KGs

    let sortedRows = rows.sort((a, b) => {
      let aValue = a.getElementsByTagName("td")[columnIndex].innerText.trim();
      let bValue = b.getElementsByTagName("td")[columnIndex].innerText.trim();

      // Sort numerically if it's a numeric column
      if (isNumericColumn) {
        aValue = parseFloat(aValue) || 0;
        bValue = parseFloat(bValue) || 0;
        return transactionsSortAscending ? aValue - bValue : bValue - aValue;
      }

      // Otherwise, sort alphabetically
      return transactionsSortAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
    });

    // Clear and append sorted rows
    let tableBody = document.getElementById("transactionsTableBody");
    tableBody.innerHTML = "";
    sortedRows.forEach(row => tableBody.appendChild(row));

    // Update the sorting icons
    updateTransactionsSortIcons(columnIndex, transactionsSortAscending);
  }

  function updateTransactionsSortIcons(columnIndex, ascending) {
    const headers = document.querySelectorAll("#transactionsTable th");
    headers.forEach((header, index) => {
      header.classList.remove('ascending', 'descending'); // Remove previous sorting class
      if (index === columnIndex) {
        header.classList.add(ascending ? 'ascending' : 'descending'); // Add ascending or descending class
      }
    });
  }
</script>

<!-- Trucks Table -->
<script>
  // Sorting logic for Trucks Table
  let trucksSortColumn = -1;
  let trucksSortAscending = true;

  function sortTrucksTable(columnIndex) {
    let table = document.getElementById("trucksTable");
    let rows = Array.from(table.getElementsByTagName("tr")).slice(1); // Skip header row
    trucksSortAscending = trucksSortColumn === columnIndex ? !trucksSortAscending : true; // Toggle sorting direction
    trucksSortColumn = columnIndex;

    // Determine if the column contains numeric values (for sorting numerically)
    let isNumericColumn = columnIndex === 0; // Assume TruckID (columnIndex 0) is numeric

    let sortedRows = rows.sort((a, b) => {
      let aValue = a.getElementsByTagName("td")[columnIndex].innerText.trim();
      let bValue = b.getElementsByTagName("td")[columnIndex].innerText.trim();

      // Sort numerically if it's a numeric column
      if (isNumericColumn) {
        aValue = parseFloat(aValue) || 0;
        bValue = parseFloat(bValue) || 0;
        return trucksSortAscending ? aValue - bValue : bValue - aValue;
      }

      // Otherwise, sort alphabetically
      return trucksSortAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
    });

    // Clear and append sorted rows
    let tableBody = document.getElementById("trucksTableBody");
    tableBody.innerHTML = "";
    sortedRows.forEach(row => tableBody.appendChild(row));

    // Update the sorting icons
    updateTrucksSortIcons(columnIndex, trucksSortAscending);
  }

  function updateTrucksSortIcons(columnIndex, ascending) {
    const headers = document.querySelectorAll("#trucksTable th");
    headers.forEach((header, index) => {
      header.classList.remove('ascending', 'descending'); // Remove previous sorting class
      if (index === columnIndex) {
        header.classList.add(ascending ? 'ascending' : 'descending'); // Add ascending or descending class
      }
    });
  }
</script>


<script>
  function attachMaintenanceEditButtons() {
    const editButtons = document.querySelectorAll('.edit-maintenance-btn');
    editButtons.forEach(button => {
      button.addEventListener('click', function() {
        const maintenanceData = JSON.parse(this.dataset.maintenance);
        populateEditMaintenanceForm(maintenanceData);
        $('#updateMaintenanceRecordModal').modal('show');
      });
    });
  }

  function attachTransactionEditButtons() {
    const editButtons = document.querySelectorAll('.edit-transaction-btn');
    editButtons.forEach(button => {
      button.addEventListener('click', function() {
        const transactionData = JSON.parse(this.dataset.transaction);
        populateEditTransactionForm(transactionData);
        $('#editTransactionModal').modal('show');
      });
    });
  }
</script>

<script>
  let maintenanceCurrentPage = 1;
  let maintenanceRowsPerPage = 5;

  function changeMaintenanceRowsPerPage() {
    maintenanceRowsPerPage = parseInt(document.getElementById("maintenanceRowsPerPage").value);
    maintenanceCurrentPage = 1;
    updateMaintenanceTable();
  }

  function updateMaintenanceTable() {
    let table = document.getElementById("maintenanceTable");
    let rows = Array.from(table.getElementsByTagName("tr")).slice(1);
    let totalRows = rows.length;
    let totalPages = Math.ceil(totalRows / maintenanceRowsPerPage);

    let startIndex = (maintenanceCurrentPage - 1) * maintenanceRowsPerPage;
    let endIndex = startIndex + maintenanceRowsPerPage;

    rows.forEach((row, index) => {
      row.style.display = index >= startIndex && index < endIndex ? "" : "none";
    });

    // Update pagination buttons
    document.getElementById("maintenancePrevBtn").disabled = maintenanceCurrentPage === 1;
    document.getElementById("maintenanceNextBtn").disabled = maintenanceCurrentPage === totalPages;

    // Generate pagination numbers
    updateMaintenancePaginationNumbers(totalPages);
  }

  function updateMaintenancePaginationNumbers(totalPages) {
    const paginationNumbers = document.getElementById("maintenancePaginationNumbers");
    paginationNumbers.innerHTML = ''; // Clear existing numbers

    for (let i = 1; i <= totalPages; i++) {
      const pageItem = document.createElement("li");
      pageItem.classList.add("page-item");
      if (i === maintenanceCurrentPage) {
        pageItem.classList.add("active");
      }
      const pageLink = document.createElement("a");
      pageLink.classList.add("page-link");
      pageLink.textContent = i;
      pageLink.addEventListener('click', () => {
        maintenanceCurrentPage = i;
        updateMaintenanceTable();
      });

      pageItem.appendChild(pageLink);
      paginationNumbers.appendChild(pageItem);
    }
  }

  function nextMaintenancePage() {
    maintenanceCurrentPage++;
    updateMaintenanceTable();
  }

  function prevMaintenancePage() {
    maintenanceCurrentPage--;
    updateMaintenanceTable();
  }

  // Call this on page load
  document.addEventListener('DOMContentLoaded', updateMaintenanceTable);
</script>

<script>
  let transactionsCurrentPage = 1;
  let transactionsRowsPerPage = 5;

  function changeTransactionsRowsPerPage() {
    transactionsRowsPerPage = parseInt(document.getElementById("transactionsRowsPerPage").value);
    transactionsCurrentPage = 1;
    updateTransactionsTable();
  }

  function updateTransactionsTable() {
    let table = document.getElementById("transactionsTable");
    let rows = Array.from(table.getElementsByTagName("tr")).slice(1);
    let totalRows = rows.length;
    let totalPages = Math.ceil(totalRows / transactionsRowsPerPage);

    let startIndex = (transactionsCurrentPage - 1) * transactionsRowsPerPage;
    let endIndex = startIndex + transactionsRowsPerPage;

    rows.forEach((row, index) => {
      row.style.display = index >= startIndex && index < endIndex ? "" : "none";
    });

    // Update pagination buttons
    document.getElementById("transactionsPrevBtn").disabled = transactionsCurrentPage === 1;
    document.getElementById("transactionsNextBtn").disabled = transactionsCurrentPage === totalPages;

    // Generate pagination numbers
    updateTransactionsPaginationNumbers(totalPages);
  }

  function updateTransactionsPaginationNumbers(totalPages) {
    const paginationNumbers = document.getElementById("transactionsPaginationNumbers");
    paginationNumbers.innerHTML = ''; // Clear existing numbers

    for (let i = 1; i <= totalPages; i++) {
      const pageItem = document.createElement("li");
      pageItem.classList.add("page-item");
      if (i === transactionsCurrentPage) {
        pageItem.classList.add("active");
      }
      const pageLink = document.createElement("a");
      pageLink.classList.add("page-link");
      pageLink.textContent = i;
      pageLink.addEventListener('click', () => {
        transactionsCurrentPage = i;
        updateTransactionsTable();
      });

      pageItem.appendChild(pageLink);
      paginationNumbers.appendChild(pageItem);
    }
  }

  function nextTransactionsPage() {
    transactionsCurrentPage++;
    updateTransactionsTable();
  }

  function prevTransactionsPage() {
    transactionsCurrentPage--;
    updateTransactionsTable();
  }

  // Call this on page load
  document.addEventListener('DOMContentLoaded', updateTransactionsTable);
</script>


<script>
  function filterMaintenanceTable() {
    let input = document.getElementById("maintenanceSearchBar").value.toLowerCase();
    let table = document.getElementById("maintenanceTable");
    let rows = table.getElementsByTagName("tr");
    let noDataFound = true; // Flag to check if no row matches the search criteria

    for (let i = 1; i < rows.length; i++) { // Start from i=1 to skip the header row
      let row = rows[i];
      if (row.id !== "noMaintenanceDataRow") {
        row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";

        if (row.style.display === "") {
          noDataFound = false;
        }
      }
    }

    document.getElementById("noMaintenanceDataRow").style.display = noDataFound ? "" : "none";
    updateMaintenanceFilteredTable(filteredRows);
  }
</script>

<script>
  function filterTransactionsTable() {
    let input = document.getElementById("transactionsSearchBar").value.toLowerCase();
    let table = document.getElementById("transactionsTable");
    let rows = table.getElementsByTagName("tr");
    let noDataFound = true; // Flag to check if no row matches the search criteria

    for (let i = 1; i < rows.length; i++) { // Start from i=1 to skip the header row
      let row = rows[i];
      if (row.id !== "noTransactionsDataRow") {
        row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";

        if (row.style.display === "") {
          noDataFound = false;
        }
      }
    }

    document.getElementById("noTransactionsDataRow").style.display = noDataFound ? "" : "none";
  }
</script>

<script>
  function filterTrucksTable() {
    let input = document.getElementById("trucksSearchBar").value.toLowerCase();
    let table = document.getElementById("trucksTable");
    let rows = table.getElementsByTagName("tr");
    let noDataFound = true; // Flag to check if no row matches the search criteria

    for (let i = 1; i < rows.length; i++) { // Skip the header row (i = 1)
      let row = rows[i];
      if (row.id !== "noTrucksDataRow") { // Skip the "No data found" row
        row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";

        if (row.style.display === "") {
          noDataFound = false; // A match was found
        }
      }
    }

    // Toggle the visibility of the "No data found" row
    document.getElementById("noTrucksDataRow").style.display = noDataFound ? "" : "none";
  }
</script>


<style>
  th {
    cursor: pointer;
  }

  /* Add ascending and descending arrow icons */
  .ascending::after {
    content: ' ↑';
    /* Unicode up arrow */
  }

  .descending::after {
    content: ' ↓';
    /* Unicode down arrow */
  }
</style>



<?php
$conn->close();
include '../officer/footer.php';
?>