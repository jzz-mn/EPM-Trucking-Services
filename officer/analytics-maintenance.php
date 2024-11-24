<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css"
    rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js for charts -->

<div class="body-wrapper">
    <div class="container-fluid">
        <?php
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

        <h5 class="border-bottom py-2 px-4 mb-4">Maintenance</h5>

        <div class="row">
            <!-- Prediction Accuracy Metrics (Moved to top) -->
            <div class="col-12 mb-4">
                <div class="card bg-info-subtle overflow-hidden shadow-none">
                    <div class="card-body">
                        <h5 class="card-title fw-semibold mb-4">Prediction Accuracy</h5>
                        <div id="accuracyMetrics" class="row">
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded text-center">
                                    <h6>Model Accuracy</h6>
                                    <div class="accuracy-value fs-4 fw-semibold">Loading...</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded text-center">
                                    <h6>Precision</h6>
                                    <div class="precision-value fs-4 fw-semibold">Loading...</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded text-center">
                                    <h6>Recall</h6>
                                    <div class="recall-value fs-4 fw-semibold">Loading...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance Predictions Chart -->
            <div class="col-12 mb-4">
                <div class="card bg-secondary-subtle overflow-hidden shadow-none">
                    <div class="card-body">
                        <h5 class="card-title fw-semibold mb-4">Maintenance Predictions</h5>
                        <div id="maintenancePredictionChart"></div>
                    </div>
                </div>
            </div>

            <!-- Detailed Predictions Table with Pagination -->
            <div class="col-12 mb-4">
                <div class="card bg-warning-subtle overflow-hidden shadow-none">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title fw-semibold mb-0">Detailed Maintenance Schedule</h5>
                            <div class="d-flex align-items-center">
                                <label class="me-2">Rows per page:</label>
                                <select id="rowsPerPage" class="form-select form-select-sm" style="width: auto;">
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Month</th>
                                        <th>Truck ID</th>
                                        <th>Truck Brand</th>
                                        <th>Maintenance Needed</th>
                                        <th>Probability</th>
                                    </tr>
                                </thead>
                                <tbody id="maintenancePredictionsTable">
                                    <tr>
                                        <td colspan="5" class="text-center">Loading predictions...</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="text-muted">
                                    Showing <span id="startRow">0</span> to <span id="endRow">0</span> of <span id="totalRows">0</span> entries
                                </div>
                                <div class="pagination-container">
                                    <button id="prevPage" class="btn btn-sm btn-outline-secondary me-2">&laquo; Previous</button>
                                    <button id="nextPage" class="btn btn-sm btn-outline-secondary">Next &raquo;</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../assets/libs/apexcharts/dist/apexcharts.min.js"></script>

<script>
// Initialize variables for pagination
let currentPage = 1;
let rowsPerPage = 5;
let allTableData = [];

// Wrap the main code in an async function
async function initializeDashboard() {
    try {
        console.log('Testing database connection...');
        const response = await fetch('http://127.0.0.1:5000/predict_maintenance', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        console.log('API Response Status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Received Data:', data);

        // Update accuracy metrics
        console.log('Updating accuracy metrics...');
        document.querySelector('.accuracy-value').textContent = `${(data.metrics.accuracy * 100).toFixed(1)}%`;
        document.querySelector('.precision-value').textContent = `${(data.metrics.precision * 100).toFixed(1)}%`;
        document.querySelector('.recall-value').textContent = `${(data.metrics.recall * 100).toFixed(1)}%`;

        // Prepare chart data
        console.log('Preparing chart data...');
        const maintenanceData = data.forecast.map(d => d.needs_maintenance ? 1 : 0);
        const months = data.forecast.map(d => d.date);

        // Create chart
        console.log('Creating chart...');
        const chartOptions = {
            series: [{
                name: 'Trucks Needing Maintenance',
                data: maintenanceData
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            colors: ['#ff6b6b'],
            xaxis: {
                categories: months,
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Number of Trucks'
                },
                min: 0,
                forceNiceScale: true,
                labels: {
                    formatter: function(val) {
                        return Math.floor(val);
                    }
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    dataLabels: {
                        position: 'top'
                    },
                    columnWidth: '60%'
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val;
                },
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    colors: ["#304758"]
                }
            }
        };

        // Render chart
        console.log('Rendering chart...');
        const chart = new ApexCharts(document.querySelector("#maintenancePredictionChart"), chartOptions);
        await chart.render();
        console.log('Chart rendered successfully');

        // Update table
        console.log('Updating table...');
        const rows = await Promise.all(data.forecast.map(async d => {
            const brand = await getTruckBrand(d.truck_id);
            return `
                <tr>
                    <td>${d.date}</td>
                    <td>${d.truck_id}</td>
                    <td>${brand}</td>
                    <td>
                        <span class="badge ${d.needs_maintenance ? 'bg-danger' : 'bg-success'}">
                            ${d.needs_maintenance ? 'Yes' : 'No'}
                        </span>
                    </td>
                    <td>${(d.probability * 100).toFixed(1)}%</td>
                </tr>
            `;
        }));

        allTableData = rows;
        updateTable();

    } catch (error) {
        console.error('Error:', error);
        const errorMessage = document.createElement('div');
        errorMessage.className = 'alert alert-danger';
        errorMessage.textContent = 'Unable to load maintenance predictions: ' + error.message;
        document.querySelector('.container-fluid').prepend(errorMessage);
    }
}

// Helper function to get truck brand
async function getTruckBrand(truckId) {
    try {
        const response = await fetch(`http://127.0.0.1:5000/get_truck_brand/${truckId}`);
        if (!response.ok) {
            throw new Error('Failed to fetch truck brand');
        }
        const data = await response.json();
        return data.brand;
    } catch (error) {
        console.error(`Error fetching truck brand for ID ${truckId}:`, error);
        return 'Unknown';
    }
}

// Function to update table pagination
function updateTable() {
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    const paginatedData = allTableData.slice(startIndex, endIndex);
    
    const tableBody = document.getElementById('maintenancePredictionsTable');
    tableBody.innerHTML = paginatedData.join('');
    
    // Update pagination info
    document.getElementById('startRow').textContent = startIndex + 1;
    document.getElementById('endRow').textContent = Math.min(endIndex, allTableData.length);
    document.getElementById('totalRows').textContent = allTableData.length;
    
    // Update button states
    document.getElementById('prevPage').disabled = currentPage === 1;
    document.getElementById('nextPage').disabled = endIndex >= allTableData.length;
}

// Event listeners for pagination
document.getElementById('prevPage').addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        updateTable();
    }
});

document.getElementById('nextPage').addEventListener('click', () => {
    const maxPage = Math.ceil(allTableData.length / rowsPerPage);
    if (currentPage < maxPage) {
        currentPage++;
        updateTable();
    }
});

document.getElementById('rowsPerPage').addEventListener('change', (e) => {
    rowsPerPage = parseInt(e.target.value);
    currentPage = 1;
    updateTable();
});

// Call the initialization function when the document is ready
document.addEventListener('DOMContentLoaded', initializeDashboard);
</script>
</body>

</html>