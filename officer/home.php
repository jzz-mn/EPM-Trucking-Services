<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php'; // Adjust the path to your database connection file

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'dashboard.php';
?>

<!-- DASHBOARD CONTENT-->
<div class="body-wrapper">
  <div class="container-fluid">
    <!-- Date Filter Section -->
    <div class="container-fluid mb-4">
      <form method="get" action="home.php" class="row align-items-center">
        <!-- Custom Date Inputs (Left Side) -->
        <?php if (isset($_GET['filter']) && $_GET['filter'] == 'custom') { ?>
          <div class="col-md-8">
            <div class="form-row align-items-center">
              <div class="col-auto">
                <div class="form-group mb-2 d-flex align-items-center">
                  <label for="start_date" class="mr-2 mb-0">From:</label>
                  <input type="date" name="start_date" class="form-control" placeholder="Start Date"
                    value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
                </div>
              </div>
              <div class="col-auto">
                <div class="form-group mb-2 d-flex align-items-center">
                  <label for="end_date" class="mr-2 mb-0">To:</label>
                  <input type="date" name="end_date" class="form-control" placeholder="End Date"
                    value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
                </div>
              </div>
              <div class="col-auto">
                <button type="submit" class="btn btn-primary mb-2">Apply</button>
              </div>
            </div>
          </div>
        <?php } else { ?>
          <!-- Empty column to align buttons to the right when custom fields are not visible -->
          <div class="col-md-8"></div>
        <?php } ?>

        <!-- Filter Buttons (Right Side) -->
        <div class="col-md-4 text-right">
          <div class="btn-group mb-2" role="group" aria-label="Date Filters">
            <button type="submit" name="filter" value="year" class="btn btn-outline-primary <?php if (isset($_GET['filter']) && $_GET['filter'] == 'year')
              echo 'active'; ?>">Year</button>
            <button type="submit" name="filter" value="month" class="btn btn-outline-primary <?php if (isset($_GET['filter']) && $_GET['filter'] == 'month')
              echo 'active'; ?>">Month</button>
            <button type="submit" name="filter" value="week" class="btn btn-outline-primary <?php if (isset($_GET['filter']) && $_GET['filter'] == 'week')
              echo 'active'; ?>">Week</button>
            <button type="submit" name="filter" value="custom" class="btn btn-outline-primary <?php if (isset($_GET['filter']) && $_GET['filter'] == 'custom')
              echo 'active'; ?>">Custom</button>
          </div>
        </div>
      </form>
    </div>

    <!-- Dashboard Cards -->
    <div class="row">
      <div class="col-12">
        <div class="card">
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
                    <a href="expenses_details.php" class="btn btn-white fs-2 fw-semibold text-nowrap">View Details</a>
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
                    <a href="revenue_details.php" class="btn btn-white fs-2 fw-semibold text-nowrap">View Details</a>
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
                    <a href="profit_details.php" class="btn btn-white fs-2 fw-semibold text-nowrap">View Details</a>
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
                    <a href="transactions_details.php" class="btn btn-white fs-2 fw-semibold text-nowrap">View
                      Details</a>
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
                    <a href="fuel_details.php" class="btn btn-white fs-2 fw-semibold text-nowrap">View Details</a>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
        <!-- Additional content -->
      </div>
    </div>
  </div>
</div>
<!-- Include your footer -->
<?php include '../officer/footer.php'; ?>