<?php
include '../includes/db_connection.php';

$months = $_GET['months'] ?? 3; // Number of months to predict, defaults to 3

// Fetch historical revenue data
$historicalQuery = "
  SELECT DATE_FORMAT(Date, '%Y-%m') AS month, SUM(RateAmount + TollFeeAmount) AS revenue
  FROM transactiongroup
  GROUP BY month
  ORDER BY month DESC
  LIMIT 12";
$historicalResult = $conn->query($historicalQuery);
$historicalData = [];

while ($row = $historicalResult->fetch_assoc()) {
  $historicalData[] = [
    'month' => $row['month'],
    'revenue' => (float) $row['revenue']
  ];
}

// Fetch forecasted revenue data
$forecastData = [];
$latestDate = end($historicalData)['month'];
$apiUrl = "http://localhost:7860/predict";

for ($i = 1; $i <= $months; $i++) {
  $date = date('Y-m', strtotime("+$i month", strtotime($latestDate)));
  $data = [
    "Date" => $date . "-01",
    "RateAmount" => 5000, // Replace with dynamic data if available
    "TollFeeAmount" => 300 // Replace with dynamic data if available
  ];
  
  $options = [
    'http' => [
      'header' => "Content-Type: application/json\r\n",
      'method' => 'POST',
      'content' => json_encode($data),
    ],
  ];
  
  $context = stream_context_create($options);
  $response = file_get_contents($apiUrl, false, $context);
  
  if ($response) {
    $prediction = json_decode($response, true);
    if (isset($prediction['predicted_revenue'])) {
      $forecastData[] = [
        'month' => $date,
        'revenue' => (float) $prediction['predicted_revenue']
      ];
    }
  }
}

// Return both historical and forecast data
header('Content-Type: application/json');
echo json_encode([
  'historical' => array_reverse($historicalData), // To ensure chronological order
  'forecast' => $forecastData
]);

$conn->close();
?>
