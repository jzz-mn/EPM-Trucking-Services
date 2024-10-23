<?php
session_start();
include '../includes/db_connection.php'; // Adjust the path as necessary

// Handle AJAX Requests
if (isset($_GET['action'])) {
  header('Content-Type: application/json');

  $action = $_GET['action'];
  $startDate = null;
  $endDate = null;

  // Determine the date range based on the current filter
  if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    switch ($filter) {
      case 'year':
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
        break;
      case 'month':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        break;
      case 'week':
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d', strtotime('sunday this week'));
        break;
      case 'custom':
        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
          $startDate = $_GET['start_date'];
          $endDate = $_GET['end_date'];
        }
        break;
      default:
        break;
    }
  }

  // Escape dates to prevent SQL injection
  if ($startDate && $endDate) {
    $startDateEscaped = mysqli_real_escape_string($conn, $startDate);
    $endDateEscaped = mysqli_real_escape_string($conn, $endDate);
  }

  switch ($action) {
    case 'getExpensesData':
      // Fetch expenses by date
      $query = "SELECT Date, SUM(TotalExpense) as TotalExpense FROM expenses WHERE Date BETWEEN '$startDateEscaped' AND '$endDateEscaped' GROUP BY Date ORDER BY Date ASC";
      $result = mysqli_query($conn, $query);
      $data = [];
      while ($row = mysqli_fetch_assoc($result)) {
        $data[] = ['x' => $row['Date'], 'y' => (float) $row['TotalExpense']];
      }
      echo json_encode($data);
      exit;

    case 'getRevenueData':
      // Fetch revenue trends over time
      $query = "SELECT Date, SUM(RateAmount) as RateAmount FROM transactiongroup WHERE Date BETWEEN '$startDateEscaped' AND '$endDateEscaped' GROUP BY Date ORDER BY Date ASC";
      $result = mysqli_query($conn, $query);
      $data = [];
      while ($row = mysqli_fetch_assoc($result)) {
        $data[] = ['x' => $row['Date'], 'y' => (float) $row['RateAmount']];
      }
      echo json_encode($data);
      exit;

    case 'getProfitData':
      // Fetch profit margins over time (Revenue - Expenses)
      $query = "SELECT tg.Date, SUM(tg.RateAmount) as Revenue, IFNULL(SUM(e.TotalExpense),0) + IFNULL(SUM(f.Amount),0) as Expenses
                      FROM transactiongroup tg
                      LEFT JOIN expenses e ON tg.ExpenseID = e.ExpenseID
                      LEFT JOIN fuel f ON e.FuelID = f.FuelID
                      WHERE tg.Date BETWEEN '$startDateEscaped' AND '$endDateEscaped'
                      GROUP BY tg.Date
                      ORDER BY tg.Date ASC";
      $result = mysqli_query($conn, $query);
      $data = [];
      while ($row = mysqli_fetch_assoc($result)) {
        $profit = (float) $row['Revenue'] - ((float) $row['Expenses']);
        $data[] = ['x' => $row['Date'], 'y' => $profit];
      }
      echo json_encode($data);
      exit;

    case 'getTransactionsData':
      // Fetch number of transactions over time
      $query = "SELECT TransactionDate, COUNT(*) as TotalTransactions FROM transactions WHERE TransactionDate BETWEEN '$startDateEscaped' AND '$endDateEscaped' GROUP BY TransactionDate ORDER BY TransactionDate ASC";
      $result = mysqli_query($conn, $query);
      $data = [];
      while ($row = mysqli_fetch_assoc($result)) {
        $data[] = ['x' => $row['TransactionDate'], 'y' => (int) $row['TotalTransactions']];
      }
      echo json_encode($data);
      exit;

    case 'getFuelData':
      // Fetch fuel consumption over time
      $query = "SELECT Date, SUM(Liters) as TotalLiters FROM fuel WHERE Date BETWEEN '$startDateEscaped' AND '$endDateEscaped' GROUP BY Date ORDER BY Date ASC";
      $result = mysqli_query($conn, $query);
      $data = [];
      while ($row = mysqli_fetch_assoc($result)) {
        $data[] = ['x' => $row['Date'], 'y' => (float) $row['TotalLiters']];
      }
      echo json_encode($data);
      exit;

    default:
      echo json_encode(['error' => 'Invalid action']);
      exit;
  }
}

// Include necessary files and initialize variables
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'dashboard.php';
?>

