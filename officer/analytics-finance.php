<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css"
    rel="stylesheet">

<div class="body-wrapper">
    <div class="container-fluid">
        <?php
        // Including sidebar if it exists
        $sidebar_path = '../officer/sidebar.php';
        if (file_exists($sidebar_path)) {
            include $sidebar_path;
        } else {
            echo "<!-- Sidebar not found at $sidebar_path -->";
        }
        ?>
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-4 mb-sm-0 card-title">Analytics</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="border-bottom py-2 px-4 mb-4">Finances</h5>
        <div class="container-fluid p-0">
            <div class="row">

                <!-- Monthly and Yearly Trends Card -->
                <div class="col-lg-6 mb-5">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="card-title mb-0">Monthly and Yearly Trends</h5>

                                <!-- Year Dropdown Filter in the card -->
                                <div class="dropdown">
                                    <select id="yearFilter" class="form-select form-select-sm"
                                        onchange="fetchTrendsData()" style="width: auto;">
                                        <?php
                                        // Determine the minimum year from both the invoices and expenses tables
                                        $minYearQuery = "
                            SELECT MIN(Year) as MinYear FROM (
                                SELECT MIN(YEAR(BillingStartDate)) AS Year FROM invoices
                                UNION
                                SELECT MIN(YEAR(Date)) AS Year FROM expenses
                            ) as YearRange";
                                        $minYearResult = $conn->query($minYearQuery);
                                        $minYearRow = $minYearResult->fetch_assoc();
                                        $minYear = $minYearRow['MinYear'] ?? date('Y');

                                        // Set the current year as the maximum year
                                        $currentYear = date('Y');

                                        // Populate dropdown from the minimum year to the current year
                                        for ($year = $minYear; $year <= $currentYear; $year++) {
                                            echo "<option value='$year'>$year</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <p class="card-subtitle mb-4">Revenue, Expenses, and Profit</p>
                            <canvas id="monthlyYearlyTrendsChart" height="120"></canvas>
                        </div>
                    </div>
                </div>


                <script>
                    // Initialize the chart
                    let monthlyYearlyTrendsChart;

                    // Function to fetch data based on the selected year
                    function fetchTrendsData() {
                        const selectedYear = document.getElementById('yearFilter').value;

                        // AJAX request to get data for the selected year
                        const xhr = new XMLHttpRequest();
                        xhr.open('GET', `fetch_trends_data.php?year=${selectedYear}`, true);
                        xhr.onload = function () {
                            if (this.status === 200) {
                                const data = JSON.parse(this.responseText);
                                if (data && data.labels.length > 0) {
                                    updateChart(data.labels, data.revenues, data.expenses, data.profits);
                                } else {
                                    alert('No data found for the selected year. Please choose another year.');
                                }
                            } else {
                                console.error('Failed to fetch data');
                            }
                        };
                        xhr.send();
                    }

                    // Function to update the chart with new data
                    function updateChart(labels, revenues, expenses, profits) {
                        if (monthlyYearlyTrendsChart) {
                            monthlyYearlyTrendsChart.destroy();
                        }

                        const ctx = document.getElementById('monthlyYearlyTrendsChart').getContext('2d');
                        monthlyYearlyTrendsChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Revenue',
                                    data: revenues,
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    fill: false,
                                    tension: 0.3
                                },
                                {
                                    label: 'Expenses',
                                    data: expenses,
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    fill: false,
                                    tension: 0.3
                                },
                                {
                                    label: 'Profit',
                                    data: profits,
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    fill: false,
                                    tension: 0.3
                                }
                                ]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    x: {
                                        beginAtZero: false,
                                        title: {
                                            display: true,
                                            text: 'Months'
                                        },
                                        ticks: {
                                            callback: function (value, index) {
                                                const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                                                return months[index];
                                            }
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Amount'
                                        },
                                        ticks: {
                                            callback: function (value) {
                                                return value.toLocaleString(); // Format with commas
                                            }
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top'
                                    }
                                }
                            }
                        });
                    }

                    // Initial fetch for the current year's data
                    document.addEventListener('DOMContentLoaded', function () {
                        fetchTrendsData();
                    });
                </script>

                <!-- Revenue Forecast Card -->
                <div class="col-lg-6 mb-5">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Revenue Forecast</h5>
                            <p class="card-subtitle mb-0">Projected monthly revenue trends for the next 6 months</p>
                            <canvas id="revenueForecastChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        fetchRevenueForecast();
                    });

                    function fetchRevenueForecast() {
                        fetch('https://epm-analytics-13715bf8762f.herokuapp.com/predict_finance', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                months: 6
                            })
                        })
                            .then(response => {
                                if (!response.ok) {
                                    return response.json().then(err => {
                                        throw new Error(err.error || `Server error: ${response.statusText}`);
                                    });
                                }
                                return response.json();
                            })
                            .then(data => {
                                console.log("Forecast Data Fetched:", data);

                                // Check if the data has the forecast field
                                if (!data.forecast || !Array.isArray(data.forecast)) {
                                    throw new Error('Invalid data format received from server');
                                }

                                const labels = data.forecast.map(item => item.month);
                                const forecastedRevenue = data.forecast.map(item => item.predicted_revenue);

                                renderRevenueForecastChart(labels, forecastedRevenue);
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                const canvas = document.getElementById('revenueForecastChart');
                                const ctx = canvas.getContext('2d');
                                ctx.clearRect(0, 0, canvas.width, canvas.height);
                                ctx.font = '14px Arial';
                                ctx.fillStyle = 'red';
                                ctx.textAlign = 'center';
                                ctx.fillText(`Error: ${error.message}`, canvas.width / 2, canvas.height / 2);
                            });
                    }

                    let revenueForecastChart;

                    function renderRevenueForecastChart(labels, forecastData) {
                        const ctx = document.getElementById('revenueForecastChart').getContext('2d');
                        if (revenueForecastChart) {
                            revenueForecastChart.destroy();
                        }

                        revenueForecastChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Forecasted Revenue',
                                    data: forecastData,
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    fill: false,
                                    borderDash: [5, 5]
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Months'
                                        }
                                    },
                                    y: {
                                        title: {
                                            display: true,
                                            text: 'Revenue'
                                        },
                                        beginAtZero: true
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top'
                                    }
                                }
                            }
                        });
                    }
                </script>


                <!-- Top Expenses Card -->
                <div class="col-lg-6">
                    <div class="card bg-secondary-subtle overflow-hidden shadow-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="card-title">Top 10 Expenses</h5>

                                <!-- Year Dropdown Filter for Top Expenses -->
                                <div class="dropdown">
                                    <?php
                                    // Determine the minimum year from the expenses and fuel tables
                                    $minYearQuery = "
            SELECT MIN(Year) as MinYear FROM (
              SELECT MIN(YEAR(Date)) AS Year FROM expenses
              UNION
              SELECT MIN(YEAR(Date)) AS Year FROM fuel
            ) as YearRange";
                                    $minYearResult = $conn->query($minYearQuery);
                                    $minYearRow = $minYearResult->fetch_assoc();
                                    $minYear = $minYearRow['MinYear'] ?? date('Y');

                                    // Set the current year as the maximum year
                                    $currentYear = date('Y');
                                    ?>
                                    <select id="topExpensesYearFilter" class="form-select form-select-sm"
                                        onchange="fetchTopExpensesData()" style="width: auto;">
                                        <?php
                                        // Populate dropdown from the minimum year to the current year
                                        for ($year = $minYear; $year <= $currentYear; $year++) {
                                            echo "<option value='$year'>$year</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <p class="card-subtitle mb-4">Overview of the highest cost drivers including fuel</p>
                            <canvas id="topExpensesChart" height="120"></canvas>
                        </div>
                    </div>
                </div>

                <script>
                    // Initialize the chart for top expenses
                    let topExpensesChart;

                    // Function to fetch top expenses data based on the selected year
                    function fetchTopExpensesData() {
                        const selectedYear = document.getElementById('topExpensesYearFilter').value;

                        // AJAX request to get data for the selected year
                        const xhr = new XMLHttpRequest();
                        xhr.open('GET', `fetch_top_expenses_data.php?year=${selectedYear}`, true);
                        xhr.onload = function () {
                            if (this.status === 200) {
                                const data = JSON.parse(this.responseText);
                                updateTopExpensesChart(data.labels, data.datasets);
                            } else {
                                console.error('Failed to fetch top expenses data');
                            }
                        };
                        xhr.send();
                    }

                    // Function to update the top expenses chart with new data
                    function updateTopExpensesChart(labels, datasets) {
                        if (topExpensesChart) {
                            topExpensesChart.destroy();
                        }

                        const ctx = document.getElementById('topExpensesChart').getContext('2d');
                        topExpensesChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: datasets.map((dataset, index) => ({
                                    label: dataset.label,
                                    data: dataset.data,
                                    backgroundColor: `rgba(${Math.floor(Math.random() * 256)}, ${Math.floor(Math.random() * 256)}, ${Math.floor(Math.random() * 256)}, 0.6)`,
                                    borderColor: `rgba(${Math.floor(Math.random() * 256)}, ${Math.floor(Math.random() * 256)}, ${Math.floor(Math.random() * 256)}, 1)`,
                                    borderWidth: 1
                                }))
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    tooltip: {
                                        callbacks: {
                                            label: function (tooltipItem) {
                                                return tooltipItem.dataset.label + ': ' + tooltipItem.raw.toLocaleString(); // Format with commas
                                            }
                                        }
                                    },
                                    legend: {
                                        display: true,
                                        position: 'top'
                                    }
                                },
                                scales: {
                                    x: {
                                        stacked: true,
                                        title: {
                                            display: true,
                                            text: 'Months'
                                        }
                                    },
                                    y: {
                                        stacked: true,
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Total Expense Amount'
                                        },
                                        ticks: {
                                            callback: function (value) {
                                                return value.toLocaleString(); // Add commas for readability
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Initial fetch for the current year's data
                    document.addEventListener('DOMContentLoaded', function () {
                        fetchTopExpensesData();
                    });
                </script>

                <!-- Fuel Expenses vs Revenue Card -->
                <div class="col-lg-6">
                    <div class="card bg-danger-subtle overflow-hidden shadow-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="card-title mb-0">Fuel Expenses vs. Revenue</h5>

                                <!-- Year Filter Dropdown -->
                                <div>
                                    <select id="fuelRevenueYearFilter" class="form-select form-select-sm"
                                        onchange="fetchFuelRevenueData()" style="width: auto;">
                                        <?php
                                        // Determine the minimum year from the fuel and invoices tables
                                        $minYearQuery = "
                  SELECT MIN(Year) as MinYear FROM (
                    SELECT MIN(YEAR(Date)) AS Year FROM fuel
                    UNION
                    SELECT MIN(YEAR(BillingStartDate)) AS Year FROM invoices
                  ) as YearRange";
                                        $minYearResult = $conn->query($minYearQuery);
                                        $minYearRow = $minYearResult->fetch_assoc();
                                        $minYear = $minYearRow['MinYear'] ?? date('Y');
                                        $currentYear = date('Y');

                                        // Populate dropdown from the minimum year to the current year
                                        for ($year = $minYear; $year <= $currentYear; $year++) {
                                            echo "<option value='$year'>$year</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <p class="card-subtitle">Analyze the relationship between fuel expenses and revenue over
                                time.</p>
                            <canvas id="fuelRevenueCorrelationChart" height="120"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Modal for No Data -->
                <div class="modal fade" id="noDataModal" tabindex="-1" aria-labelledby="noDataModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="noDataModalLabel">No Data Available</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                No data is available for the selected year. Please choose a different year.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Function to fetch data for the selected year
            function fetchFuelRevenueData() {
                const selectedYear = document.getElementById('yearFilter').value;

                fetch(`fetch_fuel_revenue_data.php?year=${selectedYear}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Fetched Data:', data); // Debugging log
                        if (data.fuelExpenses.length > 0 && data.revenues.length > 0) {
                            renderFuelRevenueChart(data.months, data.fuelExpenses, data.revenues);
                        } else {
                            alert("No data available for the selected year.");
                            if (window.fuelRevenueChart) {
                                window.fuelRevenueChart.destroy();
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching data:', error));
            }

            // Function to render the chart
            function renderFuelRevenueChart(months, fuelExpenses, revenues) {
                const ctx = document.getElementById('fuelRevenueCorrelationChart').getContext('2d');
                if (window.fuelRevenueChart) {
                    window.fuelRevenueChart.destroy();
                }

                window.fuelRevenueChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: [{
                            label: 'Fuel Expenses (PHP)',
                            data: fuelExpenses,
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            fill: false
                        },
                        {
                            label: 'Revenue (PHP)',
                            data: revenues,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            fill: false
                        }
                        ]
                    },
                    options: {
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Months'
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Amount (PHP)'
                                },
                                ticks: {
                                    callback: function (value) {
                                        return value.toLocaleString(); // Format with commas for readability
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return `${context.dataset.label}: PHP ${context.raw.toLocaleString()}`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Initial fetch when the page loads
            document.addEventListener('DOMContentLoaded', fetchFuelRevenueData);
        </script>
    </div>
</div>

<script src="../assets/js/vendor.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/libs/simplebar/dist/simplebar.min.js"></script>
<script src="../assets/js/theme/app.init.js"></script>
<script src="../assets/js/theme/theme.js"></script>
<script src="../assets/js/theme/app.min.js"></script>
<script src="../assets/js/theme/sidebarmenu-default.js"></script>
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
<script src="../assets/libs/owl.carousel/dist/owl.carousel.min.js"></script>
<script src="../assets/js/apps/productDetail.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
include '../officer/footer.php';
?>