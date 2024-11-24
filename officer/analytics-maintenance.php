<?php
session_start();
include '../includes/db_connection.php';
include 'header.php';
?>

<link href="../assets/libs/apexcharts/dist/apexcharts.css" rel="stylesheet">

<div class="body-wrapper">
    <div class="container-fluid">
        <!-- Page Title -->
        <div class="card card-body py-3 mb-4">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Maintenance Forecasting</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="../officer/home.php" class="text-muted text-decoration-none">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards Row -->
        <div class="row mb-4">
            <!-- Model Performance Card -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="card-title mb-0">Model Accuracy</h6>
                            <div class="accuracy-badge">
                                <span class="badge bg-primary total-trucks">Loading...</span>
                            </div>
                        </div>
                        <h4 class="mb-0 total-predictions">0%</h4>
                        <small class="text-muted accuracy-kpi">Loading...</small>
                    </div>
                </div>
            </div>

            <!-- Preventive Maintenance Savings -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="card-title mb-0">Potential Cost Savings</h6>
                            <div class="accuracy-badge">
                                <span class="badge bg-success maintenance-count">Loading...</span>
                            </div>
                        </div>
                        <h4 class="mb-0 maintenance-needed">₱0</h4>
                        <small class="text-muted savings-kpi">Loading...</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add container for charts -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Maintenance Forecast</h5>
                        <div id="maintenanceCharts" style="min-height: 400px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add container for maintenance table -->
        <div class="row">
            <div class="col-12">
                <div class="card maintenance-table-container">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0">Maintenance Schedule</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="maintenanceTable">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Plate Number</th>
                                        <th>Model</th>
                                        <th>Status</th>
                                    </tr>
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

<script src="../assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../assets/libs/apexcharts/dist/apexcharts.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    try {
        // Fetch evaluation data first
        const evalResponse = await fetch('http://127.0.0.1:5000/evaluate_maintenance');
        if (!evalResponse.ok) throw new Error('Failed to fetch evaluation data');
        const evalData = await evalResponse.json();
        
        if (evalData.status === 'success') {
            updateCards(evalData);
        }

        // Then fetch predictions
        const response = await fetch('http://127.0.0.1:5000/predict_maintenance');
        if (!response.ok) {
            throw new Error(`Failed to fetch predictions: ${response.status}`);
        }
        const data = await response.json();
        
        if (data.status === 'success') {
            const monthlyData = prepareMonthlyData(data.forecast);
            
            // Initialize chart only if element exists
            const chartElement = document.querySelector("#maintenanceCharts");
            if (chartElement) {
                try {
                    const maintenanceCharts = new ApexCharts(
                        chartElement,
                        getChartOptions(monthlyData)
                    );
                    await maintenanceCharts.render();
                } catch (chartError) {
                    console.error('Chart initialization error:', chartError);
                }
            }

            // Update table
            const tableBody = document.querySelector('#maintenanceTable tbody');
            if (tableBody) {
                tableBody.innerHTML = generateMaintenanceRows(monthlyData);
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
    }
});

// Update the chart options
function getChartOptions(monthlyData) {
    const sixMonthsForecast = Object.entries(monthlyData)
        .slice(0, 6)
        .reduce((acc, [month, data]) => {
            acc[month] = data;
            return acc;
        }, {});

    return {
        series: [{
            name: 'Trucks Needing Maintenance',
            data: Object.entries(sixMonthsForecast).map(([month, data]) => ({
                x: month,
                y: data.maintenance,
                trucks: data.trucks.filter(t => t.needs_maintenance)
            }))
        }],
        chart: {
            type: 'bar',
            height: 350,
            toolbar: {
                show: true
            },
            animations: {
                enabled: true
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '60%',
                distributed: true
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return val > 0 ? `${val} truck${val > 1 ? 's' : ''}` : 'No Maintenance Needed';
            }
        },
        tooltip: {
            shared: true,
            intersect: false,
            custom: function({ series, seriesIndex, dataPointIndex, w }) {
                const trucks = w.config.series[seriesIndex].data[dataPointIndex].trucks;
                return `
                    <div class="p-2">
                        <strong>${w.globals.labels[dataPointIndex]}</strong><br/>
                        ${trucks.length > 0 ? 
                            trucks.map(t => `${t.plate_no} (${t.brand})`).join('<br/>') : 
                            'No Maintenance Needed'}
                    </div>
                `;
            }
        },
        colors: ['#4CAF50', '#2196F3', '#FFC107', '#FF5722', '#9C27B0', '#795548'],
        xaxis: {
            categories: Object.keys(sixMonthsForecast)
        },
        yaxis: {
            title: {
                text: 'Number of Trucks'
            },
            max: 4,
            min: 0,
            tickAmount: 4
        }
    };
}

