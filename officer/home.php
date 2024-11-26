<?php
session_start();
include '../includes/db_connection.php'; // Adjust the path as necessary

// Set 'year' as the default filter if none is selected
if (!isset($_GET['filter'])) {
  $_GET['filter'] = 'year';
}

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
      // Aggregating data by month if too many records
      $dateRange = (strtotime($endDateEscaped) - strtotime($startDateEscaped)) / (60 * 60 * 24); // Calculate date range in days

      // Check if the date range is larger than 30 days (e.g., more than a month)
      if ($dateRange > 30) {
        // Aggregate by month
        $query = "SELECT DATE_FORMAT(e.Date, '%Y-%m') as Month, 
                         SUM(e.SalaryAmount) as SalaryAmount, 
                         SUM(e.MobileAmount) as MobileAmount, 
                         SUM(e.OtherAmount) as OtherAmount, 
                         SUM(f.Amount) as FuelAmount
                  FROM expenses e
                  LEFT JOIN fuel f ON e.FuelID = f.FuelID 
                  WHERE e.Date BETWEEN '$startDateEscaped' AND '$endDateEscaped' 
                  GROUP BY Month
                  ORDER BY Month ASC";
      } else {
        // Fetch data by day if the date range is smaller (up to 30 days)
        $query = "SELECT e.Date, 
                           SUM(e.SalaryAmount) as SalaryAmount, 
                           SUM(e.MobileAmount) as MobileAmount, 
                           SUM(e.OtherAmount) as OtherAmount, 
                           SUM(f.Amount) as FuelAmount
                    FROM expenses e
                    LEFT JOIN fuel f ON e.FuelID = f.FuelID 
                    WHERE e.Date BETWEEN '$startDateEscaped' AND '$endDateEscaped' 
                    GROUP BY e.Date 
                    ORDER BY e.Date ASC";
      }

      $result = mysqli_query($conn, $query);

      // Initialize data structure to store results
      $data = [
        'SalaryAmount' => [],
        'MobileAmount' => [],
        'OtherAmount' => [],
        'FuelAmount' => []
      ];

      // Collect data for each category
      while ($row = mysqli_fetch_assoc($result)) {
        // Use the 'Month' field if data is aggregated by month
        $date = isset($row['Month']) ? $row['Month'] : $row['Date'];
        $data['SalaryAmount'][] = ['x' => $date, 'y' => (float) $row['SalaryAmount']];
        $data['MobileAmount'][] = ['x' => $date, 'y' => (float) $row['MobileAmount']];
        $data['OtherAmount'][] = ['x' => $date, 'y' => (float) $row['OtherAmount']];
        $data['FuelAmount'][] = ['x' => $date, 'y' => (float) $row['FuelAmount']];
      }

      // Sort data by date for each category
      foreach ($data as &$series) {
        usort($series, function ($a, $b) {
          return strtotime($a['x']) - strtotime($b['x']);
        });
      }

      // Return the data as JSON for the chart
      echo json_encode($data);
      exit;


    case 'getRevenueData':
      // Calculate the date range in days
      $dateRange = (strtotime($endDateEscaped) - strtotime($startDateEscaped)) / (60 * 60 * 24); // Total days

      // Determine aggregation level based on date range
      if ($dateRange > 30) {
        // Aggregate by month
        $dateFormat = '%Y-%m'; // Year-Month format
        $groupBy = "DATE_FORMAT(tg.Date, '$dateFormat')";
        $xLabel = "Month";
      } else {
        // Aggregate by day
        $dateFormat = '%Y-%m-%d'; // Year-Month-Day format
        $groupBy = "tg.Date";
        $xLabel = "Date";
      }

      // SQL Query to aggregate RateAmount, TotalExpense, and FuelAmount
      $query = "
          SELECT 
            $groupBy as Period,
            SUM(tg.Amount) as RateAmount,
            IFNULL(SUM(e.TotalExpense), 0) as TotalExpense,
            IFNULL(SUM(f.Amount), 0) as FuelAmount
          FROM 
            transactiongroup tg
          LEFT JOIN 
            expenses e ON tg.ExpenseID = e.ExpenseID
          LEFT JOIN 
            fuel f ON e.FuelID = f.FuelID
          WHERE 
            tg.Date BETWEEN '$startDateEscaped' AND '$endDateEscaped'
          GROUP BY 
            Period
          ORDER BY 
            Period ASC
        ";

      $result = mysqli_query($conn, $query);
      if (!$result) {
        // Handle query error
        echo json_encode(['error' => 'Database query failed: ' . mysqli_error($conn)]);
        exit;
      }

      $data = [];
      while ($row = mysqli_fetch_assoc($result)) {
        $period = $row['Period'];
        $rateAmount = (float) $row['RateAmount'];
        $totalExpense = (float) $row['TotalExpense'];
        $fuelAmount = (float) $row['FuelAmount'];

        $totalRevenue = $rateAmount;

        $data[] = ['x' => $period, 'y' => $totalRevenue];
      }

      // Sort data by date (already sorted in query, but added for safety)
      usort($data, function ($a, $b) {
        return strtotime($a['x']) - strtotime($b['x']);
      });

      echo json_encode($data);
      exit;


    case 'getProfitData':
      // Calculate the date range in days
      $dateRange = ($endDate && $startDate) ? (strtotime($endDateEscaped) - strtotime($startDateEscaped)) / (60 * 60 * 24) : 0; // Total days

      // Determine aggregation level based on date range
      if ($dateRange > 30) {
        // Aggregate by month
        $dateFormat = '%Y-%m'; // Year-Month format
        $groupBy = "DATE_FORMAT(tg.Date, '$dateFormat')";
        $periodLabel = "Month";
      } else {
        // Aggregate by day
        $dateFormat = '%Y-%m-%d'; // Year-Month-Day format
        $groupBy = "tg.Date";
        $periodLabel = "Date";
      }

      // SQL Query to aggregate RateAmount, TotalExpense, and FuelAmount
      $query = "
            SELECT 
                $groupBy as Period,
                SUM(tg.Amount) as RateAmount,
                IFNULL(SUM(e.TotalExpense), 0) as TotalExpense,
                IFNULL(SUM(f.Amount), 0) as FuelAmount
            FROM 
                transactiongroup tg
            LEFT JOIN 
                expenses e ON tg.ExpenseID = e.ExpenseID
            LEFT JOIN 
                fuel f ON e.FuelID = f.FuelID
            WHERE 
                tg.Date BETWEEN '$startDateEscaped' AND '$endDateEscaped'
            GROUP BY 
                Period
            ORDER BY 
                Period ASC
        ";

      $result = mysqli_query($conn, $query);
      if (!$result) {
        // Handle query error
        echo json_encode(['error' => 'Database query failed: ' . mysqli_error($conn)]);
        exit;
      }

      $data = [];
      while ($row = mysqli_fetch_assoc($result)) {
        $period = $row['Period'];
        $rateAmount = (float) $row['RateAmount'];
        $totalExpense = (float) $row['TotalExpense'];
        $fuelAmount = (float) $row['FuelAmount'];

        // Correct Profit Calculation: Profit = RateAmount - (TotalExpense + FuelAmount)
        $profit = ($rateAmount - $totalExpense);

        $data[] = ['x' => $period, 'y' => $profit];
      }

      // Sort data by date (already sorted in query, but added for safety)
      usort($data, function ($a, $b) {
        return strtotime($a['x']) - strtotime($b['x']);
      });

      echo json_encode($data);
      exit;



    case 'getTransactionsData':
      // Aggregating data by month if too many records
      $dateRange = (strtotime($endDateEscaped) - strtotime($startDateEscaped)) / (60 * 60 * 24); // Calculate date range in days

      if ($dateRange > 30) {
        // Aggregate by month
        $query = "SELECT DATE_FORMAT(TransactionDate, '%Y-%m') as Month, COUNT(*) as TotalTransactions
                    FROM transactions 
                    WHERE TransactionDate BETWEEN '$startDateEscaped' AND '$endDateEscaped' 
                    GROUP BY Month
                    ORDER BY Month ASC";
      } else {
        // Fetch data by day
        $query = "SELECT TransactionDate, COUNT(*) as TotalTransactions
                    FROM transactions 
                    WHERE TransactionDate BETWEEN '$startDateEscaped' AND '$endDateEscaped' 
                    GROUP BY TransactionDate
                    ORDER BY TransactionDate ASC";
      }

      $result = mysqli_query($conn, $query);
      $data = [];

      while ($row = mysqli_fetch_assoc($result)) {
        $date = isset($row['Month']) ? $row['Month'] : $row['TransactionDate'];
        $data[] = ['x' => $date, 'y' => (int) $row['TotalTransactions']];
      }

      // Sort data by date
      usort($data, function ($a, $b) {
        return strtotime($a['x']) - strtotime($b['x']);
      });

      echo json_encode($data);
      exit;


    case 'getFuelData':
      // Aggregating data by month if too many records
      $dateRange = (strtotime($endDateEscaped) - strtotime($startDateEscaped)) / (60 * 60 * 24); // Calculate date range in days

      if ($dateRange > 30) {
        // Aggregate by month
        $query = "SELECT DATE_FORMAT(Date, '%Y-%m') as Month, SUM(Liters) as TotalLiters
                    FROM fuel 
                    WHERE Date BETWEEN '$startDateEscaped' AND '$endDateEscaped' 
                    GROUP BY Month
                    ORDER BY Month ASC";
      } else {
        // Fetch data by day
        $query = "SELECT Date, SUM(Liters) as TotalLiters
                    FROM fuel 
                    WHERE Date BETWEEN '$startDateEscaped' AND '$endDateEscaped' 
                    GROUP BY Date
                    ORDER BY Date ASC";
      }

      $result = mysqli_query($conn, $query);
      $data = [];

      while ($row = mysqli_fetch_assoc($result)) {
        $date = isset($row['Month']) ? $row['Month'] : $row['Date'];
        $data[] = ['x' => $date, 'y' => (float) $row['TotalLiters']];
      }

      // Sort data by date
      usort($data, function ($a, $b) {
        return strtotime($a['x']) - strtotime($b['x']);
      });

      echo json_encode($data);
      exit;


      // *** getTruckDistributionData ***
    case 'getTruckDistributionData':
      // Ensure startDate and endDate are set
      if (!$startDateEscaped || !$endDateEscaped) {
        echo json_encode(['error' => 'Invalid date range']);
        exit;
      }

      // Query to count transactions per TruckID within the date range
      $query = "SELECT tg.TruckID, t.PlateNo, COUNT(*) as TransactionCount
                FROM transactiongroup tg
                JOIN trucksinfo t ON tg.TruckID = t.TruckID
                WHERE tg.Date BETWEEN '$startDateEscaped' AND '$endDateEscaped'
                GROUP BY tg.TruckID, t.PlateNo
                ORDER BY TransactionCount DESC";

      $result = mysqli_query($conn, $query);

      if (!$result) {
        echo json_encode(['error' => 'Database query failed']);
        exit;
      }

      $data = [];
      while ($row = mysqli_fetch_assoc($result)) {
        // Use PlateNo as the label for TruckID in the output
        $data[] = ['x' => $row['PlateNo'], 'y' => (int) $row['TransactionCount']];
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
                  </div>
                </div>
              </div>

              <!-- Add more cards if needed -->
            </div>
          </div>
        </div>
      </div>

      <!-- CHART AREA -->
      <div class="col-12">
        <div class="row">
          <!-- Total Expenses Chart -->
          <div class="col-lg-6">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Total Expenses</h5>
                <div id="expenses-chart"></div>
              </div>
            </div>
          </div>
          <!-- Total Revenue Chart -->
          <div class="col-lg-6">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Total Revenue</h5>
                <div id="revenue-chart"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <!-- Total Profit Chart -->
          <div class="col-lg-6">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Total Profit</h5>
                <div id="profit-chart"></div>
              </div>
            </div>
          </div>
          <!-- Total Transactions Chart -->
          <div class="col-lg-6">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Total Transactions</h5>
                <div id="transactions-chart"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <!-- Total Fuel Consumption Chart -->
          <div class="col-lg-6">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Total Fuel Consumption</h5>
                <div id="fuel-chart"></div>
              </div>
            </div>
          </div>
          <!-- *** Truck Usage Distribution *** -->
          <div class="col-lg-6">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Truck Usage Distribution</h5>
                <div id="truck-distribution-chart"></div>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>

</div>
</div>

<!-- Include ApexCharts Library -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- JavaScript to Handle Charts -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // List of metrics including the new truckDistribution
    const metrics = ['expenses', 'revenue', 'profit', 'transactions', 'fuel', 'truckDistribution']; // Added 'truckDistribution'

    // Function to get the current theme
    function getActiveTheme() {
      return document.documentElement.getAttribute("data-bs-theme") || "light";
    }

    // Function to apply theme-specific options
    function applyThemeToChartOptions(theme, options) {
      const isDark = theme === "dark";
      const textColor = isDark ? "#FFFFFFD9" : "#29343d";

      options.chart = options.chart || {};
      options.chart.foreColor = textColor; // Set text color
      options.xaxis = options.xaxis || {};
      options.xaxis.labels = options.xaxis.labels || {};
      options.xaxis.labels.style = { colors: textColor }; // X-axis label color
      options.yaxis = options.yaxis || {};
      options.yaxis.labels = options.yaxis.labels || {};
      options.yaxis.labels.style = { colors: textColor }; // Y-axis label color
      options.title = options.title || {};
      options.title.style = { color: textColor }; // Chart title color
      options.legend = options.legend || {};
      options.legend.labels = { colors: textColor }; // Legend text color
      options.tooltip = options.tooltip || {};
      options.tooltip.theme = isDark ? "dark" : "light"; // Tooltip theme
    }

    metrics.forEach(metric => {
      fetchAndRenderChart(metric);
    });

    function fetchAndRenderChart(metric) {
      let action = '';
      let containerId = '';

      // Determine action and container ID based on metric
      switch (metric) {
        case 'expenses':
          action = 'getExpensesData';
          containerId = 'expenses-chart';
          break;
        case 'revenue':
          action = 'getRevenueData';
          containerId = 'revenue-chart';
          break;
        case 'profit':
          action = 'getProfitData';
          containerId = 'profit-chart';
          break;
        case 'transactions':
          action = 'getTransactionsData';
          containerId = 'transactions-chart';
          break;
        case 'fuel':
          action = 'getFuelData';
          containerId = 'fuel-chart';
          break;
        case 'truckDistribution':
          action = 'getTruckDistributionData';
          containerId = 'truck-distribution-chart';
          break;
        default:
          console.error('Unknown metric:', metric);
          return;
      }

      // Get current filter parameters from the URL
      const urlParams = new URLSearchParams(window.location.search);
      const filter = urlParams.get('filter') || 'year'; // Default to 'year' if not set
      const startDate = urlParams.get('start_date') || '';
      const endDate = urlParams.get('end_date') || '';

      // Build the AJAX URL
      let ajaxURL = `home.php?action=${action}&filter=${filter}`;
      if (filter === 'custom') {
        ajaxURL += `&start_date=${startDate}&end_date=${endDate}`;
      }

      // Fetch data via AJAX
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
                  type: 'line',
                  height: 350,
                },
                stroke: {
                  curve: 'smooth',
                },
                markers: {
                  size: 4,
                },
                series: [
                  {
                    name: 'Salary Amount',
                    data: data.SalaryAmount.map(item => item.y),
                  },
                  {
                    name: 'Mobile Amount',
                    data: data.MobileAmount.map(item => item.y),
                  },
                  {
                    name: 'Other Amount',
                    data: data.OtherAmount.map(item => item.y),
                  },
                  {
                    name: 'Fuel Amount',
                    data: data.FuelAmount.map(item => item.y),
                  },
                ],
                xaxis: {
                  categories: data.SalaryAmount.map(item => item.x),
                  title: {
                    text: 'Date',
                  },
                  labels: {
                    rotate: -45,
                  },
                },
                yaxis: {
                  title: {
                    text: 'Amount (₱)',
                  },
                },
                title: {
                  text: 'Expenses and Fuel Over Time',
                  align: 'center',
                },
                tooltip: {
                  y: {
                    formatter: function (val) {
                      return '₱' + val.toFixed(2);
                    },
                  },
                },
              };
              break;

              case 'revenue':
              chartOptions = {
                chart: {
                  type: 'line',
                  height: 350,
                  toolbar: {
                    show: false
                  },
                  zoom: {
                    enabled: true
                  }
                },
                colors: ['#0d6efd'], // Blue
                stroke: {
                  curve: 'smooth',
                  width: 2
                },
                markers: {
                  size: 4,
                  hover: {
                    size: 6
                  }
                },
                series: [
                  {
                    name: 'Total Revenue',
                    data: data.map(item => item.y),
                  },
                ],
                xaxis: {
                  categories: data.map(item => item.x),
                  title: {
                    text: 'Date',
                  },
                  labels: {
                    rotate: -45,
                    style: {
                      fontSize: '12px'
                    }
                  },
                },
                yaxis: {
                  title: {
                    text: 'Amount (₱)',
                  },
                  labels: {
                    formatter: function (val) {
                      return '₱' + val;
                    }
                  }
                },
                title: {
                  text: 'Revenue Trends',
                  align: 'center',
                  style: {
                    fontSize: '16px',
                    fontWeight: 'bold'
                  }
                },
                tooltip: {
                  y: {
                    formatter: function (val) {
                      return '₱' + val.toFixed(2);
                    },
                    title: {
                      formatter: function (seriesName) {
                        return seriesName;
                      }
                    }
                  }
                },
                legend: {
                  position: 'top',
                  horizontalAlign: 'center',
                  floating: false,
                  fontSize: '14px',
                  markers: {
                    width: 12,
                    height: 12,
                    radius: 0
                  }
                }
              };
              break;

            case 'profit':
              chartOptions = {
                chart: {
                  type: 'area',
                  height: 350,
                  toolbar: {
                    show: false
                  },
                  zoom: {
                    enabled: true
                  }
                },
                colors: ['#28a745'], // Green
                stroke: {
                  curve: 'smooth',
                  width: 2
                },
                markers: {
                  size: 4,
                  hover: {
                    size: 6
                  }
                },
                fill: {
                  type: 'gradient',
                  gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 90, 100]
                  }
                },
                series: [
                  {
                    name: 'Total Profit',
                    data: data.map(item => item.y),
                  },
                ],
                xaxis: {
                  categories: data.map(item => item.x),
                  title: {
                    text: 'Date',
                  },
                  labels: {
                    rotate: -45,
                    style: {
                      fontSize: '12px'
                    }
                  },
                },
                yaxis: {
                  title: {
                    text: 'Amount (₱)',
                  },
                  labels: {
                    formatter: function (val) {
                      return '₱' + val;
                    }
                  }
                },
                title: {
                  text: 'Profit Margins',
                  align: 'center',
                  style: {
                    fontSize: '16px',
                    fontWeight: 'bold'
                  }
                },
                tooltip: {
                  y: {
                    formatter: function (val) {
                      return '₱' + val.toFixed(2);
                    },
                    title: {
                      formatter: function (seriesName) {
                        return seriesName;
                      }
                    }
                  }
                },
                legend: {
                  position: 'top',
                  horizontalAlign: 'center',
                  floating: false,
                  fontSize: '14px',
                  markers: {
                    width: 12,
                    height: 12,
                    radius: 0
                  }
                }
              };
              break;

            case 'transactions':
              chartOptions = {
                chart: {
                  type: 'area',
                  height: 350,
                  toolbar: {
                    show: false
                  },
                  zoom: {
                    enabled: true
                  }
                },
                colors: ['#FF8C00'], // Orange
                stroke: {
                  curve: 'smooth',
                  width: 2
                },
                markers: {
                  size: 4,
                  hover: {
                    size: 6
                  }
                },
                fill: {
                  type: 'gradient',
                  gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 90, 100]
                  }
                },
                series: [
                  {
                    name: 'Total Transactions',
                    data: data.map(item => item.y),
                  },
                ],
                xaxis: {
                  categories: data.map(item => item.x),
                  title: {
                    text: 'Date',
                  },
                  labels: {
                    rotate: -45,
                    style: {
                      fontSize: '12px'
                    }
                  },
                },
                yaxis: {
                  title: {
                    text: 'Total Transactions',
                  },
                  labels: {
                    formatter: function (val) {
                      return val;
                    }
                  }
                },
                title: {
                  text: 'Transactions Over Time',
                  align: 'center',
                  style: {
                    fontSize: '16px',
                    fontWeight: 'bold'
                  }
                },
                tooltip: {
                  y: {
                    formatter: function (val) {
                      return val;
                    },
                    title: {
                      formatter: function (seriesName) {
                        return seriesName;
                      }
                    }
                  }
                },
                legend: {
                  position: 'top',
                  horizontalAlign: 'center',
                  floating: false,
                  fontSize: '14px',
                  markers: {
                    width: 12,
                    height: 12,
                    radius: 0
                  }
                }
              };
              break;

            case 'fuel':
              chartOptions = {
                chart: {
                  type: 'bar',
                  height: 350,
                  toolbar: {
                    show: false
                  },
                  zoom: {
                    enabled: true
                  }
                },
                colors: ['#17a2b8'], // Teal for Fuel
                plotOptions: {
                  bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                  },
                },
                dataLabels: {
                  enabled: false
                },
                series: [
                  {
                    name: 'Fuel Consumption (Liters)',
                    data: data.map(item => item.y),
                  },
                ],
                xaxis: {
                  categories: data.map(item => item.x),
                  title: {
                    text: 'Date',
                  },
                  labels: {
                    rotate: -45,
                    style: {
                      fontSize: '12px'
                    }
                  },
                },
                yaxis: {
                  title: {
                    text: 'Liters',
                  },
                  labels: {
                    formatter: function (val) {
                      return val + ' L';
                    }
                  }
                },
                title: {
                  text: 'Fuel Consumption Over Time',
                  align: 'center',
                  style: {
                    fontSize: '16px',
                    fontWeight: 'bold'
                  }
                },
                tooltip: {
                  y: {
                    formatter: function (val) {
                      return val + ' L';
                    },
                    title: {
                      formatter: function (seriesName) {
                        return seriesName;
                      }
                    }
                  }
                },
                legend: {
                  position: 'top',
                  horizontalAlign: 'center',
                  floating: false,
                  fontSize: '14px',
                  markers: {
                    width: 12,
                    height: 12,
                    radius: 0
                  }
                }
              };
              break;

            case 'truckDistribution':
              chartOptions = {
                chart: {
                  type: 'bar',
                  height: 350,
                },
                series: [
                  {
                    name: 'Number of Transactions',
                    data: data.map(item => item.y),
                  },
                ],
                xaxis: {
                  categories: data.map(item => item.x),
                  title: {
                    text: 'Plate Number',
                  },
                  labels: {
                    rotate: -45,
                  },
                },
                yaxis: {
                  title: {
                    text: 'Number of Transactions',
                  },
                },
                title: {
                  text: 'Truck Usage Over Time',
                  align: 'center',
                },
                tooltip: {
                  y: {
                    formatter: function (val) {
                      return val + ' transactions';
                    },
                  },
                },
                plotOptions: {
                  bar: {
                    distributed: true,
                    dataLabels: {
                      position: 'top',
                    },
                  },
                },
                dataLabels: {
                  enabled: true,
                  formatter: function (val) {
                    return val;
                  },
                  offsetY: -20,
                  style: {
                    fontSize: '12px',
                    colors: ['#304758'],
                  },
                },
              };
              break;
          }

          // Apply theme to the chart options
          applyThemeToChartOptions(getActiveTheme(), chartOptions);

          // Create ApexChart
          let chartContainer = document.getElementById(containerId);
          if (!chartContainer) {
            console.error('Chart container not found:', containerId);
            return;
          }

          // Clear any existing chart to prevent duplicates
          chartContainer.innerHTML = '';

          let apexChart = new ApexCharts(chartContainer, chartOptions);
          apexChart.render();
        })
        .catch(error => console.error('Error fetching data for chart:', metric, error));
    }

    // Handle theme changes and update charts dynamically
    document.querySelectorAll('.dark-layout, .light-layout').forEach(button => {
      button.addEventListener('click', () => {
        metrics.forEach(metric => fetchAndRenderChart(metric));
      });
    });
  });
</script>



<!-- Include your footer -->
<?php include '../officer/footer.php'; ?>