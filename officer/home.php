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
      // Fetch revenue trends over time
      $query = "SELECT Date, SUM(Amount) as RateAmount FROM transactiongroup WHERE Date BETWEEN '$startDateEscaped' AND '$endDateEscaped' GROUP BY Date ORDER BY Date ASC";
      $result = mysqli_query($conn, $query);
      $data = [];
      while ($row = mysqli_fetch_assoc($result)) {
        // Total Revenue = RateAmount + TotalExpenses
        // Fetch totalExpenses for the date
        $date = $row['Date'];
        $queryExpense = "SELECT IFNULL(SUM(TotalExpense), 0) as TotalExpense FROM expenses WHERE Date = '$date'";
        $resultExpense = mysqli_query($conn, $queryExpense);
        $rowExpense = mysqli_fetch_assoc($resultExpense);
        $totalExpense = $rowExpense['TotalExpense'];

        $queryFuel = "SELECT IFNULL(SUM(Amount), 0) as FuelAmount FROM fuel WHERE Date = '$date'";
        $resultFuel = mysqli_query($conn, $queryFuel);
        $rowFuel = mysqli_fetch_assoc($resultFuel);
        $fuelAmount = $rowFuel['FuelAmount'];

        $totalExpense += $fuelAmount;

        $totalRevenue = $row['RateAmount'] + $totalExpense;

        $data[] = ['x' => $date, 'y' => (float) $totalRevenue];
      }

      // Sort data by date
      usort($data, function ($a, $b) {
        return strtotime($a['x']) - strtotime($b['x']);
      });

      echo json_encode($data);
      exit;

    case 'getProfitData':
      // Aggregating data by month if too many records
      $dateRange = (strtotime($endDateEscaped) - strtotime($startDateEscaped)) / (60 * 60 * 24); // Calculate date range in days

      if ($dateRange > 30) {
        // Aggregate by month
        $query = "SELECT DATE_FORMAT(tg.Date, '%Y-%m') as Month, SUM(tg.Amount) as Revenue, 
                    IFNULL(SUM(e.TotalExpense),0) + IFNULL(SUM(f.Amount),0) as Expenses
                    FROM transactiongroup tg
                    LEFT JOIN expenses e ON tg.ExpenseID = e.ExpenseID
                    LEFT JOIN fuel f ON e.FuelID = f.FuelID
                    WHERE tg.Date BETWEEN '$startDateEscaped' AND '$endDateEscaped'
                    GROUP BY Month
                    ORDER BY Month ASC";
      } else {
        // Fetch data by day
        $query = "SELECT tg.Date, SUM(tg.Amount) as Revenue, 
                    IFNULL(SUM(e.TotalExpense),0) + IFNULL(SUM(f.Amount),0) as Expenses
                    FROM transactiongroup tg
                    LEFT JOIN expenses e ON tg.ExpenseID = e.ExpenseID
                    LEFT JOIN fuel f ON e.FuelID = f.FuelID
                    WHERE tg.Date BETWEEN '$startDateEscaped' AND '$endDateEscaped'
                    GROUP BY tg.Date
                    ORDER BY tg.Date ASC";
      }

      $result = mysqli_query($conn, $query);
      $data = [];

      while ($row = mysqli_fetch_assoc($result)) {
        $profit = (float) $row['Revenue'] - ((float) $row['Expenses']);
        $date = isset($row['Month']) ? $row['Month'] : $row['Date'];
        $data[] = ['x' => $date, 'y' => $profit];
      }

      // Sort data by date
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

    // List of metrics
    const metrics = ['expenses', 'revenue', 'profit', 'transactions', 'fuel'];

    metrics.forEach(metric => {
      fetchAndRenderChart(metric);
    });

    function fetchAndRenderChart(metric) {
      // Determine action and container ID based on metric
      let action = '';
      let containerId = '';
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
                  type: 'line', // Change chart type to line
                  height: 350
                },
                stroke: {
                  curve: 'smooth'  // Smooth curve
                },
                markers: {
                  size: 4  // Data points shown as circles
                },
                series: [
                  {
                    name: 'Salary Amount',
                    data: data.SalaryAmount.map(item => item.y)
                  },
                  {
                    name: 'Mobile Amount',
                    data: data.MobileAmount.map(item => item.y)
                  },
                  {
                    name: 'Other Amount',
                    data: data.OtherAmount.map(item => item.y)
                  },
                  {
                    name: 'Fuel Amount',
                    data: data.FuelAmount.map(item => item.y)
                  }
                ],
                xaxis: {
                  categories: data.SalaryAmount.map(item => item.x), // Use the date from the first series
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
                  text: 'Expenses and Fuel Over Time',
                  align: 'center'
                },
                tooltip: {
                  y: {
                    formatter: function (val) {
                      return '₱' + val.toFixed(2);
                    }
                  }
                }
              };

              break;

            case 'revenue':
              chartOptions = {
                chart: {
                  type: 'line',
                  height: 350
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
                },
                tooltip: {
                  y: {
                    formatter: function (val) {
                      return '₱' + val.toFixed(2);
                    }
                  }
                }
              };
              break;

            case 'profit':
              chartOptions = {
                chart: {
                  type: 'area',
                  height: 350
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
                },
                tooltip: {
                  y: {
                    formatter: function (val) {
                      return '₱' + val.toFixed(2);
                    }
                  }
                }
              };
              break;

            case 'transactions':
              chartOptions = {
                chart: {
                  type: 'area', // Line area chart
                  height: 350,
                  toolbar: {
                    show: false // Optionally hide toolbar for clean design
                  }
                },
                series: [{
                  name: 'Total Transactions',
                  data: data.map(item => item.y)
                }],
                colors: ['#00D1B2'], // Set the line and area color (you can use any color code)
                fill: {
                  type: 'gradient', // Use gradient fill for the area under the line
                  gradient: {
                    shade: 'light', // Shade of the gradient
                    gradientToColors: ['#FF9A8B'], // Color at the top of the area
                    opacityFrom: 0.3, // Opacity of the starting gradient
                    opacityTo: 0.1, // Opacity of the ending gradient
                    stops: [0, 100] // Gradient stops
                  }
                },
                xaxis: {
                  categories: data.map(item => item.x), // Dates from the data
                  title: {
                    text: 'Date'
                  },
                  labels: {
                    rotate: -45
                  }
                },
                yaxis: {
                  title: {
                    text: 'Total Transactions'
                  }
                },
                title: {
                  text: 'Transactions Over Time',
                  align: 'center'
                },
                tooltip: {
                  y: {
                    formatter: function (val) {
                      return val; // Display transaction count
                    }
                  }
                }
              };
              break;

            case 'fuel':
              chartOptions = {
                chart: {
                  type: 'bar',
                  height: 350
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
                },
                tooltip: {
                  y: {
                    formatter: function (val) {
                      return val + ' L';
                    }
                  }
                }
              };
              break;

            default:
              chartOptions = {
                chart: {
                  type: 'bar',
                  height: 350
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
                },
                tooltip: {
                  y: {
                    formatter: function (val) {
                      return val;
                    }
                  }
                }
              };
          }

          // Create ApexChart
          let chartContainer = document.getElementById(containerId);
          let apexChart = new ApexCharts(chartContainer, chartOptions);
          apexChart.render();
        })
        .catch(error => {
          console.error('Error fetching data for ' + metric + ':', error);
          let chartContainer = document.getElementById(containerId);
          chartContainer.innerHTML = '<p>Error loading data.</p>';
        });
    }
  });
</script>

<!-- Include your footer -->
<?php include '../officer/footer.php'; ?>