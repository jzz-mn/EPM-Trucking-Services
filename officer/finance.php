<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';
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
                    Finance
                  </span>
                </li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>
    <?php
    // Include the database connection
    

    // Get the last ExpenseID
    $query = "SELECT ExpenseID FROM expenses ORDER BY ExpenseID DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $nextExpenseId = isset($row['ExpenseID']) ? $row['ExpenseID'] + 1 : 1;
    ?>
    <div class="widget-content searchable-container list">
      <h5 class="border-bottom py-2 px-4 mb-4">Finances</h5>
      <!-- Add Expense Modal -->


      <script>
        // Function to calculate and display fuel amount
        function calculateFuelAmount() {
          const liters = parseFloat(document.getElementById('liters').value) || 0;
          const unitPrice = parseFloat(document.getElementById('unitPrice').value) || 0;
          const fuelAmount = (liters * unitPrice).toFixed(2); // Calculating and keeping two decimal places
          document.getElementById('fuelAmount').value = fuelAmount; // Display in the Fuel Amount field
        }

        // Attach event listeners to the 'liters' and 'unitPrice' inputs to trigger calculation
        document.getElementById('liters').addEventListener('input', calculateFuelAmount);
        document.getElementById('unitPrice').addEventListener('input', calculateFuelAmount);
      </script>


      <!-- Edit Fuel Expenses -->
      <div class="modal fade" id="editFuelExpenseModal" tabindex="-1" role="dialog"
        aria-labelledby="editFuelExpenseModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header d-flex align-items-center bg-primary">
              <h5 class="modal-title text-white fs-4">Edit Fuel Expense</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-12">
                  <div class="card w-100 border position-relative overflow-hidden mb-0">
                    <div class="card-body p-4">
                      <h4 class="card-title">Edit Fuel Expense</h4>
                      <p class="card-subtitle mb-4">Fill out the form to update an expense.</p>
                      <form id="updateExpenseForm" method="POST" action="update_expense.php">
                        <div class="row">
                          <!-- ExpenseID -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateExpenseID" class="form-label">Expense ID</label>
                              <input type="text" class="form-control" id="updateExpenseID" name="updateExpenseID"
                                readonly>
                            </div>
                          </div>
                          <!-- Date -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateDate" class="form-label">Date</label>
                              <input type="date" class="form-control" id="updateDate" name="updateDate">
                            </div>
                          </div>

                          <!-- Salary Amount -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateSalaryAmount" class="form-label">Salary Amount</label>
                              <input type="number" class="form-control" id="updateSalaryAmount"
                                name="updateSalaryAmount" step="0.01" oninput="computeTotalExpense()">
                            </div>
                          </div>
                          <!-- Mobile -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateMobile" class="form-label">Mobile Amount</label>
                              <input type="number" class="form-control" id="updateMobileAmount"
                                name="updateMobileAmount" step="0.01" oninput="computeTotalExpense()">
                            </div>
                          </div>
                          <!-- Other Amount -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="updateOtherAmount" class="form-label">Other Amount</label>
                              <input type="number" class="form-control" id="updateOtherAmount" name="updateOtherAmount"
                                step="0.01" oninput="computeTotalExpense()">
                            </div>
                          </div>

                          <!-- Total Amount -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="updateTotalExpense" class="form-label">Total Expense</label>
                              <input type="number" class="form-control" id="updateTotalExpense"
                                name="updateTotalExpense" step="0.01" readonly>
                            </div>
                          </div>

                          <div class="col-12">
                            <div class="d-flex align-items-center justify-content-end mt-4 gap-6">
                              <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
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
    </div>
    <div class="card">
      <div class="card-body p-0">
        <div class>
          <!-- Nav tabs -->
          <ul class="nav nav-tabs p-4 border-bottom" role="tablist">
            <li class="nav-item">
              <a class="nav-link active me-3" data-bs-toggle="tab" href="#home" role="tab">
                <span>Expenses</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="tab" href="#profile" role="tab">
                <span>Fuel</span>
              </a>
            </li>
          </ul>
          <!-- Tab panes -->
          <div class="tab-content p-4">
            <div class="tab-pane active" id="home" role="tabpanel">
              <div class="row mt-3">
                <div class="col-md-4 col-xl-3">
                  <form class="position-relative">
                    <input type="text" class="form-control product-search" id="input-search" placeholder="Search" />
                    <i class="ti position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                  </form>
                </div>
                <div
                  class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">

                </div>
              </div>
              <div class="py-3">
                <!-- Expense Table -->
                <div class="table-responsive">
                  <table id="" class="table text-center table-striped table-bordered text-nowrap align-middle">
                    <thead>
                      <!-- start row -->
                      <tr>
                        <th>ExpenseID</th>
                        <th>Date</th>
                        <th>SalaryAmount</th>
                        <th>MobileAmount</th>
                        <th>OtherAmount</th>
                        <th>TotalExpense</th>
                        <th>Action</th>
                      </tr>
                      <!-- end row -->
                    </thead>
                    <tbody>
                      <?php
                      // Include your database connection
                      include '../includes/db_connection.php';

                      // Fetch data from the expenses table
                      $query = "SELECT * FROM expenses";
                      $result = mysqli_query($conn, $query);

                      if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                          echo "<tr>";
                          echo "<td>" . $row['ExpenseID'] . "</td>";
                          echo "<td>" . $row['Date'] . "</td>";
                          echo "<td>" . $row['SalaryAmount'] . "</td>";
                          echo "<td>" . $row['MobileAmount'] . "</td>";
                          echo "<td>" . $row['OtherAmount'] . "</td>";
                          echo "<td>" . $row['TotalExpense'] . "</td>";
                          echo "<td>";
                          // Edit button
                          echo "<a href='#' class='me-3 text-primary' data-bs-toggle='modal' data-bs-target='#editFuelExpenseModal' onclick='populateExpenseEditForm(" . json_encode($row) . ");'>";
                          echo "<i class='fs-4 ti ti-edit'></i></a>";
                          // Delete button inside your table
                          echo "<a href='#' class='text-danger' onclick='openDeleteExpenseModal({$row['ExpenseID']});  return false;'>";
                          echo "</td>";
                          echo "</tr>";
                        }
                      } else {
                        echo "<tr><td colspan='10'>No records found</td></tr>";
                      }

                      // Close the database connection
                      mysqli_close($conn);
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="tab-pane py-3" id="profile" role="tabpanel">
              <div class="row mb-3">
                <div class="col-md-4 col-xl-3">
                  <form class="position-relative">
                    <input type="text" class="form-control product-search" id="input-search" placeholder="Search" />
                    <i class="ti position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                  </form>
                </div>
                <div
                  class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                </div>
              </div>

              <!-- Edit Fuel Modal -->
              <div class="modal fade" id="editFuelModal" tabindex="-1" role="dialog"
                aria-labelledby="editFuelModalTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header d-flex align-items-center bg-primary">
                      <h5 class="modal-title text-white fs-4">Edit Fuel Record</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="row">
                        <div class="col-12">
                          <div class="card w-100 border position-relative overflow-hidden mb-0">
                            <div class="card-body p-4">
                              <h4 class="card-title">Edit Fuel Record</h4>
                              <form id="updateFuelForm" method="POST" action="update_fuel.php">
                                <div class="row">
                                  <!-- Fuel ID -->
                                  <div class="col-lg-4">
                                    <div class="mb-3">
                                      <label for="updateFuelID" class="form-label">Fuel ID</label>
                                      <input type="text" class="form-control" id="updateFuelID" name="updateFuelID"
                                        readonly>
                                    </div>
                                  </div>
                                  <!-- Date -->
                                  <div class="col-lg-4">
                                    <div class="mb-3">
                                      <label for="updateDate" class="form-label">Date</label>
                                      <input type="date" class="form-control" id="updateDate" name="updateDate">
                                    </div>
                                  </div>
                                  <!-- Liters -->
                                  <div class="col-lg-4">
                                    <div class="mb-3">
                                      <label for="updateLiters" class="form-label">Liters</label>
                                      <input type="number" class="form-control" id="updateLiters" name="updateLiters"
                                        step="0.01" oninput="computeFuelAmount()">
                                    </div>
                                  </div>
                                  <!-- Unit Price -->
                                  <div class="col-lg-4">
                                    <div class="mb-3">
                                      <label for="updateUnitPrice" class="form-label">Unit Price</label>
                                      <input type="number" class="form-control" id="updateUnitPrice"
                                        name="updateUnitPrice" step="0.01" oninput="computeFuelAmount()">
                                    </div>
                                  </div>
                                  <!-- Fuel Type -->
                                  <div class="col-lg-4">
                                    <div class="mb-3">
                                      <label for="updateFuelType" class="form-label">Fuel Type</label>
                                      <select class="form-select" id="updateFuelType" name="updateFuelType">
                                        <option value="Diesel">Diesel</option>
                                        <option value="Gasoline">Gasoline</option>
                                      </select>
                                    </div>
                                  </div>
                                  <!-- Amount -->
                                  <div class="col-lg-6">
                                    <div class="mb-3">
                                      <label for="updateAmount" class="form-label">Amount</label>
                                      <input type="number" class="form-control" id="updateAmount" name="updateAmount"
                                        step="0.01" readonly>
                                    </div>
                                  </div>
                                  <div class="col-12">
                                    <div class="d-flex align-items-center justify-content-end mt-4 gap-6">
                                      <button class="btn bg-danger-subtle text-danger"
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
              <!-- Fuel Table -->
              <div class="table-responsive">
                <table id="" class="table text-center table-striped table-bordered display text-nowrap">
                  <thead>
                    <tr>
                      <th>FuelID</th>
                      <th>Date</th>
                      <th>Liters</th>
                      <th>Unit Price</th>
                      <th>Fuel Type</th>
                      <th>Amount</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    // Include the database connection
                    include '../includes/db_connection.php';

                    // Create the SQL query to fetch data from the fuel table
                    $sql = "SELECT FuelID, Date, Liters, UnitPrice, FuelType, Amount  FROM fuel";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                      // Loop through each row and display in the table
                      while ($row = $result->fetch_assoc()) {
                        // Prepare the row data as a JSON object
                        $fuelData = json_encode($row);

                        echo "<tr>";
                        echo "<td>" . $row['FuelID'] . "</td>";
                        echo "<td>" . $row['Date'] . "</td>";
                        echo "<td>" . $row['Liters'] . "</td>";
                        echo "<td>" . $row['UnitPrice'] . "</td>";
                        echo "<td>" . $row['FuelType'] . "</td>";
                        echo "<td>" . $row['Amount'] . "</td>";
                        echo "<td>";
                        // Edit button directly populates the modal using the row data
                        echo "<a href='#' class='me-3 text-primary' data-bs-toggle='modal' data-bs-target='#editFuelModal' onclick='populateEditForm($fuelData);'>";
                        echo "<i class='fs-4 ti ti-edit'></i></a>";
                        // Delete button (same as before)
                        echo "<a href='#' class='text-danger' onclick='openDeleteFuelModal({$row['FuelID']}); return false;'>";
                        echo "</td>";
                        echo "</tr>";
                      }
                    } else {
                      echo "<tr><td colspan='5'>No records found</td></tr>";
                    }

                    // Close the database connection
                    $conn->close();
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
  document.getElementById("nextExpenseID").value = "<?php echo $nextExpenseId; ?>";

  function populateExpenseEditForm(expense) {
    // Set values in the modal based on the selected expense row
    document.getElementById("updateExpenseID").value = expense.ExpenseID;
    document.getElementById("updateDate").value = expense.Date;
    document.getElementById("updateSalaryAmount").value = expense.SalaryAmount;
    document.getElementById("updateMobileAmount").value = expense.MobileAmount;
    document.getElementById("updateOtherAmount").value = expense.OtherAmount;
    document.getElementById("updateTotalExpense").value = expense.TotalExpense;
  }

  function computeTotalExpense() {
    // Get the values from the input fields
    const salaryAmount = parseFloat(document.getElementById("updateSalaryAmount").value) || 0;
    const mobileAmount = parseFloat(document.getElementById("updateMobileAmount").value) || 0;
    const otherAmount = parseFloat(document.getElementById("updateOtherAmount").value) || 0;

    // Calculate the total amount
    const totalExpense = salaryAmount + mobileAmount + otherAmount;

    // Set the total amount in the totalAmount input field
    document.getElementById("updateTotalExpense").value = totalExpense.toFixed(2); // Rounds to 2 decimal places
  }

  function populateEditForm(fuel) {
    document.getElementById("updateFuelID").value = fuel.FuelID;
    document.getElementById("updateDate").value = fuel.Date;
    document.getElementById("updateLiters").value = fuel.Liters;
    document.getElementById("updateUnitPrice").value = fuel.UnitPrice;
    document.getElementById("updateFuelType").value = fuel.FuelType;
    document.getElementById("updateAmount").value = fuel.Amount;
  }

  function computeFuelAmount() {
    const liters = parseFloat(document.getElementById("updateLiters").value) || 0;
    const unitPrice = parseFloat(document.getElementById("updateUnitPrice").value) || 0;
    const amount = liters * unitPrice;
    document.getElementById("updateAmount").value = amount.toFixed(2); // Rounds to 2 decimal places
  }
  function populateEditForm(fuel) {
    // Set the form fields in the modal using the fuel data passed from the table row
    document.getElementById("updateFuelID").value = fuel.FuelID;
    document.getElementById("updateDate").value = fuel.Date;
    document.getElementById("updateLiters").value = fuel.Liters;
    document.getElementById("updateUnitPrice").value = fuel.UnitPrice;
    document.getElementById("updateFuelType").value = fuel.FuelType;
    document.getElementById("updateAmount").value = fuel.Amount;
  }
</script>


<?php
include '../officer/footer.php';
?>