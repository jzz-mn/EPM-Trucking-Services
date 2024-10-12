<?php
// Total Expenses - Calculate separately and sum in PHP
// Get total expenses from expenses table
$queryTotalExpense = "SELECT IFNULL(SUM(TotalExpense), 0) AS TotalExpense FROM expenses";
$resultTotalExpense = mysqli_query($conn, $queryTotalExpense);
if (!$resultTotalExpense) {
    die("Query Failed (TotalExpense): " . mysqli_error($conn));
}
$rowTotalExpense = mysqli_fetch_assoc($resultTotalExpense);
$totalExpense = $rowTotalExpense['TotalExpense'];

// Get total amount from fuel table
$queryFuelAmount = "SELECT IFNULL(SUM(Amount), 0) AS FuelAmount FROM fuel";
$resultFuelAmount = mysqli_query($conn, $queryFuelAmount);
if (!$resultFuelAmount) {
    die("Query Failed (FuelAmount): " . mysqli_error($conn));
}
$rowFuelAmount = mysqli_fetch_assoc($resultFuelAmount);
$fuelAmount = $rowFuelAmount['FuelAmount'];

// Calculate total expenses
$totalExpenses = $totalExpense + $fuelAmount;

// Format the value for display
$formattedExpenses = number_format($totalExpenses, 2);

// Total Revenue
$queryRevenue = "SELECT IFNULL(SUM(NetAmount), 0) AS TotalRevenue FROM invoices";
$resultRevenue = mysqli_query($conn, $queryRevenue);
if (!$resultRevenue) {
    die("Query Failed (Revenue): " . mysqli_error($conn));
}
$rowRevenue = mysqli_fetch_assoc($resultRevenue);
$totalRevenue = $rowRevenue['TotalRevenue'];
$formattedRevenue = number_format($totalRevenue, 2);

// Total Profit
$totalProfit = $totalRevenue - $totalExpenses;
$formattedProfit = number_format($totalProfit, 2);

// Total Transactions
$queryTransactions = "SELECT COUNT(*) AS TotalTransactions FROM transactions";
$resultTransactions = mysqli_query($conn, $queryTransactions);
if (!$resultTransactions) {
    die("Query Failed (Transactions): " . mysqli_error($conn));
}
$rowTransactions = mysqli_fetch_assoc($resultTransactions);
$totalTransactions = $rowTransactions['TotalTransactions'];
$formattedTransactions = number_format($totalTransactions);

// Total Fuel Consumption
$queryFuelConsumption = "SELECT IFNULL(SUM(Liters), 0) AS TotalFuelConsumption FROM fuel";
$resultFuelConsumption = mysqli_query($conn, $queryFuelConsumption);
if (!$resultFuelConsumption) {
    die("Query Failed (FuelConsumption): " . mysqli_error($conn));
}
$rowFuelConsumption = mysqli_fetch_assoc($resultFuelConsumption);
$totalFuelConsumption = $rowFuelConsumption['TotalFuelConsumption'];
$formattedFuelConsumption = number_format($totalFuelConsumption, 2);
?>