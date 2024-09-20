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
                  <a class="text-muted text-decoration-none d-flex" href="./">
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
      <div class="modal fade" id="addExpenseModal" tabindex="-1" role="dialog" aria-labelledby="addExpenseModalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header d-flex align-items-center bg-primary">
              <h5 class="modal-title text-white fs-4">Add Expense</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-12">
                  <div class="card w-100 border position-relative overflow-hidden mb-0">
                    <div class="card-body p-4">
                      <h4 class="card-title">Add Expenses</h4>
                      <p class="card-subtitle mb-4">Fill out the form to record an expense.</p>
                      <form id="addExpenseForm" method="POST" action="add_expense.php">
                        <div class="row">
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="nextExpenseID" class="form-label">Expense ID</label>
                              <input type="text" class="form-control" id="nextExpenseID" name="nextExpenseID" readonly>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="expenseDate" class="form-label">Date</label>
                              <input type="date" class="form-control" id="expenseDate" name="expenseDate">
                            </div>
                          </div>

                          <!-- Other input fields for amounts -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="tollFee" class="form-label">Toll Fee</label>
                              <input type="number" class="form-control" id="tollFee" name="tollFee"
                                oninput="calculateTotal()">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="rateAmount" class="form-label">Rate Amount</label>
                              <input type="number" class="form-control" id="rateAmount" name="rateAmount"
                                oninput="calculateTotal()">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="salaryAmount" class="form-label">Salary Amount</label>
                              <input type="number" class="form-control" id="salaryAmount" name="salaryAmount"
                                oninput="calculateTotal()">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="gasAmount" class="form-label">Gas Amount</label>
                              <input type="number" class="form-control" id="gasAmount" name="gasAmount"
                                oninput="calculateTotal()">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="allowanceAmount" class="form-label">Allowance Amount</label>
                              <input type="number" class="form-control" id="allowanceAmount" name="allowanceAmount"
                                oninput="calculateTotal()">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="extraMealAmount" class="form-label">Extra Meal Amount</label>
                              <input type="number" class="form-control" id="extraMealAmount" name="extraMealAmount"
                                oninput="calculateTotal()">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="mobileFee" class="form-label">Mobile Fee</label>
                              <input type="number" class="form-control" id="mobileFee" name="mobileFee"
                                oninput="calculateTotal()">
                            </div>
                          </div>

                          <!-- Total Amount Field -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="totalAmount" class="form-label">Total Amount</label>
                              <input type="number" class="form-control" id="totalAmount" name="totalAmount" readonly>
                            </div>
                          </div>

                          <div class="col-12 mb-3">
                            <div class="d-flex gap-6 m-0 justify-content-end">
                              <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Discard</button>
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

      <!-- Add Fuel Expenses -->
      <div class="modal fade" id="addFuelExpenseModal" tabindex="-1" role="dialog"
        aria-labelledby="addFuelExpenseModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header d-flex align-items-center bg-primary">
              <h5 class="modal-title text-white fs-4">Add Fuel Expense</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-12">
                  <div class="card w-100 border position-relative overflow-hidden mb-0">
                    <div class="card-body p-4">
                      <h4 class="card-title">Add Fuel Expense</h4>
                      <p class="card-subtitle mb-4">Fill out the form to record an expense.</p>
                      <form>
                        <div class="row">
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="dieselId" class="form-label">Diesel ID</label>
                              <input type="text" class="form-control" id="dieselId" placeholder="Enter Diesel ID">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="date" class="form-label">Date</label>
                              <input type="date" class="form-control" id="date" placeholder="Enter Date">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="controlNumber" class="form-label">Control Number</label>
                              <input type="text" class="form-control" id="controlNumber"
                                placeholder="Enter Control Number">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="plateNumber" class="form-label">Plate Number</label>
                              <input type="text" class="form-control" id="plateNumber" placeholder="Enter Plate Number">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="seriesNumber" class="form-label">Series Number</label>
                              <input type="text" class="form-control" id="seriesNumber"
                                placeholder="Enter Series Number">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="liters" class="form-label">Liters</label>
                              <input type="number" class="form-control" id="liters" placeholder="Enter Liters">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="unitPrice" class="form-label">Unit Price</label>
                              <input type="text" class="form-control" id="unitPrice" placeholder="Enter Unit Price">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="fuelAmount" class="form-label">Fuel Amount</label>
                              <input type="text" class="form-control" id="fuelAmount" placeholder="Enter Fuel Amount">
                            </div>
                          </div>
                          <div class="col-12">
                            <div class="d-flex align-items-center justify-content-end mt-4 gap-6">
                              <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
                              <button class="btn btn-primary">Save</button>
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
      <!-- Edit Fuel Expenses Modal -->
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
                          <!-- Toll Fee -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateTollFee" class="form-label">Toll Fee</label>
                              <input type="number" class="form-control" id="updateTollFee" name="updateTollFee"
                                step="0.01" oninput="computeTotalAmount()">
                            </div>
                          </div>
                          <!-- Rate Amount -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateRateAmount" class="form-label">Rate Amount</label>
                              <input type="number" class="form-control" id="updateRateAmount" name="updateRateAmount"
                                step="0.01" oninput="computeTotalAmount()">
                            </div>
                          </div>
                          <!-- Salary Amount -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateSalaryAmount" class="form-label">Salary Amount</label>
                              <input type="number" class="form-control" id="updateSalaryAmount"
                                name="updateSalaryAmount" step="0.01" oninput="computeTotalAmount()">
                            </div>
                          </div>
                          <!-- Gas Amount -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateGasAmount" class="form-label">Gas Amount</label>
                              <input type="number" class="form-control" id="updateGasAmount" name="updateGasAmount"
                                step="0.01" oninput="computeTotalAmount()">
                            </div>
                          </div>
                          <!-- Allowance Amount -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateAllowanceAmount" class="form-label">Allowance Amount</label>
                              <input type="number" class="form-control" id="updateAllowanceAmount"
                                name="updateAllowanceAmount" step="0.01" oninput="computeTotalAmount()">
                            </div>
                          </div>
                          <!-- Extra Meal Amount -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateExtraMealAmount" class="form-label">Extra Meal Amount</label>
                              <input type="number" class="form-control" id="updateExtraMealAmount"
                                name="updateExtraMealAmount" step="0.01" oninput="computeTotalAmount()">
                            </div>
                          </div>
                          <!-- Mobile -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateMobile" class="form-label">Mobile</label>
                              <input type="number" class="form-control" id="updateMobile" name="updateMobile"
                                step="0.01" oninput="computeTotalAmount()">
                            </div>
                          </div>
                          <!-- Total Amount -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="updateTotalAmount" class="form-label">Total Amount</label>
                              <input type="number" class="form-control" id="updateTotalAmount" name="updateTotalAmount"
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
                  <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
                    data-bs-target="#addExpenseModal">
                    <i class="ti ti-users text-white me-1 fs-5"></i> Add Expenses
                  </a>
                </div>
              </div>
              <div class="py-3">
                <div class="table-responsive">
                  <table id="" class="table table-striped table-bordered text-nowrap align-middle">
                    <thead>
                      <!-- start row -->
                      <tr>
                        <th>ExpenseID</th>
                        <th>Date</th>
                        <th>TollFee</th>
                        <th>RateAmount</th>
                        <th>TotalAmount</th>
                        <th>SalaryAmount</th>
                        <th>GasAmount</th>
                        <th>AllowanceAmount</th>
                        <th>ExtraMealAmount</th>
                        <th>Mobile</th>
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
                          echo "<td>" . $row['TollFee'] . "</td>";
                          echo "<td>" . $row['RateAmount'] . "</td>";
                          echo "<td>" . $row['TotalAmount'] . "</td>";
                          echo "<td>" . $row['SalaryAmount'] . "</td>";
                          echo "<td>" . $row['GasAmount'] . "</td>";
                          echo "<td>" . $row['AllowanceAmount'] . "</td>";
                          echo "<td>" . $row['ExtraMealAmount'] . "</td>";
                          echo "<td>" . $row['Mobile'] . "</td>";
                          echo "<td>";
                          // Edit button
                          echo "<a href='#' class='me-3 text-primary' data-bs-toggle='modal' data-bs-target='#editFuelExpenseModal' onclick='populateEditForm(" . json_encode($row) . ");'>";
                          echo "<i class='fs-4 ti ti-edit'></i></a>";
                          // Delete button inside your table
                          echo "<a href='#' class='text-danger' onclick='openDeleteExpenseModal({$row['ExpenseID']});  return false;'>";
                          echo "<i class='fs-4 ti ti-trash'></i></a>";
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



                <!-- Delete Expense Modal -->
                <div class="modal fade" id="deleteExpenseModal" tabindex="-1" role="dialog"
                  aria-labelledby="deleteExpenseModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                      <div class="modal-header bg-danger">
                        <h5 class="modal-title text-white" id="deleteExpenseModalLabel">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <p>Are you sure you want to delete this expense? This action cannot be undone.</p>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteExpenseBtn">Delete</button>
                      </div>
                    </div>
                  </div>
                </div>

                <script>
                  // Store the ID of the expense to delete
                  let expenseIDToDelete = null;

                  // Function to open the modal and pass the ExpenseID
                  function openDeleteExpenseModal(expenseID) {
                    expenseIDToDelete = expenseID; // Set the expense ID to delete
                    $('#deleteExpenseModal').modal('show'); // Display the modal
                  }

                  // Handle the delete button click inside the modal
                  document.getElementById('confirmDeleteExpenseBtn').addEventListener('click', function () {
                    if (expenseIDToDelete !== null) {
                      // Send AJAX request to delete the expense
                      fetch('delete_expense.php', {
                        method: 'POST',
                        headers: {
                          'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `id=${expenseIDToDelete}` // Send the expense ID as POST data
                      })
                        .then(response => response.json())
                        .then(data => {
                          if (data.success) {
                            // Hide the modal after successful deletion
                            $('#deleteExpenseModal').modal('hide');
                            // Reload the page or remove the deleted expense row
                            alert('Expense deleted successfully.');
                            location.reload(); // Reload to reflect changes
                          } else {
                            alert('Failed to delete expense: ' + data.message);
                          }
                        })
                        .catch(error => console.error('Error deleting expense:', error));
                    }
                  });
                </script>





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
                  <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
                    data-bs-target="#addFuelExpenseModal">
                    <i class="ti ti-users text-white me-1 fs-5"></i> Add Fuel Record
                  </a>
                </div>
              </div>
              <div class="table-responsive">
                <table id="" class="table table-striped table-bordered display text-nowrap">
                  <thead>
                    <!-- start row -->
                    <tr>
                      <th>Name</th>
                      <th>Position</th>
                      <th>Office</th>
                      <th>Age</th>
                      <th>Start date</th>
                      <th>Salary</th>
                    </tr>
                    <!-- end row -->
                  </thead>
                  <tbody></tbody>
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
<script src="../assets/libs/owl.carousel/dist/owl.carousel.min.js"></script>
<script src="../assets/js/apps/productDetail.js"></script>
<script src="../assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="../assets/js/datatable/datatable-basic.init.js"></script>
<script src="../assets/js/datatable/datatable-advanced.init.js"></script>
<script>
  document.getElementById("nextExpenseID").value = "<?php echo $nextExpenseId; ?>";
</script>
<script>
  function calculateTotal() {
    var tollFee = parseFloat(document.getElementById("tollFee").value) || 0;
    var rateAmount = parseFloat(document.getElementById("rateAmount").value) || 0;
    var salaryAmount = parseFloat(document.getElementById("salaryAmount").value) || 0;
    var gasAmount = parseFloat(document.getElementById("gasAmount").value) || 0;
    var allowanceAmount = parseFloat(document.getElementById("allowanceAmount").value) || 0;
    var extraMealAmount = parseFloat(document.getElementById("extraMealAmount").value) || 0;
    var mobileFee = parseFloat(document.getElementById("mobileFee").value) || 0;

    var total = tollFee + rateAmount + salaryAmount + gasAmount + allowanceAmount + extraMealAmount + mobileFee;
    document.getElementById("totalAmount").value = total.toFixed(2);
  }

  function populateEditForm(expense) {
    // Set values in the modal based on the selected expense row
    document.getElementById("updateExpenseID").value = expense.ExpenseID;
    document.getElementById("updateDate").value = expense.Date;
    document.getElementById("updateTollFee").value = expense.TollFee;
    document.getElementById("updateRateAmount").value = expense.RateAmount;
    document.getElementById("updateSalaryAmount").value = expense.SalaryAmount;
    document.getElementById("updateGasAmount").value = expense.GasAmount;
    document.getElementById("updateAllowanceAmount").value = expense.AllowanceAmount;
    document.getElementById("updateExtraMealAmount").value = expense.ExtraMealAmount;
    document.getElementById("updateMobile").value = expense.Mobile;
    document.getElementById("updateTotalAmount").value = expense.TotalAmount;
  }
  function computeTotalAmount() {
    // Get the values from the input fields
    const tollFee = parseFloat(document.getElementById("updateTollFee").value) || 0;
    const rateAmount = parseFloat(document.getElementById("updateRateAmount").value) || 0;
    const salaryAmount = parseFloat(document.getElementById("updateSalaryAmount").value) || 0;
    const gasAmount = parseFloat(document.getElementById("updateGasAmount").value) || 0;
    const allowanceAmount = parseFloat(document.getElementById("updateAllowanceAmount").value) || 0;
    const extraMealAmount = parseFloat(document.getElementById("updateExtraMealAmount").value) || 0;
    const mobileFee = parseFloat(document.getElementById("updateMobile").value) || 0;

    // Calculate the total amount
    const totalAmount = tollFee + rateAmount + salaryAmount + gasAmount + allowanceAmount + extraMealAmount + mobileFee;

    // Set the total amount in the totalAmount input field
    document.getElementById("updateTotalAmount").value = totalAmount.toFixed(2); // Rounds to 2 decimal places
  }

</script>
</body>

</html>