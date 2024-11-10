<?php
include '../includes/db_connection.php';

$year = $_GET['year'];

// Query to fetch monthly fuel expenses and revenue for the selected year
$query = "
  SELECT MONTH(f.Date) AS Month,
         SUM(f.Amount) AS TotalFuelExpense, 
         IFNULL(SUM(i.TotalAmount), 0) AS TotalRevenue
  FROM fuel f
  LEFT JOIN invoices i ON f.Date = i.BillingStartDate
  WHERE YEAR(f.Date) = $year
  GROUP BY Month
  ORDER BY Month";

$result = $conn->query($query);
if (!$result) {
    die("Query Error: " . $conn->error);
}

// Initialize arrays for data
$months = array_fill(0, 12, null);
$fuelExpenses = array_fill(0, 12, 0);
$revenues = array_fill(0, 12, 0);

while ($row = $result->fetch_assoc()) {
    $monthIndex = (int)$row['Month'] - 1; // Array index starts at 0 for Jan
    $fuelExpenses[$monthIndex] = (float)$row['TotalFuelExpense'];
    $revenues[$monthIndex] = (float)$row['TotalRevenue'];
}

// Return JSON data for the chart
header('Content-Type: application/json');
echo json_encode([
    'months' => ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
    'fuelExpenses' => $fuelExpenses,
    'revenues' => $revenues
]);

$conn->close();
?>