<?php include '../officer/header.php'; ?>

<!-- DASHBOARD CONTENT-->
<div class="body-wrapper">
  <div class="container-fluid">
    <!-- Custom Date Range Modal -->
    <div class="modal fade" id="customDateModal" tabindex="-1" aria-labelledby="customDateModalLabel"
      aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="get" action="home.php">
            <div class="modal-header">
              <h5 class="modal-title" id="customDateModalLabel">Custom Date Range</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <!-- Hidden Filter Input -->
              <input type="hidden" name="filter" value="custom">
              <div class="mb-3">
                <label for="start_date" class="form-label">From:</label>
                <input type="date" name="start_date" class="form-control" id="start_date" required
                  value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
              </div>
              <div class="mb-3">
                <label for="end_date" class="form-label">To:</label>
                <input type="date" name="end_date" class="form-control" id="end_date" required
                  value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Apply</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Chart Modal -->
    <div class="modal fade" id="chartModal" tabindex="-1" aria-labelledby="chartModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg"> <!-- Use modal-lg for larger charts -->
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="chartModalLabel">Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Apex Chart Container -->
            <div id="chart"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Dashboard Cards -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="container-fluid mb-0 pb-0">
            <form method="get" action="home.php" class="row align-items-center">
              <!-- Optional: Display Selected Date Range -->
              <div class="col-md-8">
                <?php
                if ($startDate && $endDate) {
                  echo "<p class='text-left mb-4'>Showing results from <strong>" . date('F j, Y', strtotime($startDate)) . "</strong> to <strong>" . date('F j, Y', strtotime($endDate)) . "</strong></p>";
                } elseif (isset($_GET['filter']) && $_GET['filter'] == 'custom') {
                  echo "<p class='text-left mb-4 text-warning'>Please select a valid date range.</p>";
                }
                ?>
              </div>

              <!-- Filter Buttons (Right Side) -->
              <div class="col-md-4 text-end">
                <div class="btn-group mb-2" role="group" aria-label="Date Filters">
                  <button type="submit" name="filter" value="year" class="btn btn-outline-primary <?php if (isset($_GET['filter']) && $_GET['filter'] == 'year')
                    echo 'active'; ?>">Year</button>
                  <button type="submit" name="filter" value="month" class="btn btn-outline-primary <?php if (isset($_GET['filter']) && $_GET['filter'] == 'month')
                    echo 'active'; ?>">Month</button>
                  <button type="submit" name="filter" value="week" class="btn btn-outline-primary <?php if (isset($_GET['filter']) && $_GET['filter'] == 'week')
                    echo 'active'; ?>">Week</button>
                  <!-- Custom Button to Trigger Modal -->
                  <button type="button" class="btn btn-outline-primary <?php if (isset($_GET['filter']) && $_GET['filter'] == 'custom')
                    echo 'active'; ?>" data-bs-toggle="modal" data-bs-target="#customDateModal">
                    Custom
                  </button>
                </div>
              </div>
            </form>
          </div>

          <div class="card-body p-4 pb-0" data-simplebar="">
            <div class="row flex-nowrap">
              <!-- Total Expenses Card -->
              <div class="col">
                <div class="card primary-gradient">
                  <div class="card-body text-center px-9 pb-4">
                    <div
                      class="d-flex align-items-center justify-content-center round-48 rounded text-bg-primary flex-shrink-0 mb-3 mx-auto">
                      <iconify-icon icon="solar:dollar-minimalistic-linear" class="fs-7 text-white"></iconify-icon>
                    </div>
                    <h6 class="fw-normal fs-3 mb-1">Total Expenses</h6>
                    <h4 class="mb-3 d-flex align-items-center justify-content-center gap-1">
                      <?php echo '₱' . $formattedExpenses; ?>
                    </h4>
                    <a href="#" class="btn btn-white fs-2 fw-semibold text-nowrap view-details-btn"
                      data-metric="expenses">View Details</a>
                  </div>
                </div>
              </div>

              <!-- Total Revenue Card -->
              <div class="col">
                <div class="card warning-gradient">
                  <div class="card-body text-center px-9 pb-4">
                    <div
                      class="d-flex align-items-center justify-content-center round-48 rounded text-bg-warning flex-shrink-0 mb-3 mx-auto">
                      <iconify-icon icon="solar:recive-twice-square-linear" class="fs-7 text-white"></iconify-icon>
                    </div>
                    <h6 class="fw-normal fs-3 mb-1">Total Revenue</h6>
                    <h4 class="mb-3 d-flex align-items-center justify-content-center gap-1">
                      <?php echo '₱' . $formattedRevenue; ?>
                    </h4>
                    <a href="#" class="btn btn-white fs-2 fw-semibold text-nowrap view-details-btn"
                      data-metric="revenue">View Details</a>
                  </div>
                </div>
              </div>

              <!-- Total Profit Card -->
              <div class="col">
                <div class="card secondary-gradient">
                  <div class="card-body text-center px-9 pb-4">
                    <div
                      class="d-flex align-items-center justify-content-center round-48 rounded text-bg-secondary flex-shrink-0 mb-3 mx-auto">
                      <iconify-icon icon="ic:outline-backpack" class="fs-7 text-white"></iconify-icon>
                    </div>
                    <h6 class="fw-normal fs-3 mb-1">Total Profit</h6>
                    <h4 class="mb-3 d-flex align-items-center justify-content-center gap-1">
                      <?php echo '₱' . $formattedProfit; ?>
                    </h4>
                    <a href="#" class="btn btn-white fs-2 fw-semibold text-nowrap view-details-btn"
                      data-metric="profit">View Details</a>
                  </div>
                </div>
              </div>

              <!-- Total Transactions Card -->
              <div class="col">
                <div class="card danger-gradient">
                  <div class="card-body text-center px-9 pb-4">
                    <div
                      class="d-flex align-items-center justify-content-center round-48 rounded text-bg-danger flex-shrink-0 mb-3 mx-auto">
                      <iconify-icon icon="ic:baseline-sync-problem" class="fs-7 text-white"></iconify-icon>
                    </div>
                    <h6 class="fw-normal fs-3 mb-1">Total Transactions</h6>
                    <h4 class="mb-3 d-flex align-items-center justify-content-center gap-1">
                      <?php echo $formattedTransactions; ?>
                    </h4>
                    <a href="#" class="btn btn-white fs-2 fw-semibold text-nowrap view-details-btn"
                      data-metric="transactions">View Details</a>
                  </div>
                </div>
              </div>

              <!-- Total Fuel Consumption Card -->
              <div class="col">
                <div class="card success-gradient">
                  <div class="card-body text-center px-9 pb-4">
                    <div
                      class="d-flex align-items-center justify-content-center round-48 rounded text-bg-success flex-shrink-0 mb-3 mx-auto">
                      <iconify-icon icon="ic:outline-forest" class="fs-7 text-white"></iconify-icon>
                    </div>
                    <h6 class="fw-normal fs-3 mb-1">Total Fuel Consumption</h6>
                    <h4 class="mb-3 d-flex align-items-center justify-content-center gap-1">
                      <?php echo $formattedFuelConsumption . ' L'; ?>
                    </h4>
                    <a href="#" class="btn btn-white fs-2 fw-semibold text-nowrap view-details-btn"
                      data-metric="fuel">View Details</a>
                  </div>
                </div>
              </div>
              <!-- Add more cards if needed -->
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <div class="d-md-flex align-items-center justify-content-between mb-3">
              <div>
                <h5 class="card-title">Revenue Forecast</h5>
                <p class="card-subtitle mb-0">Overview of Profit</p>
              </div>

              <div class="hstack gap-9 mt-4 mt-md-0">
                <div class="d-flex align-items-center gap-2">
                  <span class="d-block flex-shrink-0 round-10 bg-primary rounded-circle"></span>
                  <span class="text-nowrap text-muted">2024</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="d-block flex-shrink-0 round-10 bg-danger rounded-circle"></span>
                  <span class="text-nowrap text-muted">2023</span>
                </div>
              </div>
            </div>
            <div style="height: 305px;" class="me-n2 rounded-bars">
              <div id="revenue-forecast"></div>
            </div>
            <div class="row mt-4 mb-2">
              <div class="col-md-4">
                <div class="hstack gap-6 mb-3 mb-md-0">
                  <span class="d-flex align-items-center justify-content-center round-48 bg-light rounded">
                    <iconify-icon icon="solar:pie-chart-2-linear" class="fs-7 text-dark"></iconify-icon>
                  </span>
                  <div>
                    <span>Total</span>
                    <h5 class="mt-1 fw-medium mb-0">$96,640</h5>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="hstack gap-6 mb-3 mb-md-0">
                  <span class="d-flex align-items-center justify-content-center round-48 bg-primary-subtle rounded">
                    <iconify-icon icon="solar:dollar-minimalistic-linear" class="fs-7 text-primary"></iconify-icon>
                  </span>
                  <div>
                    <span>Profit</span>
                    <h5 class="mt-1 fw-medium mb-0">$48,820</h5>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="hstack gap-6">
                  <span class="d-flex align-items-center justify-content-center round-48 bg-danger-subtle rounded">
                    <iconify-icon icon="solar:database-linear" class="fs-7 text-danger"></iconify-icon>
                  </span>
                  <div>
                    <span>Earnings</span>
                    <h5 class="mt-1 fw-medium mb-0">$48,820</h5>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- ----------------------------------------- -->
      <!-- Annual Profit -->
      <!-- ----------------------------------------- -->
      <div class="col-lg-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-4">Annual Profit</h5>
            <div class="bg-primary bg-opacity-10 rounded-1 overflow-hidden mb-4">
              <div class="p-4 mb-9">
                <div class="d-flex align-items-center justify-content-between">
                  <span class="text-dark-light">Conversion Rate</span>
                  <h3 class="mb-0">18.4%</h3>
                </div>
              </div>
              <div id="annual-profit"></div>
            </div>
            <div class="d-flex align-items-center justify-content-between pb-6 border-bottom">
              <div>
                <span class="text-muted fw-medium">Added to Cart</span>
                <span class="fs-11 fw-medium d-block mt-1">5 clicks</span>
              </div>
              <div class="text-end">
                <h6 class="fw-bolder mb-1 lh-base">$21,120.70</h6>
                <span class="fs-11 fw-medium text-success">+13.2%</span>
              </div>
            </div>
            <div class="d-flex align-items-center justify-content-between py-6 border-bottom">
              <div>
                <span class="text-muted fw-medium">Reached to Checkout</span>
                <span class="fs-11 fw-medium d-block mt-1">12 clicks</span>
              </div>
              <div class="text-end">
                <h6 class="fw-bolder mb-1 lh-base">$16,100.00</h6>
                <span class="fs-11 fw-medium text-danger">-7.4%</span>
              </div>
            </div>
            <div class="d-flex align-items-center justify-content-between pt-6">
              <div>
                <span class="text-muted fw-medium">Added to Cart</span>
                <span class="fs-11 fw-medium d-block mt-1">24 views</span>
              </div>
              <div class="text-end">
                <h6 class="fw-bolder mb-1 lh-base">$6,400.50</h6>
                <span class="fs-11 fw-medium text-success">+9.3%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-5">
        <!-- -------------------------------------------- -->
        <!-- Your Performance -->
        <!-- -------------------------------------------- -->
        <div class="card">
          <div class="card-body">
            <h5 class="card-title fw-semibold">Your Performance</h5>
            <p class="card-subtitle mb-0 lh-base">Last check on 25 february</p>

            <div class="row mt-4">
              <div class="col-md-6">
                <div class="vstack gap-9 mt-2">
                  <div class="hstack align-items-center gap-3">
                    <div
                      class="d-flex align-items-center justify-content-center round-48 rounded bg-primary-subtle flex-shrink-0">
                      <iconify-icon icon="solar:shop-2-linear" class="fs-7 text-primary"></iconify-icon>
                    </div>
                    <div>
                      <h6 class="mb-0 text-nowrap">64 new orders</h6>
                      <span>Processing</span>
                    </div>

                  </div>
                  <div class="hstack align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center round-48 rounded bg-danger-subtle">
                      <iconify-icon icon="solar:filters-outline" class="fs-7 text-danger"></iconify-icon>
                    </div>
                    <div>
                      <h6 class="mb-0">4 orders</h6>
                      <span>On hold</span>
                    </div>

                  </div>
                  <div class="hstack align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center round-48 rounded bg-secondary-subtle">
                      <iconify-icon icon="solar:pills-3-linear" class="fs-7 text-secondary"></iconify-icon>
                    </div>
                    <div>
                      <h6 class="mb-0">12 orders</h6>
                      <span>Delivered</span>
                    </div>

                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="text-center mt-sm-n7">
                  <div id="your-preformance"></div>
                  <h2 class="fs-8">275</h2>
                  <p class="mb-0">
                    Learn insigs how to manage all aspects of your
                    startup.
                  </p>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="row">
          <div class="col-md-6">
            <!-- -------------------------------------------- -->
            <!-- Customers -->
            <!-- -------------------------------------------- -->
            <div class="card">
              <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                  <div>
                    <h5 class="card-title fw-semibold">Customers</h5>
                    <p class="card-subtitle mb-0">Last 7 days</p>
                  </div>
                  <span class="fs-11 text-success fw-semibold lh-lg">+26.5%</span>
                </div>
                <div class="py-4 my-1">
                  <div id="customers-area"></div>
                </div>
                <div class="d-flex flex-column align-items-center gap-2 w-100 mt-3">
                  <div class="d-flex align-items-center gap-2 w-100">
                    <span class="d-block flex-shrink-0 round-8 bg-primary rounded-circle"></span>
                    <h6 class="fs-3 fw-normal text-muted mb-0">April 07 - April 14</h6>
                    <h6 class="fs-3 fw-normal mb-0 ms-auto text-muted">6,380</h6>
                  </div>
                  <div class="d-flex align-items-center gap-2 w-100">
                    <span class="d-block flex-shrink-0 round-8 bg-light rounded-circle"></span>
                    <h6 class="fs-3 fw-normal text-muted mb-0">Last Week</h6>
                    <h6 class="fs-3 fw-normal mb-0 ms-auto text-muted">4,298</h6>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <!-- -------------------------------------------- -->
            <!-- Sales Overview -->
            <!-- -------------------------------------------- -->
            <div class="card">
              <div class="card-body">
                <h5 class="card-title fw-semibold">Sales Overview</h5>
                <p class="card-subtitle mb-1">Last 7 days</p>

                <div class="position-relative labels-chart">
                  <span class="fs-11 label-1">0%</span>
                  <span class="fs-11 label-2">25%</span>
                  <span class="fs-11 label-3">50%</span>
                  <span class="fs-11 label-4">75%</span>
                  <div id="sales-overview"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
      <div class="col-lg-8">
        <!-- -------------------------------------------- -->
        <!-- Revenue by Product -->
        <!-- -------------------------------------------- -->
        <div class="card">
          <div class="card-body">
            <div class="d-flex flex-wrap gap-3 mb-9 justify-content-between align-items-center">
              <h5 class="card-title fw-semibold mb-0">Revenue by Product</h5>
              <select class="form-select w-auto fw-semibold">
                <option value="1">Sep 2024</option>
                <option value="2">Oct 2024</option>
                <option value="3">Nov 2024</option>
              </select>
            </div>

            <div class="table-responsive">
              <ul class="nav nav-tabs theme-tab gap-3 flex-nowrap" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" data-bs-toggle="tab" href="#app" role="tab">
                    <div class="hstack gap-2">
                      <iconify-icon icon="solar:widget-linear" class="fs-4"></iconify-icon>
                      <span>App</span>
                    </div>

                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" data-bs-toggle="tab" href="#mobile" role="tab">
                    <div class="hstack gap-2">
                      <iconify-icon icon="solar:smartphone-line-duotone" class="fs-4"></iconify-icon>
                      <span>Mobile</span>
                    </div>
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" data-bs-toggle="tab" href="#saas" role="tab">
                    <div class="hstack gap-2">
                      <iconify-icon icon="solar:calculator-linear" class="fs-4"></iconify-icon>
                      <span>SaaS</span>
                    </div>
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" data-bs-toggle="tab" href="#other" role="tab">
                    <div class="hstack gap-2">
                      <iconify-icon icon="solar:folder-open-outline" class="fs-4"></iconify-icon>
                      <span>Others</span>
                    </div>
                  </a>
                </li>
              </ul>
            </div>
            <div class="tab-content mb-n3">
              <div class="tab-pane active" id="app" role="tabpanel">
                <div class="table-responsive" data-simplebar>
                  <table class="table text-nowrap align-middle table-custom mb-0 last-items-borderless">
                    <thead>
                      <tr>
                        <th scope="col" class="fw-normal ps-0">Assigned
                        </th>
                        <th scope="col" class="fw-normal">Progress</th>
                        <th scope="col" class="fw-normal">Priority</th>
                        <th scope="col" class="fw-normal">Budget</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-1.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Minecraf App</h6>
                              <span>Jason Roy</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-success-subtle text-success">Low</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>
                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-2.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Web App Project</h6>
                              <span>Mathew Flintoff</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-warning-subtle text-warning">Medium</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>
                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-3.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Modernize Dashboard</h6>
                              <span>Anil Kumar</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-secondary-subtle text-secondary">Very
                            High</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>
                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-4.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Dashboard Co</h6>
                              <span>George Cruize</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-danger-subtle text-danger">High</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="tab-pane" id="mobile" role="tabpanel">
                <div class="table-responsive" data-simplebar>
                  <table class="table text-nowrap align-middle table-custom mb-0 last-items-borderless">
                    <thead>
                      <tr>
                        <th scope="col" class="fw-normal ps-0">Assigned
                        </th>
                        <th scope="col" class="fw-normal">Progress</th>
                        <th scope="col" class="fw-normal">Priority</th>
                        <th scope="col" class="fw-normal">Budget</th>
                      </tr>
                    </thead>
                    <tbody>

                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-2.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Web App Project</h6>
                              <span>Mathew Flintoff</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-warning-subtle text-warning">Medium</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>
                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-3.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Modernize Dashboard</h6>
                              <span>Anil Kumar</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-secondary-subtle text-secondary">Very
                            High</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>
                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-1.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Minecraf App</h6>
                              <span>Jason Roy</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-success-subtle text-success">Low</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>
                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-4.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Dashboard Co</h6>
                              <span>George Cruize</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-danger-subtle text-danger">High</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="tab-pane" id="saas" role="tabpanel">
                <div class="table-responsive" data-simplebar>
                  <table class="table text-nowrap align-middle table-custom mb-0 last-items-borderless">
                    <thead>
                      <tr>
                        <th scope="col" class="fw-normal ps-0">Assigned
                        </th>
                        <th scope="col" class="fw-normal">Progress</th>
                        <th scope="col" class="fw-normal">Priority</th>
                        <th scope="col" class="fw-normal">Budget</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-2.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Web App Project</h6>
                              <span>Mathew Flintoff</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-warning-subtle text-warning">Medium</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>
                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-1.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Minecraf App</h6>
                              <span>Jason Roy</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-success-subtle text-success">Low</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>

                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-3.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Modernize Dashboard</h6>
                              <span>Anil Kumar</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-secondary-subtle text-secondary">Very
                            High</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>
                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-4.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Dashboard Co</h6>
                              <span>George Cruize</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-danger-subtle text-danger">High</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="tab-pane" id="other" role="tabpanel">
                <div class="table-responsive" data-simplebar>
                  <table class="table text-nowrap align-middle table-custom mb-0 last-items-borderless">
                    <thead>
                      <tr>
                        <th scope="col" class="fw-normal ps-0">Assigned
                        </th>
                        <th scope="col" class="fw-normal">Progress</th>
                        <th scope="col" class="fw-normal">Priority</th>
                        <th scope="col" class="fw-normal">Budget</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-1.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Minecraf App</h6>
                              <span>Jason Roy</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-success-subtle text-success">Low</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>
                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-3.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Modernize Dashboard</h6>
                              <span>Anil Kumar</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-secondary-subtle text-secondary">Very
                            High</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>
                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-2.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Web App Project</h6>
                              <span>Mathew Flintoff</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-warning-subtle text-warning">Medium</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>

                      <tr>
                        <td class="ps-0">
                          <div class="d-flex align-items-center gap-6">
                            <img src="../assets/images/products/dash-prd-4.jpg" alt="prd1" width="48" class="rounded" />
                            <div>
                              <h6 class="mb-0">Dashboard Co</h6>
                              <span>George Cruize</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span>73.2%</span>
                        </td>
                        <td>
                          <span class="badge bg-danger-subtle text-danger">High</span>
                        </td>
                        <td>
                          <span class="text-dark-light">$3.5k</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>


          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <!-- -------------------------------------------- -->
        <!-- Total settlements -->
        <!-- -------------------------------------------- -->
        <div class="card bg-primary-subtle">
          <div class="card-body">
            <div class="hstack align-items-center gap-3 mb-4">
              <span class="d-flex align-items-center justify-content-center round-48 bg-white rounded flex-shrink-0">
                <iconify-icon icon="solar:box-linear" class="fs-7 text-primary"></iconify-icon>
              </span>
              <div>
                <p class="mb-1 text-dark-light">Total settlements</p>
                <h4 class="mb-0 fw-bolder">$122,580</h5>
              </div>
            </div>
            <div style="height: 278px;">
              <div id="settlements"></div>
            </div>
            <div class="row mt-4 mb-2">
              <div class="col-md-6 text-center">
                <p class="mb-1 text-dark-light lh-lg">Total balance</p>
                <h4 class="mb-0 text-nowrap">$122,580</h4>
              </div>
              <div class="col-md-6 text-center mt-3 mt-md-0">
                <p class="mb-1 text-dark-light lh-lg">Withdrawals</p>
                <h4 class="mb-0">$31,640</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12">
        <div class="card mb-0">
          <div class="card-body calender-sidebar app-calendar">
            <div id="calendar"></div>
          </div>
        </div>
        <!-- BEGIN MODAL -->
        <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">
                  Add / Edit Event
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-12">
                    <div>
                      <label class="form-label">Event Title</label>
                      <input id="event-title" type="text" class="form-control" />
                    </div>
                  </div>
                  <div class="col-md-12 mt-6">
                    <div>
                      <label class="form-label">Event Color</label>
                    </div>
                    <div class="d-flex">
                      <div class="n-chk">
                        <div class="form-check form-check-primary form-check-inline">
                          <input class="form-check-input" type="radio" name="event-level" value="Danger"
                            id="modalDanger" />
                          <label class="form-check-label" for="modalDanger">Danger</label>
                        </div>
                      </div>
                      <div class="n-chk">
                        <div class="form-check form-check-warning form-check-inline">
                          <input class="form-check-input" type="radio" name="event-level" value="Success"
                            id="modalSuccess" />
                          <label class="form-check-label" for="modalSuccess">Success</label>
                        </div>
                      </div>
                      <div class="n-chk">
                        <div class="form-check form-check-success form-check-inline">
                          <input class="form-check-input" type="radio" name="event-level" value="Primary"
                            id="modalPrimary" />
                          <label class="form-check-label" for="modalPrimary">Primary</label>
                        </div>
                      </div>
                      <div class="n-chk">
                        <div class="form-check form-check-danger form-check-inline">
                          <input class="form-check-input" type="radio" name="event-level" value="Warning"
                            id="modalWarning" />
                          <label class="form-check-label" for="modalWarning">Warning</label>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-12 mt-6">
                    <div>
                      <label class="form-label">Enter Start Date</label>
                      <input id="event-start-date" type="date" class="form-control" />
                    </div>
                  </div>

                  <div class="col-md-12 mt-6">
                    <div>
                      <label class="form-label">Enter End Date</label>
                      <input id="event-end-date" type="date" class="form-control" />
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">
                  Close
                </button>
                <button type="button" class="btn btn-success btn-update-event" data-fc-event-public-id="">
                  Update changes
                </button>
                <button type="button" class="btn btn-primary btn-add-event">
                  Add Event
                </button>
              </div>
            </div>
          </div>
        </div>
        <!-- END MODAL -->
      </div>
    </div>

    <!-- Include your footer -->
    <?php include '../officer/footer.php'; ?>
  </div>
