<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Maintenance Analytics</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
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
                <h4 class="mb-4 card-title">Maintenance Analytics</h4>
            </div>

            <h5 class="border-bottom py-2 px-4 mb-4">Predicted Maintenance Needs for Upcoming Months</h5>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card bg-warning-subtle overflow-hidden shadow-none">
                        <div class="card-body">
                            <h5 class="card-title">Predicted Maintenance Needs per Truck</h5>
                            <table class="table table-bordered table-hover">
                                <thead class="table-warning">
                                    <tr>
                                        <th>Truck ID</th>
                                        <th>Month</th>
                                        <th>Year</th>
                                        <th>Predicted Maintenance Needed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                                    $predictedData = [];

                                    $truckQuery = "SELECT TruckID, MAX(Year) AS LastYear, MAX(Month) AS LastMonth FROM truckmaintenance GROUP BY TruckID";
                                    $truckResult = mysqli_query($conn, $truckQuery);

                                    while ($truck = mysqli_fetch_assoc($truckResult)) {
                                        $truckID = $truck['TruckID'];
                                        $lastYear = (int)$truck['LastYear'];
                                        $lastMonth = (int)$truck['LastMonth'];
                                        $predictedData[$truckID] = [];

                                        $currentYear = $lastYear;
                                        $currentMonth = $lastMonth + 1;
                                        if ($currentMonth > 12) {
                                            $currentMonth = 1;
                                            $currentYear += 1;
                                        }

                                        for ($i = 0; $i < 12; $i++) {
                                            $apiData = json_encode([
                                                'truck_id' => $truckID,
                                                'year' => $currentYear,
                                                'month' => $currentMonth,
                                                'description_encoded' => 5
                                            ]);

                                            $options = [
                                                'http' => [
                                                    'header' => "Content-type: application/json\r\n",
                                                    'method' => 'POST',
                                                    'content' => $apiData
                                                ]
                                            ];
                                            $context = stream_context_create($options);
                                            $response = file_get_contents('http://127.0.0.1:5000/predict_maintenance', false, $context);

                                            if ($response === FALSE) {
                                                echo "<tr><td colspan='4'>Error fetching prediction for Truck ID $truckID in {$months[$currentMonth - 1]} $currentYear</td></tr>";
                                                continue;
                                            }

                                            $result = json_decode($response, true);
                                            $maintenanceNeeded = $result['maintenance_needed'] ? "Yes" : "No";
                                            echo "<tr>
                                                <td>{$truckID}</td>
                                                <td>{$months[$currentMonth - 1]}</td>
                                                <td>{$currentYear}</td>
                                                <td>{$maintenanceNeeded}</td>
                                              </tr>";

                                            $predictedData[$truckID][] = $result['maintenance_needed'] ? 1 : 0;

                                            $currentMonth += 1;
                                            if ($currentMonth > 12) {
                                                $currentMonth = 1;
                                                $currentYear += 1;
                                            }
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card bg-light shadow-none">
                        <div class="card-body">
                            <h5 class="card-title">Select Truck for Maintenance Prediction</h5>
                            <select id="truckSelect" class="form-select mb-3" aria-label="Select Truck">
                                <option value="all">All Trucks</option>
                                <?php foreach (array_keys($predictedData) as $truckID): ?>
                                    <option value="<?php echo $truckID; ?>">Truck <?php echo $truckID; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <canvas id="maintenancePredictionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const labels = <?php echo json_encode($months); ?>;
            const datasets = [];
            const allDatasets = {};

            <?php foreach ($predictedData as $truckID => $data): ?>
                datasets.push({
                    label: 'Truck <?php echo $truckID; ?>',
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: getRandomColor(),
                    borderColor: getRandomColor(),
                    borderWidth: 1
                });
                allDatasets['<?php echo $truckID; ?>'] = datasets[datasets.length - 1];
            <?php endforeach; ?>

            function getRandomColor() {
                const hue = Math.floor(Math.random() * 360);
                return `hsl(${hue}, 70%, 50%)`;
            }

            const ctx = document.getElementById('maintenancePredictionChart').getContext('2d');
            let myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'Maintenance Prediction Trends (Monthly)'
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Maintenance Needed (1 = Yes, 0 = No)'
                            },
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    return value === 1 ? 'Yes' : 'No';
                                }
                            }
                        }
                    }
                }
            });

            document.getElementById('truckSelect').addEventListener('change', function() {
                const selectedTruck = this.value;
                myChart.data.datasets = selectedTruck === 'all' ? Object.values(allDatasets) : [allDatasets[selectedTruck]];
                myChart.update();
            });
        });
    </script>
</body>

</html>
