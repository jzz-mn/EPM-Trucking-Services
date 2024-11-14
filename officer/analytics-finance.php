<?php
// Include the database connection file if needed
include '../includes/db_connection.php'; // Ensure this path is correct based on your directory structure
include '../officer/header.php';
// Start output buffering
ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Analytics</title>
    
    <!-- CSS Styles -->
    <style>
        @charset "UTF-8"; 
        @import url(https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap);

        canvas {
            aspect-ratio: 400 / 200; /* Maintain a 2:1 aspect ratio */
            width: 100%; /* Ensure it fills its container */
            height: auto; /* Adjust height automatically based on width */
        }

        body {
            font-family: 'Manrope', sans-serif;
            margin: 20px;
        }

        .card {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .card-subtitle {
            font-size: 16px;
            color: #666;
        }
    </style>
    
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

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
    document.addEventListener('DOMContentLoaded', function() {
        fetchRevenueForecast();
    });

    function fetchRevenueForecast() {
        fetch('http://127.0.0.1:5000/predict_finance', {
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


</body>
</html>

<?php
// End output buffering and flush output
ob_end_flush();
?>