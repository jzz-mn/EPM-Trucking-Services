<?php
include '../includes/db_connection.php';

$year = $_GET['year'];

// Query for Top 10 Expenses by Month, including Fuel Expenses, filtered by year
$query = "
  SELECT 'Salary' AS ExpenseCategory, MONTH(Date) AS Month, SUM(SalaryAmount) AS TotalExpense
  FROM expenses
  WHERE YEAR(Date) = $year
  GROUP BY ExpenseCategory, Month
  UNION ALL
  SELECT 'Mobile' AS ExpenseCategory, MONTH(Date) AS Month, SUM(MobileAmount) AS TotalExpense
  FROM expenses
  WHERE YEAR(Date) = $year
  GROUP BY ExpenseCategory, Month
  UNION ALL
  SELECT 'Other' AS ExpenseCategory, MONTH(Date) AS Month, SUM(OtherAmount) AS TotalExpense
  FROM expenses
  WHERE YEAR(Date) = $year
  GROUP BY ExpenseCategory, Month
  UNION ALL
  SELECT 'Fuel' AS ExpenseCategory, MONTH(Date) AS Month, SUM(Amount) AS TotalExpense
  FROM fuel
  WHERE YEAR(Date) = $year
  GROUP BY ExpenseCategory, Month
  ORDER BY TotalExpense DESC
  LIMIT 10";

$result = $conn->query($query);

$months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
$expenseData = [];
$categories = [];

while ($row = $result->fetch_assoc()) {
  $category = $row['ExpenseCategory'];
  $monthIndex = (int)$row['Month'] - 1;
  
  if (!isset($expenseData[$category])) {
    $expenseData[$category] = array_fill(0, 12, 0); // Initialize all months to 0
  }
  
  $expenseData[$category][$monthIndex] = $row['TotalExpense'];
}

// Prepare data for Chart.js
$response = [
  'labels' => $months,
  'datasets' => []
];

foreach ($expenseData as $category => $data) {
  $response['datasets'][] = [
    'label' => $category,
    'data' => $data
  ];
}

// Return JSON data for the chart
echo json_encode($response);
?>
