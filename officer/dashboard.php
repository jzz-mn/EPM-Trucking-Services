<?php
// Date range variables
$startDate = null;
$endDate = null;

// Determine the date range based on the selected filter
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    switch ($filter) {
        case 'year':
            // Start of the current year
            $startDate = date('Y-01-01');
            // End of the current year
            $endDate = date('Y-12-31');
            break;
        case 'month':
            // Start of the current month
            $startDate = date('Y-m-01');
            // End of the current month
            $endDate = date('Y-m-t');
            break;
        case 'week':
            // Start of the current week (Monday)
            $startDate = date('Y-m-d', strtotime('monday this week'));
            // End of the current week (Sunday)
            $endDate = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'custom':
            // Get start and end dates from user input
            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                $startDate = $_GET['start_date'];
                $endDate = $_GET['end_date'];
            }
            break;
        default:
            // No filter applied
            break;
    }
}

// Prepare WHERE clauses for SQL queries
$expensesWhere = '';
$fuelWhere = '';
$invoicesWhere = '';
$transactionsWhere = '';

if ($startDate && $endDate) {
    // Escape the dates
    $startDateEscaped = mysqli_real_escape_string($conn, $startDate);
    $endDateEscaped = mysqli_real_escape_string($conn, $endDate);

    // Build WHERE clauses
    $expensesWhere = "WHERE Date BETWEEN '$startDateEscaped' AND '$endDateEscaped'";
    $fuelWhere = "WHERE Date BETWEEN '$startDateEscaped' AND '$endDateEscaped'";
    $invoicesWhere = "WHERE BillingStartDate >= '$startDateEscaped' AND BillingEndDate <= '$endDateEscaped'";
    $transactionsWhere = "WHERE TransactionDate BETWEEN '$startDateEscaped' AND '$endDateEscaped'";
}

// Total Expenses - Calculate separately and sum in PHP
// Get total expenses from expenses table
$queryTotalExpense = "SELECT IFNULL(SUM(TotalExpense), 0) AS TotalExpense FROM expenses $expensesWhere";
$resultTotalExpense = mysqli_query($conn, $queryTotalExpense);
if (!$resultTotalExpense) {
    die("Query Failed (TotalExpense): " . mysqli_error($conn));
}
$rowTotalExpense = mysqli_fetch_assoc($resultTotalExpense);
$totalExpense = $rowTotalExpense['TotalExpense'];

// Get total amount from fuel table
$queryFuelAmount = "SELECT IFNULL(SUM(Amount), 0) AS FuelAmount FROM fuel $fuelWhere";
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
$queryRevenue = "SELECT IFNULL(SUM(NetAmount), 0) AS TotalRevenue FROM invoices $invoicesWhere";
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
$queryTransactions = "SELECT COUNT(*) AS TotalTransactions FROM transactions $transactionsWhere";
$resultTransactions = mysqli_query($conn, $queryTransactions);
if (!$resultTransactions) {
    die("Query Failed (Transactions): " . mysqli_error($conn));
}
$rowTransactions = mysqli_fetch_assoc($resultTransactions);
$totalTransactions = $rowTransactions['TotalTransactions'];
$formattedTransactions = number_format($totalTransactions);

// Total Fuel Consumption
$queryFuelConsumption = "SELECT IFNULL(SUM(Liters), 0) AS TotalFuelConsumption FROM fuel $fuelWhere";
$resultFuelConsumption = mysqli_query($conn, $queryFuelConsumption);
if (!$resultFuelConsumption) {
    die("Query Failed (FuelConsumption): " . mysqli_error($conn));
}
$rowFuelConsumption = mysqli_fetch_assoc($resultFuelConsumption);
$totalFuelConsumption = $rowFuelConsumption['TotalFuelConsumption'];
$formattedFuelConsumption = number_format($totalFuelConsumption, 2);
?>