<?php
session_start();
include '../includes/header.php';
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
                  <a class="text-muted text-decoration-none d-flex" href="../super-admin/home.php">
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
                            <input type="number" class="form-control" id="updateWeightKgs" name="weightKgs"
                              step="0.01" placeholder="Enter Weight">

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
                            <input type="number" class="form-control" id="maintenanceYear" name="maintenanceYear" placeholder="Enter Year" min="1900" max="2100" step="1">
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
                            <input type="text" class="form-control" id="maintenanceCategory" name="maintenanceCategory" placeholder="Enter Category">
                          </div>
                        </div>

                        <!-- Description -->
                        <div class="col-lg-4">
                          <div class="mb-3">
                            <label for="maintenanceDescription" class="form-label">Description</label>
                            <input type="text" class="form-control" id="maintenanceDescription" name="maintenanceDescription" placeholder="Enter Description">
                          </div>
                        </div>

                        <!-- Amount -->
                        <div class="col-lg-4">
                          <div class="mb-3">
                            <label for="maintenanceAmount" class="form-label">Amount</label>
                            <input type="number" class="form-control" id="maintenanceAmount" name="maintenanceAmount" placeholder="Enter Amount" oninput="computeMaintenanceAmount()">
                          </div>
                        </div>

                        <div class="col-12">
                          <div class="d-flex align-items-center justify-content-end mt-4 gap-6">
                            <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
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
    <div class="modal fade" id="editTransactionModal" tabindex="-1" role="dialog" aria-labelledby="editTransactionModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editTransactionModalLabel">Edit Transaction</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
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
                    <input type="text" class="form-control" id="drNo" name="drNo" required>
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

                <div class="col-12">
                  <div class="d-flex justify-content-end gap-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                  </div>
                </div>
              </div>
            </form>
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
          </ul>

          <!-- Single Tab Content Wrapper -->
          <div class="tab-content p-4">
            <!-- Maintenance Tab -->
            <div class="tab-pane active" id="maintenance" role="tabpanel">
              <div class="row mt-3">
                <div class="col-md-4 col-xl-3">
                  <form class="position-relative">
                    <input type="text" class="form-control product-search" id="input-search-maintenance" placeholder="Search" />
                  </form>
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
                  <table id="" class="table table-striped table-bordered display text-nowrap">
                    <thead>
                      <tr>
                        <th>Maintenance ID</th>
                        <th>Year</th>
                        <th>Month</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                          $maintenanceData = json_encode($row);
                          echo "<tr>";
                          echo "<td>" . $row['MaintenanceID'] . "</td>";
                          echo "<td>" . $row['Year'] . "</td>";
                          echo "<td>" . $row['Month'] . "</td>";
                          echo "<td>" . $row['Category'] . "</td>";
                          echo "<td>" . $row['Description'] . "</td>";
                          echo "<td>" . $row['Amount'] . "</td>";
                          echo "<td>";
                          echo "<a href='#' class='me-3 text-primary edit-maintenance-btn' data-bs-toggle='modal' data-maintenance='" . htmlspecialchars($maintenanceData) . "'>";
                          echo "<i class='fs-4 ti ti-edit'></i></a>";
                          echo "</td>";
                          echo "</tr>";
                        }
                      } else {
                        echo "<tr><td colspan='7'>No maintenance records found</td></tr>";
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
                <?php
                $conn->close();
                ?>
              </div>
            </div>

            <!-- Transactions Tab -->
            <div class="tab-pane" id="transactions" role="tabpanel">
              <div class="row mt-3">
                <div class="col-md-4 col-xl-3">
                  <form class="position-relative">
                    <input type="text" class="form-control product-search" id="input-search-transactions" placeholder="Search" />
                  </form>
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
                  <table id="" class="table table-striped table-bordered display text-nowrap">
                    <thead>
                      <tr>
                        <th>Transaction ID</th>
                        <th>Transaction Group ID</th>
                        <th>Transaction Date</th>
                        <th>DR No</th>
                        <th>Outlet Name</th>
                        <th>Qty</th>
                        <th>KGs</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      while ($row = $result->fetch_assoc()) {
                        $transactionData = json_encode($row);  // Prepare the row data as a JSON object

                        echo "<tr>";
                        echo "<td>" . $row['TransactionID'] . "</td>";
                        echo "<td>" . $row['TransactionGroupID'] . "</td>";
                        echo "<td>" . $row['TransactionDate'] . "</td>";
                        echo "<td>" . $row['DRno'] . "</td>";
                        echo "<td>" . $row['OutletName'] . "</td>";
                        echo "<td>" . $row['Qty'] . "</td>";
                        echo "<td>" . $row['KGs'] . "</td>";
                        echo "<td>";
                        // Add a data-attribute to store the row data and attach the edit button
                        echo "<a href='#' class='me-3 text-primary edit-transaction-btn' data-bs-toggle='modal' data-transaction='" . htmlspecialchars($transactionData) . "'>";
                        echo "<i class='fs-4 ti ti-edit'></i></a>";
                        echo "</td>";
                        echo "</tr>";
                      }

                      ?>
                    </tbody>
                  </table>
                </div>
                <?php
                $conn->close();
                ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>


    <script>
      // Function to populate the Edit Maintenance modal with the selected record data
      function populateEditMaintenanceForm(maintenance) {
        // Set values in the modal based on the selected maintenance row
        document.getElementById("maintenanceId").value = maintenance.MaintenanceID;
        document.getElementById("maintenanceYear").value = maintenance.Year; // Set year
        document.getElementById("maintenanceMonth").value = maintenance.Month; // Set month
        document.getElementById("maintenanceCategory").value = maintenance.Category;
        document.getElementById("maintenanceDescription").value = maintenance.Description;
        document.getElementById("maintenanceAmount").value = maintenance.Amount;
      }

      // Event listener for calculating and updating the maintenance amount if needed
      function computeMaintenanceAmount() {
        const amount = parseFloat(document.getElementById("maintenanceAmount").value) || 0;
        document.getElementById("maintenanceAmount").value = amount.toFixed(2); // Rounds to 2 decimal places
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
      // Function to populate the Edit Transaction modal with the selected record data
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




    <div class="offcanvas customizer offcanvas-end" tabindex="-1" id="offcanvasExample"
      aria-labelledby="offcanvasExampleLabel">
      <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
        <h4 class="offcanvas-title fw-semibold" id="offcanvasExampleLabel">
          Settings
        </h4>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body" data-simplebar style="height: calc(100vh - 80px)">
        <h6 class="fw-semibold fs-4 mb-2">Theme</h6>

        <div class="d-flex flex-row gap-3 customizer-box" role="group">
          <input type="radio" class="btn-check light-layout" name="theme-layout" id="light-layout" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2" for="light-layout">
            <i class="icon ti ti-brightness-up fs-7 me-2"></i>Light
          </label>

          <input type="radio" class="btn-check dark-layout" name="theme-layout" id="dark-layout" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2" for="dark-layout">
            <i class="icon ti ti-moon fs-7 me-2"></i>Dark
          </label>
        </div>

        <h6 class="mt-5 fw-semibold fs-4 mb-2">Theme Direction</h6>
        <div class="d-flex flex-row gap-3 customizer-box" role="group">
          <input type="radio" class="btn-check" name="direction-l" id="ltr-layout" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2" for="ltr-layout">
            <i class="icon ti ti-text-direction-ltr fs-7 me-2"></i>LTR
          </label>

          <input type="radio" class="btn-check" name="direction-l" id="rtl-layout" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2" for="rtl-layout">
            <i class="icon ti ti-text-direction-rtl fs-7 me-2"></i>RTL
          </label>
        </div>

        <h6 class="mt-5 fw-semibold fs-4 mb-2">Theme Colors</h6>

        <div class="d-flex flex-row flex-wrap gap-3 customizer-box color-pallete" role="group">
          <input type="radio" class="btn-check" name="color-theme-layout" id="Blue_Theme" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
            onclick="handleColorTheme('Blue_Theme')" for="Blue_Theme" data-bs-toggle="tooltip" data-bs-placement="top"
            data-bs-title="BLUE_THEME">
            <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-1">
              <i class="ti ti-check text-white d-flex icon fs-5"></i>
            </div>
          </label>

          <input type="radio" class="btn-check" name="color-theme-layout" id="Aqua_Theme" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
            onclick="handleColorTheme('Aqua_Theme')" for="Aqua_Theme" data-bs-toggle="tooltip" data-bs-placement="top"
            data-bs-title="AQUA_THEME">
            <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-2">
              <i class="ti ti-check text-white d-flex icon fs-5"></i>
            </div>
          </label>

          <input type="radio" class="btn-check" name="color-theme-layout" id="Purple_Theme" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
            onclick="handleColorTheme('Purple_Theme')" for="Purple_Theme" data-bs-toggle="tooltip" data-bs-placement="top"
            data-bs-title="PURPLE_THEME">
            <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-3">
              <i class="ti ti-check text-white d-flex icon fs-5"></i>
            </div>
          </label>

          <input type="radio" class="btn-check" name="color-theme-layout" id="green-theme-layout" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
            onclick="handleColorTheme('Green_Theme')" for="green-theme-layout" data-bs-toggle="tooltip"
            data-bs-placement="top" data-bs-title="GREEN_THEME">
            <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-4">
              <i class="ti ti-check text-white d-flex icon fs-5"></i>
            </div>
          </label>

          <input type="radio" class="btn-check" name="color-theme-layout" id="cyan-theme-layout" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
            onclick="handleColorTheme('Cyan_Theme')" for="cyan-theme-layout" data-bs-toggle="tooltip"
            data-bs-placement="top" data-bs-title="CYAN_THEME">
            <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-5">
              <i class="ti ti-check text-white d-flex icon fs-5"></i>
            </div>
          </label>

          <input type="radio" class="btn-check" name="color-theme-layout" id="orange-theme-layout" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
            onclick="handleColorTheme('Orange_Theme')" for="orange-theme-layout" data-bs-toggle="tooltip"
            data-bs-placement="top" data-bs-title="ORANGE_THEME">
            <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-6">
              <i class="ti ti-check text-white d-flex icon fs-5"></i>
            </div>
          </label>
        </div>

        <h6 class="mt-5 fw-semibold fs-4 mb-2">Layout Type</h6>
        <div class="d-flex flex-row gap-3 customizer-box" role="group">
          <div>
            <input type="radio" class="btn-check" name="page-layout" id="vertical-layout" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary rounded-2" for="vertical-layout">
              <i class="icon ti ti-layout-sidebar-right fs-7 me-2"></i>Vertical
            </label>
          </div>
          <div>
            <input type="radio" class="btn-check" name="page-layout" id="horizontal-layout" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary rounded-2" for="horizontal-layout">
              <i class="icon ti ti-layout-navbar fs-7 me-2"></i>Horizontal
            </label>
          </div>
        </div>

        <h6 class="mt-5 fw-semibold fs-4 mb-2">Container Option</h6>

        <div class="d-flex flex-row gap-3 customizer-box" role="group">
          <input type="radio" class="btn-check" name="layout" id="boxed-layout" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2" for="boxed-layout">
            <i class="icon ti ti-layout-distribute-vertical fs-7 me-2"></i>Boxed
          </label>

          <input type="radio" class="btn-check" name="layout" id="full-layout" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2" for="full-layout">
            <i class="icon ti ti-layout-distribute-horizontal fs-7 me-2"></i>Full
          </label>
        </div>

        <h6 class="fw-semibold fs-4 mb-2 mt-5">Sidebar Type</h6>
        <div class="d-flex flex-row gap-3 customizer-box" role="group">
          <a href="javascript:void(0)" class="fullsidebar">
            <input type="radio" class="btn-check" name="sidebar-type" id="full-sidebar" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary rounded-2" for="full-sidebar">
              <i class="icon ti ti-layout-sidebar-right fs-7 me-2"></i>Full
            </label>
          </a>
          <div>
            <input type="radio" class="btn-check" name="sidebar-type" id="mini-sidebar" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary rounded-2" for="mini-sidebar">
              <i class="icon ti ti-layout-sidebar fs-7 me-2"></i>Collapse
            </label>
          </div>
        </div>

        <h6 class="mt-5 fw-semibold fs-4 mb-2">Card With</h6>

        <div class="d-flex flex-row gap-3 customizer-box" role="group">
          <input type="radio" class="btn-check" name="card-layout" id="card-with-border" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2" for="card-with-border">
            <i class="icon ti ti-border-outer fs-7 me-2"></i>Border
          </label>

          <input type="radio" class="btn-check" name="card-layout" id="card-without-border" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2" for="card-without-border">
            <i class="icon ti ti-border-none fs-7 me-2"></i>Shadow
          </label>
        </div>
      </div>
    </div>

    <script>
      function handleColorTheme(e) {
        document.documentElement.setAttribute("data-color-theme", e);
      }
    </script>
  </div>

  <!--  Search Bar -->
  <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-bottom">
          <input type="search" class="form-control" placeholder="Search here" id="search" />
          <a href="javascript:void(0)" data-bs-dismiss="modal" class="lh-1">
            <i class="ti ti-x fs-5 ms-3"></i>
          </a>
        </div>
        <div class="modal-body message-body" data-simplebar="">
          <h5 class="mb-0 fs-5 p-1">Quick Page Links</h5>
          <ul class="list mb-0 py-2">
            <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
              <a href="javascript:void(0)">
                <span class="text-dark fw-semibold d-block">Analytics</span>
                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard1</span>
              </a>
            </li>
            <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
              <a href="javascript:void(0)">
                <span class="text-dark fw-semibold d-block">eCommerce</span>
                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard2</span>
              </a>
            </li>
            <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
              <a href="javascript:void(0)">
                <span class="text-dark fw-semibold d-block">CRM</span>
                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard3</span>
              </a>
            </li>
            <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
              <a href="javascript:void(0)">
                <span class="text-dark fw-semibold d-block">Contacts</span>
                <span class="fs-2 d-block text-body-secondary">/apps/contacts</span>
              </a>
            </li>
            <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
              <a href="javascript:void(0)">
                <span class="text-dark fw-semibold d-block">Posts</span>
                <span class="fs-2 d-block text-body-secondary">/apps/blog/posts</span>
              </a>
            </li>
            <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
              <a href="javascript:void(0)">
                <span class="text-dark fw-semibold d-block">Detail</span>
                <span
                  class="fs-2 d-block text-body-secondary">/apps/blog/detail/streaming-video-way-before-it-was-cool-go-dark-tomorrow</span>
              </a>
            </li>
            <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
              <a href="javascript:void(0)">
                <span class="text-dark fw-semibold d-block">Shop</span>
                <span class="fs-2 d-block text-body-secondary">/apps/ecommerce/shop</span>
              </a>
            </li>
            <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
              <a href="javascript:void(0)">
                <span class="text-dark fw-semibold d-block">Modern</span>
                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard1</span>
              </a>
            </li>
            <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
              <a href="javascript:void(0)">
                <span class="text-dark fw-semibold d-block">Dashboard</span>
                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard2</span>
              </a>
            </li>
            <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
              <a href="javascript:void(0)">
                <span class="text-dark fw-semibold d-block">Contacts</span>
                <span class="fs-2 d-block text-body-secondary">/apps/contacts</span>
              </a>
            </li>
            <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
              <a href="javascript:void(0)">
                <span class="text-dark fw-semibold d-block">Posts</span>
                <span class="fs-2 d-block text-body-secondary">/apps/blog/posts</span>
              </a>
            </li>
            <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
              <a href="javascript:void(0)">
                <span class="text-dark fw-semibold d-block">Detail</span>
                <span
                  class="fs-2 d-block text-body-secondary">/apps/blog/detail/streaming-video-way-before-it-was-cool-go-dark-tomorrow</span>
              </a>
            </li>
            <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
              <a href="javascript:void(0)">
                <span class="text-dark fw-semibold d-block">Shop</span>
                <span class="fs-2 d-block text-body-secondary">/apps/ecommerce/shop</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>


</div>
<div class="dark-transparent sidebartoggler"></div>
<script src="../assets/js/vendor.min.js"></script>
<!-- Import Js Files -->
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/libs/simplebar/dist/simplebar.min.js"></script>
<script src="../assets/js/theme/app.init.js"></script>
<script src="../assets/js/theme/theme.js"></script>
<script src="../assets/js/theme/app.min.js"></script>
<script src="../assets/js/theme/sidebarmenu-default.js"></script>

<!-- solar icons -->
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
<script src="../assets/libs/fullcalendar/index.global.min.js"></script>
<script src="../assets/js/apps/contact.js"></script>
<script>
  function loadTransactionData(transactionID) {
    // Make an AJAX request to fetch transaction data
    $.ajax({
      url: 'get_transaction_data.php', // Create this PHP file to return transaction data
      method: 'POST',
      data: {
        transactionID: transactionID
      },
      dataType: 'json',
      success: function(response) {
        // Populate the modal fields with the response data
        $('#updateTransactionID').val(response.TransactionID);
        $('#updateTransactionDate').val(response.Date);
        $('#updateInvoiceID').val(response.InvoiceID);
        $('#updatePlateNumber').val(response.PlateNumber);
        $('#updateDRNumber').val(response.DRNumber);
        $('#updateSourceCustomerCode').val(response.SourceCustomerCode);
        $('#updateCustomerName').val(response.CustomerName);
        $('#updateDestinationCustomerCode').val(response.DestinationCustomerCode);
        $('#updateQuantityQtl').val(response.Qty);
        $('#updateWeightKgs').val(response.Kgs);
        $('#updateExpenseID').val(response.ExpenseID);
      }
    });
  }
</script>
</body>

</html>