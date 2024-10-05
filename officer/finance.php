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
    include '../includes/db_connection.php';

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
                              <input type="number" class="form-control" id="updateSalaryAmount" name="updateSalaryAmount" step="0.01" oninput="computeTotalExpense()">
                            </div>
                          </div>
                          <!-- Toll Fee -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateTollFee" class="form-label">Toll Fee Amount</label>
                              <input type="number" class="form-control" id="updateTollFeeAmount" name="updateTollFeeAmount" step="0.01" oninput="computeTotalExpense()">
                              </div>
                          </div>
                          <!-- Mobile -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateMobile" class="form-label">Mobile Amount</label>
                              <input type="number" class="form-control" id="updateMobileAmount" name="updateMobileAmount" step="0.01" oninput="computeTotalExpense()">
                              </div>
                          </div>
                          <!-- Other Amount -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="updateOtherAmount" class="form-label">Other Amount</label>
                              <input type="number" class="form-control" id="updateOtherAmount" name="updateOtherAmount" step="0.01" oninput="computeTotalExpense()">
                            </div>
                          </div>
                          
                          <!-- Total Amount -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="updateTotalExpense" class="form-label">Total Expense</label>
                              <input type="number" class="form-control" id="updateTotalExpense" name="updateTotalExpense"
                                step="0.01" readonly>
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
                  <table id="" class="table table-striped table-bordered text-nowrap align-middle">
                    <thead>
                      <!-- start row -->
                      <tr>
                        <th>ExpenseID</th>
                        <th>Date</th>
                        <th>SalaryAmount</th>
                        <th>TollFeeAmount</th>
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
                          echo "<td>" . $row['TollFeeAmount'] . "</td>";
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
<div class="modal fade" id="editFuelModal" tabindex="-1" role="dialog" aria-labelledby="editFuelModalTitle" aria-hidden="true">
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
                        <input type="text" class="form-control" id="updateFuelID" name="updateFuelID" readonly>
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
                        <input type="number" class="form-control" id="updateLiters" name="updateLiters" step="0.01" oninput="computeFuelAmount()">
                      </div>
                    </div>
                    <!-- Unit Price -->
                    <div class="col-lg-4">
                      <div class="mb-3">
                        <label for="updateUnitPrice" class="form-label">Unit Price</label>
                        <input type="number" class="form-control" id="updateUnitPrice" name="updateUnitPrice" step="0.01" oninput="computeFuelAmount()">
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
                        <input type="number" class="form-control" id="updateAmount" name="updateAmount" step="0.01" readonly>
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


              <!-- Fuel Table -->
              <div class="table-responsive">
                <table id="" class="table table-striped table-bordered display text-nowrap">
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
</div>


<script>
  document.getElementById("nextExpenseID").value = "<?php echo $nextExpenseId; ?>";
</script>
<script>

function populateExpenseEditForm(expense) {
    // Set values in the modal based on the selected expense row
    document.getElementById("updateExpenseID").value = expense.ExpenseID;
    document.getElementById("updateDate").value = expense.Date;
    document.getElementById("updateSalaryAmount").value = expense.SalaryAmount;
    document.getElementById("updateTollFeeAmount").value = expense.TollFeeAmount;
    document.getElementById("updateMobileAmount").value = expense.MobileAmount;
    document.getElementById("updateOtherAmount").value = expense.OtherAmount;
    document.getElementById("updateTotalExpense").value = expense.TotalExpense;
}

  function computeTotalExpense() {
    // Get the values from the input fields
    const salaryAmount = parseFloat(document.getElementById("updateSalaryAmount").value) || 0;
    const tollFeeAmount = parseFloat(document.getElementById("updateTollFeeAmount").value) || 0;
    const mobileAmount = parseFloat(document.getElementById("updateMobileAmount").value) || 0;
    const otherAmount = parseFloat(document.getElementById("updateOtherAmount").value) || 0;

    // Calculate the total amount
    const totalExpense = salaryAmount + tollFeeAmount + mobileAmount + otherAmount;

    // Set the total amount in the totalAmount input field
    document.getElementById("updateTotalExpense").value = totalExpense.toFixed(2); // Rounds to 2 decimal places
  }


  </script>

  
<script>
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
<script src="../assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>


<script src="../assets/js/datatable/datatable-advanced.init.js"></script>

  
</body >

</html >