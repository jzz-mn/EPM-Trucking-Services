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

// Read and encode the logo image
$logoPath = 'assets/images/logos/epm-logo-no-bg.png'; // Adjust the path as needed
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $base64Logo = base64_encode($logoData);
} else {
    $base64Logo = ''; // Handle missing logo
}

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
$tgQuery = "
    SELECT tg.*, ti.PlateNo, f.FuelType, f.UnitPrice, e.TotalExpense
    FROM transactiongroup tg
    JOIN trucksinfo ti ON tg.TruckID = ti.TruckID
    LEFT JOIN fuel f ON tg.FuelID = f.FuelID
    LEFT JOIN expenses e ON tg.ExpenseID = e.ExpenseID
    WHERE tg.BillingInvoiceNo = ?
";
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

// Fetch transactions associated with these TransactionGroupIDs, sorted by DRno
$transactionsByGroup = [];
if (!empty($transactionGroupIDs)) {
    $ids = implode(',', array_map('intval', $transactionGroupIDs));
    $txnQuery = "SELECT * FROM transactions WHERE TransactionGroupID IN ($ids) ORDER BY DRno";
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
            font-size: 10px; /* Smaller font size */
            text-transform: uppercase; /* Uppercase text */
        }
        .invoice-header {
            margin-bottom: 20px;
        }
        .logo {
            float: left;
        }
        .company-info {
            text-align: center;
        }
        .invoice-details {
            text-align: left;
            margin-top: 20px;
        }
        .transactions-table {
            border-collapse: collapse;
            width: 100%;
        }
        .transactions-table th, .transactions-table td {
            border: 1px solid #000;
            padding: 5px;
        }
        .transactions-table th {
            background-color: #f0f0f0;
        }
        .transactions-table td.numeric, .transactions-table th.numeric {
            text-align: right;
        }
        .transaction-group {
            page-break-inside: avoid;
        }
        .totals-row {
            page-break-inside: avoid;
        }
        .totals {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .totals-table {
            width: 50%;
            margin-left: auto; /* Align the table to the right */
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 3px;
        }
        .totals-table td.numeric {
            text-align: right;
        }
        .totals-table td:first-child {
            text-align: left;
        }
        .signatures {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
        }
        .signature-table td {
            width: 50%;
            vertical-align: top;
        }
        .signature-table p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="invoice-header">
        <div class="logo">
            <?php if ($base64Logo): ?>
                <img src="data:image/png;base64,<?php echo $base64Logo; ?>" alt="Logo" width="100">
            <?php endif; ?>
        </div>
        <div class="company-info">
            <h2>E.P.MONTALBO TRUCKING</h2>
            <p>ELMA/MANNY MONTALBO</p>
            <p>244 COLIAT, IBAAN, BATANGAS</p>
            <p>09190053438 / e.p.montalbo@gmail.com</p>
            <p>TIN# 730-494-707-000</p>
            <h3>STATEMENT OF ACCOUNT</h3>
        </div>
        <div class="invoice-details">
            <p><strong>BILLED TO:</strong> <?php echo htmlspecialchars($invoice['BilledTo']); ?></p>
            <p><strong>BILLING INVOICE #:</strong> SOA# <?php echo htmlspecialchars($invoice['BillingInvoiceNo']); ?></p>
            <p><strong>BILLING DATE:</strong> <?php echo strtoupper(date('M. d', strtotime($invoice['BillingStartDate']))); ?> - <?php echo date('d, Y', strtotime($invoice['BillingEndDate'])); ?></p>
        </div>
    </div>

    <!-- Transactions Table -->
    <?php
    // Initialize overall totals
    $totalRate = 0;
    $totalTollFee = 0;
    $totalAmount = 0;
    ?>
    <table class="transactions-table">
        <thead>
            <tr>
                <th>DATE</th>
                <th>PLATE #</th>
                <th>DR #</th>
                <th>DIESEL</th>
                <th>OUTLET NAME</th>
                <th class="numeric">QTY</th>
                <th class="numeric">KGS</th>
                <th class="numeric">RATE</th>
                <th class="numeric">TOLLFEE/PW</th>
                <th class="numeric">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
    <?php
    foreach ($transactionGroups as $tg):

        // Initialize subtotals
        $subtotalQty = 0;
        $subtotalKGs = 0;

        $fuelUnitPrice = $tg['UnitPrice'] ?? 0; // UnitPrice from fuel table
        $tollFeePW = $tg['TotalExpense'] ?? 0; // TollFee/PW from expenses
        $rate = $tg['RateAmount'] ?? 0; // Rate from transactiongroup
        $amount = $tg['Amount'] ?? 0; // Amount from transactiongroup

        // Start of transaction group
        echo '<!-- Start of Transaction Group -->';

        if (isset($transactionsByGroup[$tg['TransactionGroupID']])):

            foreach ($transactionsByGroup[$tg['TransactionGroupID']] as $txn):
                $subtotalQty += $txn['Qty'];
                $subtotalKGs += $txn['KGs'];
    ?>
                <tr>
                    <td><?php echo date('j-M-Y', strtotime($txn['TransactionDate'])); ?></td>
                    <td><?php echo htmlspecialchars($tg['PlateNo']); ?></td>
                    <td><?php echo htmlspecialchars($txn['DRno']); ?></td>
                    <td class="numeric">-</td>
                    <td><?php echo htmlspecialchars($txn['OutletName']); ?></td>
                    <td class="numeric"><?php echo number_format($txn['Qty'], 2); ?></td>
                    <td class="numeric"><?php echo number_format($txn['KGs'], 2); ?></td>
                    <!-- Do not show values in Rate, TollFee/PW, and Amount columns -->
                    <td class="numeric">-</td>
                    <td class="numeric">-</td>
                    <td class="numeric">-</td>
                </tr>
    <?php
            endforeach;
        endif;

        // Subtotal row for the transaction group
    ?>
        <tr class="transaction-group">
            <td colspan="3"><strong>SUB TOTAL</strong></td>
            <td class="numeric"><?php echo '₱' . number_format($fuelUnitPrice, 2); ?></td>
            <td></td>
            <td class="numeric"><?php echo number_format($subtotalQty, 2); ?></td>
            <td class="numeric"><?php echo number_format($subtotalKGs, 2); ?></td>
            <td class="numeric"><?php echo '₱' . number_format($rate, 2); ?></td>
            <td class="numeric"><?php echo '₱' . number_format($tollFeePW, 2); ?></td>
            <td class="numeric"><?php echo '₱' . number_format($amount, 2); ?></td>
        </tr>
    <?php
        // End of transaction group
        echo '<!-- End of Transaction Group -->';

        // Accumulate totals
        $totalRate += $rate;
        $totalTollFee += $tollFeePW;
        $totalAmount += $amount;

    endforeach;
    ?>
        <!-- Total row at the bottom -->
        <tr class="totals-row">
            <td colspan="7"><strong>TOTAL</strong></td>
            <td class="numeric"><strong><?php echo '₱' . number_format($totalRate, 2); ?></strong></td>
            <td class="numeric"><strong><?php echo '₱' . number_format($totalTollFee, 2); ?></strong></td>
            <td class="numeric"><strong><?php echo '₱' . number_format($totalAmount, 2); ?></strong></td>
        </tr>
        </tbody>
    </table>

    <!-- Totals Section -->
    <?php
    // Use the totals from the 'invoices' table for the final calculations
    $grossAmount = $invoice['GrossAmount'];
    $vat = $invoice['VAT'];
    $totalAmountInvoice = $invoice['TotalAmount'];
    $ewt = $invoice['EWT'];
    $amountNetOfTax = $invoice['AmountNetOfTax'];
    $addTollCharges = $invoice['AddTollCharges'];
    $netAmount = $invoice['NetAmount'];
    ?>

    <div class="totals">
        <table class="totals-table">
            <tr>
                <td><strong>GROSS AMOUNT:</strong></td>
                <td class="numeric"><?php echo '₱' . number_format($grossAmount, 2); ?></td>
            </tr>
            <tr>
                <td><strong>ADD: VAT 12%</strong></td>
                <td class="numeric"><?php echo '₱' . number_format($vat, 2); ?></td>
            </tr>
            <tr>
                <td><strong>TOTAL AMOUNT</strong></td>
                <td class="numeric"><?php echo '₱' . number_format($totalAmountInvoice, 2); ?></td>
            </tr>
            <tr>
                <td><strong>LESS: EWT 2%</strong></td>
                <td class="numeric"><?php echo '₱' . number_format($ewt, 2); ?></td>
            </tr>
            <tr>
                <td><strong>AMOUNT NET OF TAX</strong></td>
                <td class="numeric"><?php echo '₱' . number_format($amountNetOfTax, 2); ?></td>
            </tr>
            <tr>
                <td><strong>ADD: TOLL/CHARGES</strong></td>
                <td class="numeric"><?php echo '₱' . number_format($addTollCharges, 2); ?></td>
            </tr>
            <tr>
                <td><strong>NET AMOUNT:</strong></td>
                <td class="numeric"><?php echo '₱' . number_format($netAmount, 2); ?></td>
            </tr>
        </table>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <table class="signature-table">
            <tr>
                <td>
                    <p>PREPARED BY:</p>
                    <p><strong>MANNY MONTALBO</strong></p>
                    <p>E.P.MONTALBO TRUCKING</p>
                </td>
                <td>
                    <p>RECEIVED BY:</p>
                    <p>__________________________</p>
                    <p>DATE:</p>
                </td>
            </tr>
            <tr>
                <td>
                    <p>CHECKED BY:</p>
                    <p>__________________________</p>
                    <p>DP ASSISTANT</p>
                </td>
                <td>
                    <p>APPROVED BY:</p>
                    <p>__________________________</p>
                    <p>DP HEAD</p>
                </td>
            </tr>
        </table>
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
