<?php
include '../includes/db_connection.php';

$year = (int)$_GET['year']; // Cast to int for security

// Query to fetch revenue, expenses, and profit per month for the selected year
$query = "
  SELECT m.Month,
         COALESCE(i.TotalRevenue, 0) AS TotalRevenue,
         COALESCE(e.TotalExpenses, 0) AS TotalExpenses,
         COALESCE(i.TotalRevenue, 0) - COALESCE(e.TotalExpenses, 0) AS Profit
  FROM (SELECT 1 AS Month UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
        UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
        UNION ALL SELECT 11 UNION ALL SELECT 12) AS m
  LEFT JOIN (
      SELECT MONTH(BillingStartDate) AS Month, SUM(TotalAmount) AS TotalRevenue
      FROM invoices
      WHERE YEAR(BillingStartDate) = $year
      GROUP BY MONTH(BillingStartDate)
  ) AS i ON m.Month = i.Month
  LEFT JOIN (
      SELECT MONTH(Date) AS Month, SUM(TotalExpense) AS TotalExpenses
      FROM expenses
      WHERE YEAR(Date) = $year
      GROUP BY MONTH(Date)
  ) AS e ON m.Month = e.Month
  ORDER BY m.Month";

$result = $conn->query($query);
$months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
$revenues = array_fill(0, 12, 0);
$expenses = array_fill(0, 12, 0);
$profits = array_fill(0, 12, 0);

while ($row = $result->fetch_assoc()) {
  $monthIndex = (int)$row['Month'] - 1;
  $revenues[$monthIndex] = $row['TotalRevenue'];
  $expenses[$monthIndex] = $row['TotalExpenses'];
  $profits[$monthIndex] = $row['Profit'];
}

// Return JSON data for the chart
echo json_encode([
  'labels' => $months,
  'revenues' => $revenues,
  'expenses' => $expenses,
  'profits' => $profits
]);
?>
