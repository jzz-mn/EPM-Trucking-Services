<?php
session_start();
include 'header.php';
?>

<link href="../assets/libs/apexcharts/dist/apexcharts.css" rel="stylesheet">

<div class="body-wrapper">
    <div class="container-fluid">
        <!-- Page Title -->
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-space-between">
                        <h4 class="mb-4 mb-sm-0 card-title">Analytics</h4>
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

        <h5 class="border-bottom py-2 px-4 mb-4">Finance</h5>

        <!-- Summary Cards Row -->
        <div class="row mb-4">
            <!-- Revenue Summary Card -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="card-title mb-0">Revenue Forecast</h6>
                            <div class="accuracy-badge">
                                <span class="badge bg-success revenue-mae">Loading...</span>
                            </div>
                        </div>
                        <h4 class="mb-0 revenue-total">₱0.00</h4>
                        <small class="text-muted">Predicted Monthly Average</small>
                    </div>
                </div>
            </div>

            <!-- Expenses Summary Card -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="card-title mb-0">Expenses Forecast</h6>
                            <div class="accuracy-badge">
                                <span class="badge bg-primary expense-mae">Loading...</span>
                            </div>
                        </div>
                        <h4 class="mb-0 expense-total">₱0.00</h4>
                        <small class="text-muted">Predicted Monthly Average</small>
                    </div>
                </div>
            </div>

            <!-- Profit Summary Card -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="card-title mb-0">Profit Forecast</h6>
                            <div class="accuracy-badge">
                                <span class="badge bg-warning profit-mae">Loading...</span>
                            </div>
                        </div>
                        <h4 class="mb-0 profit-total">₱0.00</h4>
                        <small class="text-muted">Predicted Monthly Average</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#revenue-tab">Revenue</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#expenses-tab">Expenses</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#profit-tab">Profit</a>
                            </li>
                        </ul>
                        <div class="tab-content mt-4">
                            <div class="tab-pane fade show active" id="revenue-tab">
                                <div id="revenueChart"></div>
                            </div>
                            <div class="tab-pane fade" id="expenses-tab">
                                <div id="expensesChart"></div>
                            </div>
                            <div class="tab-pane fade" id="profit-tab">
                                <div id="profitChart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tables Row -->
        <div class="row">
            <!-- Revenue Table -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title fw-semibold mb-4">Revenue Forecast</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Amount</th>
                                        <th>±Error</th>
                                    </tr>
                                </thead>
                                <tbody id="revenueTable"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expenses Table -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title fw-semibold mb-4">Expenses Forecast</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Amount</th>
                                        <th>±Error</th>
                                    </tr>
                                </thead>
                                <tbody id="expensesTable"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profit Table -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title fw-semibold mb-4">Profit Forecast</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Amount</th>
                                        <th>±Error</th>
                                    </tr>
                                </thead>
                                <tbody id="profitTable"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../assets/libs/apexcharts/dist/apexcharts.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<link href="../assets/libs/datatables/datatables.min.css" rel="stylesheet">
