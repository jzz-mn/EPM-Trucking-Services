<?php
require_once '../includes/db_connection.php';
require_once '../vendor/autoload.php'; // Adjust the path if necessary

use Dompdf\Dompdf;
use Dompdf\Options;

// Get the BillingInvoiceNo from POST
if (!isset($_POST['BillingInvoiceNo'])) {
    die('No invoice number provided.');
}

$billingInvoiceNo = intval($_POST['BillingInvoiceNo']);

// Fetch invoice details
$invoiceQuery = "SELECT * FROM invoices WHERE BillingInvoiceNo = ?";
$stmt = $conn->prepare($invoiceQuery);
$stmt->bind_param('i', $billingInvoiceNo);
$stmt->execute();
$invoiceResult = $stmt->get_result();
if ($invoiceResult->num_rows === 0) {
    die('Invoice not found.');
}
$invoice = $invoiceResult->fetch_assoc();
$stmt->close();

// Fetch transaction groups associated with this invoice
$tgQuery = "SELECT * FROM transactiongroup WHERE BillingInvoiceNo = ?";
$stmt = $conn->prepare($tgQuery);
$stmt->bind_param('i', $billingInvoiceNo);
$stmt->execute();
$tgResult = $stmt->get_result();
$transactionGroups = [];
while ($row = $tgResult->fetch_assoc()) {
    $transactionGroups[] = $row;
}
$stmt->close();

// Get TransactionGroupIDs
$transactionGroupIDs = array_column($transactionGroups, 'TransactionGroupID');

// Fetch transactions associated with these TransactionGroupIDs
$transactionsByGroup = [];
if (!empty($transactionGroupIDs)) {
    $ids = implode(',', array_map('intval', $transactionGroupIDs));
    $txnQuery = "SELECT * FROM transactions WHERE TransactionGroupID IN ($ids)";
    $txnResult = $conn->query($txnQuery);
    while ($txn = $txnResult->fetch_assoc()) {
        $tgid = $txn['TransactionGroupID'];
        if (!isset($transactionsByGroup[$tgid])) {
            $transactionsByGroup[$tgid] = [];
        }
        $transactionsByGroup[$tgid][] = $txn;
    }
}

// Generate the HTML content for the PDF
ob_start();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo htmlspecialchars($billingInvoiceNo); ?></title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        .invoice-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .invoice-details,
        .transaction-group,
        .transactions-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .transactions-table {
            border-collapse: collapse;
            width: 100%;
        }

        .transactions-table th,
        .transactions-table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .transactions-table th {
            background-color: #f0f0f0;
        }

        .totals {
            text-align: right;
            margin-top: 20px;
        }

        .totals p {
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="invoice-header">
        <h1>Invoice #<?php echo htmlspecialchars($billingInvoiceNo); ?></h1>
        <p>Date Range: <?php echo htmlspecialchars($invoice['BillingStartDate']); ?> -
            <?php echo htmlspecialchars($invoice['BillingEndDate']); ?>
        </p>
        <p>Billed To: <?php echo htmlspecialchars($invoice['BilledTo']); ?></p>
    </div>

    <div class="invoice-details">
        <p><strong>Gross Amount:</strong> <?php echo number_format($invoice['GrossAmount'], 2); ?></p>
        <p><strong>VAT:</strong> <?php echo number_format($invoice['VAT'], 2); ?></p>
        <p><strong>Total Amount:</strong> <?php echo number_format($invoice['TotalAmount'], 2); ?></p>
        <p><strong>EWT:</strong> <?php echo number_format($invoice['EWT'], 2); ?></p>
        <p><strong>Add Toll Charges:</strong> <?php echo number_format($invoice['AddTollCharges'], 2); ?></p>
        <p><strong>Amount Net Of Tax:</strong> <?php echo number_format($invoice['AmountNetOfTax'], 2); ?></p>
        <p><strong>Net Amount:</strong> <?php echo number_format($invoice['NetAmount'], 2); ?></p>
    </div>

    <?php foreach ($transactionGroups as $tg): ?>
        <div class="transaction-group">
            <h2>Transaction Group #<?php echo htmlspecialchars($tg['TransactionGroupID']); ?></h2>
            <p><strong>Date:</strong> <?php echo htmlspecialchars($tg['Date']); ?></p>
            <p><strong>Rate Amount:</strong> <?php echo number_format($tg['RateAmount'], 2); ?></p>
            <p><strong>Total KGs:</strong> <?php echo number_format($tg['TotalKGs'], 2); ?></p>
            <!-- Add other details if needed -->
        </div>

        <?php if (isset($transactionsByGroup[$tg['TransactionGroupID']])): ?>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Transaction Date</th>
                        <th>DR No</th>
                        <th>Outlet Name</th>
                        <th>Qty</th>
                        <th>KGs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactionsByGroup[$tg['TransactionGroupID']] as $txn): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($txn['TransactionID']); ?></td>
                            <td><?php echo htmlspecialchars($txn['TransactionDate']); ?></td>
                            <td><?php echo htmlspecialchars($txn['DRno']); ?></td>
                            <td><?php echo htmlspecialchars($txn['OutletName']); ?></td>
                            <td><?php echo number_format($txn['Qty'], 2); ?></td>
                            <td><?php echo number_format($txn['KGs'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php endforeach; ?>

    <!-- Totals at the bottom -->
    <div class="totals">
        <h3>Totals</h3>
        <p><strong>Gross Amount:</strong> <?php echo number_format($invoice['GrossAmount'], 2); ?></p>
        <p><strong>Net Amount:</strong> <?php echo number_format($invoice['NetAmount'], 2); ?></p>
    </div>
</body>

</html>
<?php
$html = ob_get_clean();

// Set up Dompdf options
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', true);

// Instantiate Dompdf
$dompdf = new Dompdf($options);

// Load HTML content
$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream('invoice_' . $billingInvoiceNo . '.pdf', array('Attachment' => false));

// Close the database connection
$conn->close();
?>