</div>

<!-- Include ApexCharts Library -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- JavaScript to Handle Modals and Charts -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const viewDetailButtons = document.querySelectorAll('.view-details-btn');
    const chartModal = new bootstrap.Modal(document.getElementById('chartModal'));
    const chartContainer = document.getElementById('chart');
    const chartModalLabel = document.getElementById('chartModalLabel');

    let apexChart; // To store the ApexChart instance

    viewDetailButtons.forEach(button => {
      button.addEventListener('click', function (e) {
        e.preventDefault();
        const metric = this.getAttribute('data-metric');

        // Set modal title and action based on metric
        let title = '';
        let action = '';
        switch (metric) {
          case 'expenses':
            title = 'Total Expenses Details';
            action = 'getExpensesData';
            break;
          case 'revenue':
            title = 'Total Revenue Details';
            action = 'getRevenueData';
            break;
          case 'profit':
            title = 'Total Profit Details';
            action = 'getProfitData';
            break;
          case 'transactions':
            title = 'Total Transactions Details';
            action = 'getTransactionsData';
            break;
          case 'fuel':
            title = 'Total Fuel Consumption Details';
            action = 'getFuelData';
            break;
          default:
            title = 'Details';
            action = '';
        }

        chartModalLabel.textContent = title;

        if (action === '') {
          chartContainer.innerHTML = '<p>No data available.</p>';
          chartModal.show();
          return;
        }

        // Fetch data via AJAX
        // Get current filter parameters from the URL
        const urlParams = new URLSearchParams(window.location.search);
        const filter = urlParams.get('filter') || 'month'; // Default to 'month' if not set
        const startDate = urlParams.get('start_date') || '';
        const endDate = urlParams.get('end_date') || '';

        // Build the AJAX URL
        let ajaxURL = `home.php?action=${action}&filter=${filter}`;
        if (filter === 'custom') {
          ajaxURL += `&start_date=${startDate}&end_date=${endDate}`;
        }

        // Optional: Display a loading indicator
        chartContainer.innerHTML = '<p>Loading...</p>';
        chartModal.show();

        fetch(ajaxURL)
          .then(response => response.json())
          .then(data => {
            // Prepare data for ApexCharts
            let chartOptions = {};

            // Configure chart options based on metric
            switch (metric) {
              case 'expenses':
                chartOptions = {
                  chart: {
                    type: 'bar',
                    height: 400
                  },
                  series: [{
                    name: 'Total Expenses',
                    data: data.map(item => item.y)
                  }],
                  xaxis: {
                    categories: data.map(item => item.x),
                    title: {
                      text: 'Date'
                    },
                    labels: {
                      rotate: -45
                    }
                  },
                  yaxis: {
                    title: {
                      text: 'Amount (₱)'
                    }
                  },
                  title: {
                    text: 'Expenses Over Time',
                    align: 'center'
                  }
                };
                break;

              case 'revenue':
                chartOptions = {
                  chart: {
                    type: 'line',
                    height: 400
                  },
                  series: [{
                    name: 'Total Revenue',
                    data: data.map(item => item.y)
                  }],
                  xaxis: {
                    categories: data.map(item => item.x),
                    title: {
                      text: 'Date'
                    },
                    labels: {
                      rotate: -45
                    }
                  },
                  yaxis: {
                    title: {
                      text: 'Amount (₱)'
                    }
                  },
                  title: {
                    text: 'Revenue Trends',
                    align: 'center'
                  }
                };
                break;

              case 'profit':
                chartOptions = {
                  chart: {
                    type: 'area',
                    height: 400
                  },
                  series: [{
                    name: 'Total Profit',
                    data: data.map(item => item.y)
                  }],
                  xaxis: {
                    categories: data.map(item => item.x),
                    title: {
                      text: 'Date'
                    },
                    labels: {
                      rotate: -45
                    }
                  },
                  yaxis: {
                    title: {
                      text: 'Amount (₱)'
                    }
                  },
                  title: {
                    text: 'Profit Margins',
                    align: 'center'
                  }
                };
                break;

              case 'transactions':
                chartOptions = {
                  chart: {
                    type: 'pie',
                    height: 400
                  },
                  series: data.map(item => item.y),
                  labels: data.map(item => item.x),
                  title: {
                    text: 'Transactions Distribution',
                    align: 'center'
                  }
                };
                break;

              case 'fuel':
                chartOptions = {
                  chart: {
                    type: 'bar',
                    height: 400
                  },
                  series: [{
                    name: 'Fuel Consumption (Liters)',
                    data: data.map(item => item.y)
                  }],
                  xaxis: {
                    categories: data.map(item => item.x),
                    title: {
                      text: 'Date'
                    },
                    labels: {
                      rotate: -45
                    }
                  },
                  yaxis: {
                    title: {
                      text: 'Liters'
                    }
                  },
                  title: {
                    text: 'Fuel Consumption Over Time',
                    align: 'center'
                  }
                };
                break;

              default:
                chartOptions = {
                  chart: {
                    type: 'bar',
                    height: 400
                  },
                  series: [{
                    name: 'Data',
                    data: data.map(item => item.y)
                  }],
                  xaxis: {
                    categories: data.map(item => item.x),
                    title: {
                      text: 'Category'
                    },
                    labels: {
                      rotate: -45
                    }
                  },
                  yaxis: {
                    title: {
                      text: 'Value'
                    }
                  },
                  title: {
                    text: 'Details',
                    align: 'center'
                  }
                };
            }

            // Clear previous chart if exists
            if (apexChart) {
              apexChart.destroy();
            }

            // Create a new ApexChart
            apexChart = new ApexCharts(chartContainer, chartOptions);
            apexChart.render();
          })
          .catch(error => {
            console.error('Error fetching data:', error);
            chartContainer.innerHTML = '<p>Error loading data.</p>';
          });
      });
    });
  });
</script>

</body>

</html>