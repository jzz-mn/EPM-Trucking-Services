<?php
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
                    Trucks
                  </span>
                </li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <div class="widget-content searchable-container list">
      <!-- Add Maintenance Modal -->
      <div class="modal fade" id="addMaintenanceRecordModal" tabindex="-1" role="dialog"
        aria-labelledby="addMaintenanceRecordModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header d-flex align-items-center bg-primary">
              <h5 class="modal-title text-white fs-4">Add Maintenance Record</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-12">
                  <div class="card w-100 border position-relative overflow-hidden mb-0">
                    <div class="card-body p-4">
                      <h4 class="card-title">Add Maintenance Record</h4>
                      <p class="card-subtitle mb-4">Fill out the form to record a maintenance expense.</p>
                      <form>
                        <div class="row">
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="maintenanceId" class="form-label">Maintenance ID</label>
                              <input type="text" class="form-control" id="maintenanceId"
                                placeholder="Enter Maintenance ID">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="maintenanceDate" class="form-label">Date</label>
                              <input type="date" class="form-control" id="maintenanceDate" placeholder="Select Date">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="maintenanceCategory" class="form-label">Category</label>
                              <input type="text" class="form-control" id="maintenanceCategory"
                                placeholder="Enter Category">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="maintenanceDescription" class="form-label">Description</label>
                              <input type="text" class="form-control" id="maintenanceDescription"
                                placeholder="Enter Description">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="maintenanceAmount" class="form-label">Amount</label>
                              <input type="number" class="form-control" id="maintenanceAmount"
                                placeholder="Enter Amount">
                            </div>
                          </div>
                          <div class="col-lg-12">
                            <div class="mb-3">
                              <label for="maintenanceDetails" class="form-label">Details</label>
                              <input type="text" class="form-control" id="maintenanceDetails"
                                placeholder="Enter Maintenance Details">
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
      <!-- Add Transaction Modal -->
      <div class="modal fade" id="addTransactionModal" tabindex="-1" role="dialog"
        aria-labelledby="addTransactionModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header d-flex align-items-center bg-primary">
              <h5 class="modal-title text-white fs-4">Add Transaction</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-12">
                  <div class="card w-100 border position-relative overflow-hidden mb-0">
                    <div class="card-body p-4">
                      <h4 class="card-title">Add Transaction</h4>
                      <p class="card-subtitle mb-4">Fill out the details to create a new transaction.</p>
                      <form action="add_transaction.php" method="POST">
                        <div class="row">
                          <!-- Transaction ID -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="transactionID" class="form-label">Transaction ID</label>
                              <input type="text" class="form-control" id="transactionID" name="transactionID" value="<?php
                              include '../includes/db_connection.php';
                              $query = 'SELECT MAX(TransactionID) AS lastID FROM transactions';
                              $result = mysqli_query($conn, $query);
                              $row = mysqli_fetch_assoc($result);
                              echo isset($row['lastID']) ? $row['lastID'] + 1 : 1;
                              ?>" readonly>
                            </div>
                          </div>

                          <!-- Date -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="transactionDate" class="form-label">Date</label>
                              <input type="date" class="form-control" id="transactionDate" name="transactionDate"
                                placeholder="Enter Date">
                            </div>
                          </div>
                          <!-- Billing Invoice Number (Dropdown) -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="invoiceID" class="form-label">Billing Invoice Number</label>
                              <select class="form-select" id="invoiceID" name="invoiceID">
                                <option value="" disabled selected>Select Billing Invoice Number</option>
                                <?php
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
                              <label for="expenseID" class="form-label">Expense ID</label>
                              <select class="form-select" id="expenseID" name="expenseID">
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
                          <!-- Phone Number -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="phoneNumber" class="form-label">Phone Number</label>
                              <input type="text" class="form-control" id="phoneNumber" name="phoneNumber"
                                placeholder="Enter Phone Number">
                            </div>
                          </div>
                          <!-- DR Number -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="drNumber" class="form-label">DR Number</label>
                              <input type="text" class="form-control" id="drNumber" name="drNumber"
                                placeholder="Enter DR Number">
                            </div>
                          </div>
                          <!-- Source Customer Code -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="sourceCustomerCode" class="form-label">Source Customer Code</label>
                              <input type="text" class="form-control" id="sourceCustomerCode" name="sourceCustomerCode"
                                placeholder="Enter Source Customer Code">
                            </div>
                          </div>
                          <!-- Customer Number -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="customerNumber" class="form-label">Customer Name</label>
                              <input type="text" class="form-control" id="customerNumber" name="customerNumber"
                                placeholder="Enter Customer Name">
                            </div>
                          </div>
                          <!-- Destination Customer Code -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="destinationCustomerCode" class="form-label">Destination Customer Code</label>
                              <input type="text" class="form-control" id="destinationCustomerCode"
                                name="destinationCustomerCode" placeholder="Enter Destination Customer Code">
                            </div>
                          </div>
                          <!-- Quantity -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="quantityQtl" class="form-label">Quantity (Qtl)</label>
                              <input type="number" class="form-control" id="quantityQtl" name="quantityQtl"
                                placeholder="Enter Quantity">
                            </div>
                          </div>
                          <!-- Weight -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="weightKgs" class="form-label">Weight (Kgs)</label>
                              <input type="number" class="form-control" id="weightKgs" name="weightKgs"
                                placeholder="Enter Weight">
                            </div>
                          </div>
                          <!-- Submit and Cancel -->
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

      <h5 class="border-bottom py-2 px-4 mb-4">Trucks</h5>
      <div class="card">
        <div class="card-body p-0">
          <div class>
            <!-- Nav tabs -->
            <ul class="nav nav-tabs p-4 border-bottom" role="tablist">
              <li class="nav-item">
                <a class="nav-link active me-3" data-bs-toggle="tab" href="#home" role="tab">
                  <span>Maintenance</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#profile" role="tab">
                  <span>Transactions</span>
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
                      data-bs-target="#addMaintenanceRecordModal">
                      <i class="ti ti-users text-white me-1 fs-5"></i> Add Maintenance Record
                    </a>
                  </div>
                </div>
                <div class="py-3">
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
                      data-bs-target="#addTransactionModal">
                      <i class="ti ti-users text-white me-1 fs-5"></i> Add Transaction
                    </a>
                  </div>
                </div>
                <div class="table-responsive">
                  <table id="transactionsTable" class="table table-striped table-bordered display text-nowrap">
                    <thead>
                      <tr>
                        <th>Transaction ID</th>
                        <th>Invoice ID</th>
                        <th>Date</th>
                        <th>Plate Number</th>
                        <th>DR Number</th>
                        <th>Source Customer Code</th>
                        <th>Customer Name</th>
                        <th>Destination Customer Code</th>
                        <th>Qty</th>
                        <th>Kgs</th>
                        <th>Expense ID</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Include database connection
                      include '../includes/db_connection.php';

                      // Query to retrieve transactions data
                      $sql = "SELECT TransactionID, InvoiceID, Date, PlateNumber, DRNumber, SourceCustomerCode, CustomerName, DestinationCustomerCode, Qty, Kgs, ExpenseID FROM transactions";
                      $result = mysqli_query($conn, $sql);

                      // Check if there are rows returned
                      if (mysqli_num_rows($result) > 0) {
                        // Loop through the data and output it into the table
                        while ($row = mysqli_fetch_assoc($result)) {
                          echo "<tr>";
                          echo "<td>" . $row['TransactionID'] . "</td>";
                          echo "<td>" . $row['InvoiceID'] . "</td>";
                          echo "<td>" . $row['Date'] . "</td>";
                          echo "<td>" . $row['PlateNumber'] . "</td>";
                          echo "<td>" . $row['DRNumber'] . "</td>";
                          echo "<td>" . $row['SourceCustomerCode'] . "</td>";
                          echo "<td>" . $row['CustomerName'] . "</td>";
                          echo "<td>" . $row['DestinationCustomerCode'] . "</td>";
                          echo "<td>" . $row['Qty'] . "</td>";
                          echo "<td>" . $row['Kgs'] . "</td>";
                          echo "<td>" . $row['ExpenseID'] . "</td>";
                          echo "</tr>";
                        }
                      } else {
                        echo "<tr><td colspan='11'>No transactions found</td></tr>";
                      }

                      // Close the database connection
                      mysqli_close($conn);
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
</body>

</html>