<script src="../assets/libs/datatables/datatables.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', async function() {
        try {
            // Fetch predictions from API
            const response = await fetch('https://epm-analytics-13715bf8762f.herokuapp.com//predict_finance', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to fetch predictions');
            }

            const data = await response.json();

            if (data.status !== 'success') {
                throw new Error(data.message || 'Failed to get prediction data');
            }

            // Update MAE badges
            const revenueMae = data.metrics?.revenue_mae || 0;
            const expenseMae = data.metrics?.expense_mae || 0;

            document.querySelector('.revenue-mae').textContent =
                `₱${revenueMae.toLocaleString(undefined, {maximumFractionDigits: 2})}`;
            document.querySelector('.expense-mae').textContent =
                `₱${expenseMae.toLocaleString(undefined, {maximumFractionDigits: 2})}`;

            // Format data for revenue chart
            const revenueChartData = data.forecast.map(item => ({
                x: new Date(item.month).getTime(), // Convert to timestamp
                y: parseFloat(item.revenue)
            }));

            // Revenue chart options
            const revenueChartOptions = {
                series: [{
                    name: 'Forecasted Revenue',
                    data: revenueChartData
                }],
                chart: {
                    height: 350,
                    type: 'line',
                    zoom: {
                        enabled: true
                    }
                },
                colors: ['#2e7d32'],
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        datetimeFormatter: {
                            year: 'yyyy',
                            month: "MMM 'yy"
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Revenue (₱)'
                    },
                    labels: {
                        formatter: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            };

            // Create revenue chart
            if (document.querySelector("#revenueChart")) {
                const revenueChart = new ApexCharts(
                    document.querySelector("#revenueChart"),
                    revenueChartOptions
                );
                revenueChart.render();
            }

            // Group by month for revenue
            const monthlyRevenue = {};
            data.forecast.forEach(item => {
                const date = new Date(item.month);
                const monthYear = date.toLocaleString('default', {
                    year: 'numeric',
                    month: 'long'
                });
                monthlyRevenue[monthYear] = (monthlyRevenue[monthYear] || 0) + parseFloat(item.revenue);
            });

            // Create table rows for revenue
            const revenueRows = Object.entries(monthlyRevenue)
                .sort(([monthA], [monthB]) => new Date(monthA) - new Date(monthB))
                .map(([month, revenue]) => `
                <tr>
                    <td>${month}</td>
                    <td>₱${revenue.toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}</td>
                    <td>
                        <span class="badge bg-success">±₱${revenueMae.toLocaleString(undefined, {maximumFractionDigits: 2})}</span>
                    </td>
                </tr>
            `).join('');

            // Update revenue table
            const revenueTable = document.querySelector('#revenueTable');
            if (revenueTable) {
                revenueTable.innerHTML = revenueRows || '<tr><td colspan="3" class="text-center">No forecast data available</td></tr>';
            }

            // Format data for expenses chart
            const expensesChartData = data.forecast.map(item => ({
                x: new Date(item.month).getTime(),
                y: parseFloat(item.expenses)
            }));

            // Expenses chart options
            const expensesChartOptions = {
                series: [{
                    name: 'Forecasted Expenses',
                    data: expensesChartData
                }],
                chart: {
                    height: 350,
                    type: 'line',
                    zoom: {
                        enabled: true
                    }
                },
                colors: ['#d32f2f'], // Red for expenses
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        datetimeFormatter: {
                            year: 'yyyy',
                            month: "MMM 'yy"
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Expenses (₱)'
                    },
                    labels: {
                        formatter: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            };

            // Create expenses chart
            if (document.querySelector("#expensesChart")) {
                const expensesChart = new ApexCharts(
                    document.querySelector("#expensesChart"),
                    expensesChartOptions
                );
                expensesChart.render();
            }

            // Group by month for expenses
            const monthlyExpenses = {};
            data.forecast.forEach(item => {
                const date = new Date(item.month);
                const monthYear = date.toLocaleString('default', {
                    year: 'numeric',
                    month: 'long'
                });
                monthlyExpenses[monthYear] = (monthlyExpenses[monthYear] || 0) + parseFloat(item.expenses);
            });

            // Create table rows for expenses
            const expensesRows = Object.entries(monthlyExpenses)
                .sort(([monthA], [monthB]) => new Date(monthA) - new Date(monthB))
                .map(([month, expenses]) => `
                <tr>
                    <td>${month}</td>
                    <td>₱${expenses.toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}</td>
                    <td>
                        <span class="badge bg-primary">±₱${expenseMae.toLocaleString(undefined, {maximumFractionDigits: 2})}</span>
                    </td>
                </tr>
            `).join('');

            // Update expenses table
            const expensesTable = document.querySelector('#expensesTable');
            if (expensesTable) {
                expensesTable.innerHTML = expensesRows || '<tr><td colspan="3" class="text-center">No forecast data available</td></tr>';
            }

            // Format data for profit chart
            const profitChartData = data.forecast.map(item => ({
                x: new Date(item.month).getTime(),
                y: parseFloat(item.profit)
            }));

            // Profit chart options
            const profitChartOptions = {
                series: [{
                    name: 'Forecasted Profit',
                    data: profitChartData
                }],
                chart: {
                    height: 350,
                    type: 'line',
                    zoom: {
                        enabled: true
                    }
                },
                colors: ['#ffa726'], // Orange for profit
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        datetimeFormatter: {
                            year: 'yyyy',
                            month: "MMM 'yy"
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Profit (₱)'
                    },
                    labels: {
                        formatter: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            };

            // Create profit chart
            if (document.querySelector("#profitChart")) {
                const profitChart = new ApexCharts(
                    document.querySelector("#profitChart"),
                    profitChartOptions
                );
                profitChart.render();
            }

            // Update profit MAE badge
            const profitMae = data.metrics?.profit_mae || 0;
            document.querySelector('.profit-mae').textContent =
                `₱${profitMae.toLocaleString(undefined, {maximumFractionDigits: 2})}`;

            // Group by month for profit
            const monthlyProfit = {};
            data.forecast.forEach(item => {
                const date = new Date(item.month);
                const monthYear = date.toLocaleString('default', {
                    year: 'numeric',
                    month: 'long'
                });
                monthlyProfit[monthYear] = (monthlyProfit[monthYear] || 0) + parseFloat(item.profit);
            });

            // Create table rows for profit
            const profitRows = Object.entries(monthlyProfit)
                .sort(([monthA], [monthB]) => new Date(monthA) - new Date(monthB))
                .map(([month, profit]) => `
                <tr>
                    <td>${month}</td>
                    <td>₱${profit.toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}</td>
                    <td>
                        <span class="badge bg-warning">±₱${profitMae.toLocaleString(undefined, {maximumFractionDigits: 2})}</span>
                    </td>
                </tr>
            `).join('');

            // Update profit table
            const profitTable = document.querySelector('#profitTable');
            if (profitTable) {
                profitTable.innerHTML = profitRows || '<tr><td colspan="3" class="text-center">No forecast data available</td></tr>';
            }

            // Add after loading the data
            // Calculate and display averages
            const calculateAverage = (data, key) => {
                const sum = data.reduce((acc, item) => acc + parseFloat(item[key]), 0);
                return sum / data.length;
            };

            const revenueAvg = calculateAverage(data.forecast, 'revenue');
            const expenseAvg = calculateAverage(data.forecast, 'expenses');
            const profitAvg = calculateAverage(data.forecast, 'profit');

            updateElement('.revenue-total', `₱${revenueAvg.toLocaleString(undefined, {maximumFractionDigits: 2})}`);
            updateElement('.expense-total', `₱${expenseAvg.toLocaleString(undefined, {maximumFractionDigits: 2})}`);
            updateElement('.profit-total', `₱${profitAvg.toLocaleString(undefined, {maximumFractionDigits: 2})}`);

            // Update MAE badges to show as percentages of the averages
            document.querySelector('.revenue-mae').textContent =
                `±₱${revenueMae.toLocaleString(undefined, {maximumFractionDigits: 2})} (${((revenueMae/revenueAvg)*100).toFixed(1)}%)`;
            document.querySelector('.expense-mae').textContent =
                `±₱${expenseMae.toLocaleString(undefined, {maximumFractionDigits: 2})} (${((expenseMae/expenseAvg)*100).toFixed(1)}%)`;
            document.querySelector('.profit-mae').textContent =
                `±₱${profitMae.toLocaleString(undefined, {maximumFractionDigits: 2})} (${((profitMae/profitAvg)*100).toFixed(1)}%)`;

        } catch (error) {
            console.error('Error:', error);
            const errorAlert = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Failed to load predictions: ${error.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
            document.querySelector('.container-fluid').insertAdjacentHTML('afterbegin', errorAlert);
        }
    });

    function updateElement(selector, value) {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = value;
        } else {
            console.warn(`Element not found: ${selector}`);
        }
    }
</script>

<?php
include 'footer.php';
?>