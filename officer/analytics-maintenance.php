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
            <!-- Most Common Maintenance Issues by Description where Amount > 0 -->
            <div class="col-lg-6">
                <div class="card bg-secondary-subtle overflow-hidden shadow-none">
                    <div class="card-body">
                        <h5 class="card-title">Most Common Maintenance Issues</h5>
                        <?php
                        $query = "SELECT Description, COUNT(*) AS Frequency 
                                  FROM truckmaintenance 
                                  WHERE Amount > 0.00 
                                  GROUP BY Description 
                                  ORDER BY Frequency DESC 
                                  LIMIT 10;";
                        $result = mysqli_query($conn, $query);

                        if (!$result) {
                            echo "Error: " . mysqli_error($conn);
                        }

                        $descriptions = [];
                        $frequencies = [];
                        while ($row = mysqli_fetch_assoc($result)) {
                            $descriptions[] = $row['Description'];
                            $frequencies[] = $row['Frequency'];
                        }
                        ?>
                        <canvas id="commonIssuesChart"></canvas>
                        <script>
                            var ctx = document.getElementById('commonIssuesChart').getContext('2d');
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: <?php echo json_encode($descriptions); ?>,
                                    datasets: [{
                                        label: 'Frequency',
                                        data: <?php echo json_encode($frequencies); ?>,
                                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: {
                                            position: 'top'
                                        },
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true
                                        },
                                        x: {
                                            title: {
                                                display: true,
                                                text: 'Description'
                                            }
                                        }
                                    }
                                }
                            });
                        </script>
                    </div>
                </div>
            </div>

            <!-- Predicted Maintenance Needs Chart -->
            <div class="col-lg-6">
                <div class="card bg-warning-subtle overflow-hidden shadow-none">
                    <div class="card-body">
                        <h5 class="card-title">Predicted Maintenance Needs</h5>
                        <canvas id="maintenancePredictionChart"></canvas>
                        <script>
                            async function fetchMaintenancePredictionsFromCSV() {
                                try {
                                    // Path to the CSV file
                                    const filePath = 'maintenance_forecast.csv'; // This path will be accessible server-side.

                                    const response = await fetch(filePath);
                                    if (!response.ok) {
                                        throw new Error(`Failed to load CSV: ${response.status}`);
                                    }

                                    const csvText = await response.text();
                                    const predictions = parseCSV(csvText);

                                    return predictions;
                                } catch (error) {
                                    console.error("Error loading CSV:", error);
                                    return [];
                                }
                            }

                            function parseCSV(csvText) {
                                const rows = csvText.split('\n');
                                const headers = rows[0].split(','); // Get CSV headers
                                const data = [];

                                for (let i = 1; i < rows.length; i++) {
                                    const columns = rows[i].split(',');
                                    if (columns.length < headers.length) continue;

                                    // Create an object for each row
                                    const prediction = {
                                        Month: columns[1].trim(), // YYYY-MM format
                                        TruckID: parseInt(columns[0].trim()), // TruckID
                                        MaintenanceForecast: columns[3].trim() // 'Yes' or 'No'
                                    };
                                    data.push(prediction);
                                }

                                return data;
                            }

                            async function renderMaintenancePredictionChart() {
                                const predictions = await fetchMaintenancePredictionsFromCSV();

                                if (predictions.length === 0) {
                                    document.getElementById("maintenancePredictionChart").parentNode.innerHTML += `
                            <p class="text-center mt-3">No prediction data available.</p>`;
                                    return;
                                }

                                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                const maintenanceData = Array(12).fill(0);
                                const trucksPerMonth = Array(12).fill("").map(() => []);

                                predictions.forEach(prediction => {
                                    const monthIndex = new Date(prediction.Month).getMonth(); // Get the month index from the date
                                    if (prediction.MaintenanceForecast === "Yes") {
                                        maintenanceData[monthIndex] += 1;
                                        trucksPerMonth[monthIndex].push(`TruckID: ${prediction.TruckID}`);
                                    }
                                });

                                if (maintenanceData.every(value => value === 0)) {
                                    const ctxParent = document.getElementById('maintenancePredictionChart').parentNode;
                                    ctxParent.innerHTML += `
                            <p class="text-center mt-3">
                                No trucks require maintenance for the selected year. 
                                This could indicate:
                                <ul>
                                    <li>Trucks are well-maintained.</li>
                                    <li>The model predicts no upcoming maintenance issues based on current data.</li>
                                </ul>
                            </p>`;
                                    return;
                                }

                                const ctx = document.getElementById('maintenancePredictionChart').getContext('2d');
                                new Chart(ctx, {
                                    type: 'bar',
                                    data: {
                                        labels: months,
                                        datasets: [{
                                            label: 'Trucks Requiring Maintenance',
                                            data: maintenanceData,
                                            backgroundColor: 'rgba(255, 99, 132, 0.6)',
                                            borderColor: 'rgba(255, 99, 132, 1)',
                                            borderWidth: 1
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        plugins: {
                                            tooltip: {
                                                callbacks: {
                                                    afterBody: (tooltipItems) => {
                                                        const monthIndex = tooltipItems[0].dataIndex;
                                                        return trucksPerMonth[monthIndex].join(", ");
                                                    }
                                                }
                                            }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                title: {
                                                    display: true,
                                                    text: 'Number of Trucks Requiring Maintenance'
                                                }
                                            },
                                            x: {
                                                title: {
                                                    display: true,
                                                    text: 'Month'
                                                }
                                            }
                                        }
                                    }
                                });
                            }

                            // Initialize the chart rendering
                            renderMaintenancePredictionChart();
                        </script>
                    </div>
                </div>
            </div>



            <div class="row mt-4">
                <!-- Top Trucks with Highest Maintenance Costs -->
                <div class="col-lg-6">
                    <div class="card bg-primary-subtle overflow-hidden shadow-none">
                        <div class="card-body">
                            <h5 class="card-title">Top Trucks with Highest Maintenance Costs</h5>
                            <?php
                            $query = "SELECT t.TruckBrand, t.TruckID, SUM(m.Amount) AS TotalMaintenanceCost 
                                  FROM truckmaintenance m 
                                  JOIN trucksinfo t ON m.TruckID = t.TruckID 
                                  GROUP BY t.TruckBrand, t.TruckID
                                  ORDER BY TotalMaintenanceCost DESC 
                                  LIMIT 5;";
                            $result = mysqli_query($conn, $query);

                            if (!$result) {
                                echo "Error: " . mysqli_error($conn);
                            }

                            $topTrucks = [];
                            while ($row = mysqli_fetch_assoc($result)) {
                                $topTrucks[] = $row;
                            }
                            ?>
                            <table class="table table-bordered table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Truck Brand</th>
                                        <th>Truck ID</th>
                                        <th>Total Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topTrucks as $truck): ?>
                                        <tr>
                                            <td><?php echo $truck['TruckBrand']; ?></td>
                                            <td><?php echo $truck['TruckID']; ?></td>
                                            <td><?php echo number_format($truck['TotalMaintenanceCost'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Truck Maintenance Cost Summary -->
                <div class="col-lg-6">
                    <div class="card bg-info-subtle overflow-hidden shadow-none">
                        <div class="card-body">
                            <h5 class="card-title">Truck Maintenance Cost Summary</h5>
                            <?php
                            $summaryQuery = "SELECT 
                                         COUNT(DISTINCT TruckID) AS TotalTrucks,
                                         SUM(Amount) AS TotalCost,
                                         AVG(Amount) AS AvgCost
                                         FROM truckmaintenance 
                                         WHERE Amount > 0.00";
                            $summaryResult = mysqli_query($conn, $summaryQuery);

                            if (!$summaryResult) {
                                echo "Error: " . mysqli_error($conn);
                            }

                            $summaryData = mysqli_fetch_assoc($summaryResult);
                            ?>
                            <p><strong>Total Trucks with Maintenance:</strong>
                                <?php echo $summaryData['TotalTrucks']; ?></p>
                            <p><strong>Total Maintenance Cost:</strong>
                                <?php echo number_format($summaryData['TotalCost'], 2); ?></p>
                            <p><strong>Average Maintenance Cost per Truck:</strong>
                                <?php echo number_format($summaryData['AvgCost'], 2); ?></p>
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
    <script src="../assets/libs/owl.carousel/dist/owl.carousel.min.js"></script>
    <script src="../assets/js/apps/productDetail.js"></script>
    </body>

    </html>