// Helper function to show errors
function showError(message) {
    const errorAlert = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    document.querySelector('.container-fluid').insertAdjacentHTML('afterbegin', errorAlert);
}

function updateElement(selector, value) {
    const element = document.querySelector(selector);
    if (element) {
        element.textContent = value;
    } else {
        console.warn(`Element not found: ${selector}`);
    }
}

function generateMaintenanceRows(monthlyData) {
    const sixMonthsForecast = Object.entries(monthlyData)
        .slice(0, 6);
    
    return sixMonthsForecast.map(([month, data]) => {
        const maintenanceTrucks = data.trucks.filter(t => t.needs_maintenance);
        
        if (maintenanceTrucks.length === 0) {
            return `
                <tr>
                    <td>${month}</td>
                    <td colspan="3" class="text-center">
                        <span class="badge bg-success">No Maintenance Needed</span>
                    </td>
                </tr>
            `;
        }

        return `
            <tr>
                <td>${month}</td>
                <td>
                    ${maintenanceTrucks.map(t => t.plate_no).join(', ')}
                </td>
                <td>
                    ${maintenanceTrucks.map(t => t.brand).join(', ')}
                </td>
                <td>
                    <span class="badge bg-primary">
                        ${maintenanceTrucks.length} Truck${maintenanceTrucks.length > 1 ? 's' : ''} Need Maintenance
                    </span>
                </td>
            </tr>
        `;
    }).join('');
}

// Add filter functionality
document.getElementById('monthFilter').addEventListener('change', filterTable);

function filterTable() {
    const month = document.getElementById('monthFilter').value;
    const rows = document.querySelectorAll('#maintenanceTable tbody tr');
    
    rows.forEach(row => {
        const showMonth = month === 'all' || row.dataset.month === month;
        row.style.display = showMonth ? '' : 'none';
    });
}

// Add this function after the DOMContentLoaded event listener
function prepareMonthlyData(forecast) {
    const monthlyData = {};
    const seen = new Set(); // Track unique truck-month combinations
    
    forecast.forEach(prediction => {
        const month = prediction.month;
        const key = `${prediction.plate_no}-${month}`;
        
        if (seen.has(key)) {
            return; // Skip duplicate entries
        }
        seen.add(key);
        
        if (!monthlyData[month]) {
            monthlyData[month] = {
                total: 0,
                maintenance: 0,
                trucks: []
            };
        }
        
        monthlyData[month].total++;
        if (prediction.needs_maintenance) {
            monthlyData[month].maintenance++;
        }
        monthlyData[month].trucks.push(prediction);
    });
    
    return monthlyData;
}

// Update the cards section
function updateCards(evalData) {
    if (evalData && evalData.evaluation) {
        // Model Performance Card
        const accuracy = evalData.evaluation.accuracy || 0;
        document.querySelector('.total-predictions').textContent = `${accuracy}%`;
        document.querySelector('.accuracy-kpi').textContent = 'Model Accuracy';
        document.querySelector('.total-trucks').textContent = 'Performance Metrics';

        // Cost Savings Card
        const historicalCost = evalData.evaluation.data_stats.historical_cost || 0;
        const potentialSavings = evalData.evaluation.data_stats.potential_savings || 0;
        const savingsPercent = ((potentialSavings / historicalCost) * 100).toFixed(1);
        
        document.querySelector('.maintenance-needed').textContent = `₱${historicalCost.toLocaleString()}`;
        document.querySelector('.maintenance-count').textContent = 'Last Month\'s Cost';
        document.querySelector('.savings-kpi').textContent = 
            `Potential Savings: ₱${potentialSavings.toLocaleString()} (${savingsPercent}%)`;
    }
}
</script>

<?php 
include 'footer.php';
?>