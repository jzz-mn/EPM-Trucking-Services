<?php
require_once '../includes/db_connection.php';
require_once '../vendor/autoload.php'; // Ensure the path is correct

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// --- Step 1: Retrieve BillingInvoiceNo and Format from POST ---
if (!isset($_POST['BillingInvoiceNo'])) {
    die('No invoice number provided.');
}

$billingInvoiceNo = intval($_POST['BillingInvoiceNo']);

// Get the format from POST
$format = isset($_POST['format']) ? $_POST['format'] : 'pdf'; // default to 'pdf'

// --- Step 2: Handle Logo Image ---
$logoPath = 'assets/images/logos/epm-logo-no-bg.png'; // Adjust the path as necessary
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $base64Logo = base64_encode($logoData);
} else {
    $base64Logo = ''; // Optional: Provide a default image or leave blank
}

// --- Step 3: Fetch Invoice Details ---
$invoiceQuery = "SELECT * FROM invoices WHERE BillingInvoiceNo = ?";
$stmt = $conn->prepare($invoiceQuery);
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param('i', $billingInvoiceNo);
$stmt->execute();
$invoiceResult = $stmt->get_result();
if ($invoiceResult->num_rows === 0) {
    die('Invoice not found.');
}
$invoice = $invoiceResult->fetch_assoc();
$stmt->close();

// --- Step 4: Fetch Transaction Groups Associated with the Invoice ---
$tgQuery = "
    SELECT tg.*, ti.PlateNo, e.TotalExpense, e.FuelID, f.FuelType, f.UnitPrice
    FROM transactiongroup tg
    JOIN trucksinfo ti ON tg.TruckID = ti.TruckID
    LEFT JOIN expenses e ON tg.ExpenseID = e.ExpenseID
    LEFT JOIN fuel f ON e.FuelID = f.FuelID
    WHERE tg.BillingInvoiceNo = ?
    ORDER BY tg.TransactionGroupID ASC
";
$stmt = $conn->prepare($tgQuery);
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param('i', $billingInvoiceNo);
$stmt->execute();
$tgResult = $stmt->get_result();
$transactionGroups = [];
while ($row = $tgResult->fetch_assoc()) {
    $transactionGroups[] = $row;
}
$stmt->close();

// --- Step 5: Fetch Transactions Associated with These Transaction Groups ---
$transactionGroupIDs = array_column($transactionGroups, 'TransactionGroupID');

$transactionsByGroup = [];
if (!empty($transactionGroupIDs)) {
    // Secure the IDs by ensuring they are integers
    $ids = implode(',', array_map('intval', $transactionGroupIDs));
    $txnQuery = "SELECT * FROM transactions WHERE TransactionGroupID IN ($ids) ORDER BY DRno ASC";
    $txnResult = $conn->query($txnQuery);
    if ($txnResult) {
        while ($txn = $txnResult->fetch_assoc()) {
            $tgid = $txn['TransactionGroupID'];
            if (!isset($transactionsByGroup[$tgid])) {
                $transactionsByGroup[$tgid] = [];
            }
            $transactionsByGroup[$tgid][] = $txn;
        }
    }
}

// --- Step 6: Generate Output Based on Format ---
if ($format === 'pdf') {
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
                font-size: 10px;
                text-transform: uppercase;
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
                margin-top: 20px;
            }

            .transactions-table th,
            .transactions-table td {
                border: 1px solid #000;
                padding: 5px;
            }

            .transactions-table th {
                background-color: #f0f0f0;
            }

            .transactions-table td.numeric,
            .transactions-table th.numeric {
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
                margin-left: auto;
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
        <!-- Header Section -->
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
                <p><strong>BILLING INVOICE #:</strong> SOA# <?php echo htmlspecialchars($invoice['BillingInvoiceNo']); ?>
                </p>
                <p><strong>BILLING DATE:</strong>
                    <?php echo strtoupper(date('M. d', strtotime($invoice['BillingStartDate']))); ?> -
                    <?php echo date('d, Y', strtotime($invoice['BillingEndDate'])); ?>
                </p>
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
                    $tollFeePW = $tg['TollFeeAmount'] ?? 0; // TollFeeAmount from transactiongroup
                    $rate_amount = $tg['RateAmount'] ?? 0; // RateAmount from transactiongroup
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
                        <td class="numeric"><?php echo '₱' . number_format($rate_amount, 2); ?></td>
                        <td class="numeric"><?php echo '₱' . number_format($tollFeePW, 2); ?></td>
                        <td class="numeric"><?php echo '₱' . number_format($amount, 2); ?></td>
                    </tr>
                    <?php
                    // End of transaction group
                    echo '<!-- End of Transaction Group -->';

                    // Accumulate totals
                    $totalRate += $rate_amount;
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

        <!-- Totals Section from Invoices Table -->
        <?php
        // Extract totals from the invoices table
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

        <!-- Signatures Section -->
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

    // --- Step 7: Configure Dompdf ---
    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans'); // Ensure UTF-8 support
    $options->set('isRemoteEnabled', true); // Enable loading of remote content (if needed)

    // Instantiate Dompdf with options
    $dompdf = new Dompdf($options);

    // Load the HTML content
    $dompdf->loadHtml($html);

    // (Optional) Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');

    // Render the HTML as PDF
    $dompdf->render();

    // Output the generated PDF to Browser
    $dompdf->stream('invoice_' . $billingInvoiceNo . '.pdf', array('Attachment' => false));

    // Close the database connection
    $conn->close();

    exit;

} elseif ($format === 'excel') {
    // Generate Excel file

    // Create a new Spreadsheet object
    $spreadsheet = new Spreadsheet();

    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('E.P.MONTALBO TRUCKING')
        ->setTitle('Invoice #' . $billingInvoiceNo)
        ->setSubject('Invoice')
        ->setDescription('Generated Invoice');

    $sheet = $spreadsheet->getActiveSheet();

    // --- Populate the spreadsheet with data ---

    // Header Section
    $rowNum = 1;

    // Company Info
    $sheet->setCellValue('A' . $rowNum, 'E.P.MONTALBO TRUCKING');
    $sheet->mergeCells('A' . $rowNum . ':J' . $rowNum);
    $sheet->getStyle('A' . $rowNum)->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal('center');
    $rowNum++;

    $sheet->setCellValue('A' . $rowNum, 'ELMA/MANNY MONTALBO');
    $sheet->mergeCells('A' . $rowNum . ':J' . $rowNum);
    $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal('center');
    $rowNum++;

    $sheet->setCellValue('A' . $rowNum, '244 COLIAT, IBAAN, BATANGAS');
    $sheet->mergeCells('A' . $rowNum . ':J' . $rowNum);
    $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal('center');
    $rowNum++;

    $sheet->setCellValue('A' . $rowNum, '09190053438 / e.p.montalbo@gmail.com');
    $sheet->mergeCells('A' . $rowNum . ':J' . $rowNum);
    $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal('center');
    $rowNum++;

    $sheet->setCellValue('A' . $rowNum, 'TIN# 730-494-707-000');
    $sheet->mergeCells('A' . $rowNum . ':J' . $rowNum);
    $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal('center');
    $rowNum += 2;

    $sheet->setCellValue('A' . $rowNum, 'STATEMENT OF ACCOUNT');
    $sheet->mergeCells('A' . $rowNum . ':J' . $rowNum);
    $sheet->getStyle('A' . $rowNum)->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal('center');
    $rowNum += 2;

    // Invoice Details
    $sheet->setCellValue('A' . $rowNum, 'BILLED TO:');
    $sheet->setCellValue('B' . $rowNum, $invoice['BilledTo']);
    $rowNum++;

    $sheet->setCellValue('A' . $rowNum, 'BILLING INVOICE #:');
    $sheet->setCellValue('B' . $rowNum, 'SOA# ' . $invoice['BillingInvoiceNo']);
    $rowNum++;

    $sheet->setCellValue('A' . $rowNum, 'BILLING DATE:');
    $sheet->setCellValue('B' . $rowNum, strtoupper(date('M. d', strtotime($invoice['BillingStartDate']))) . ' - ' . date('d, Y', strtotime($invoice['BillingEndDate'])));
    $rowNum += 2;

    // Transactions Table Headers
    $sheet->setCellValue('A' . $rowNum, 'DATE');
    $sheet->setCellValue('B' . $rowNum, 'PLATE #');
    $sheet->setCellValue('C' . $rowNum, 'DR #');
    $sheet->setCellValue('D' . $rowNum, 'DIESEL');
    $sheet->setCellValue('E' . $rowNum, 'OUTLET NAME');
    $sheet->setCellValue('F' . $rowNum, 'QTY');
    $sheet->setCellValue('G' . $rowNum, 'KGS');
    $sheet->setCellValue('H' . $rowNum, 'RATE');
    $sheet->setCellValue('I' . $rowNum, 'TOLLFEE/PW');
    $sheet->setCellValue('J' . $rowNum, 'AMOUNT');
    $sheet->getStyle('A' . $rowNum . ':J' . $rowNum)->getFont()->setBold(true);
    $sheet->getStyle('A' . $rowNum . ':J' . $rowNum)->getAlignment()->setHorizontal('center');
    $rowNum++;

    // Initialize overall totals
    $totalRate = 0;
    $totalTollFee = 0;
    $totalAmount = 0;

    foreach ($transactionGroups as $tg) {
        // Initialize subtotals
        $subtotalQty = 0;
        $subtotalKGs = 0;

        $fuelUnitPrice = $tg['UnitPrice'] ?? 0; // UnitPrice from fuel table
        $tollFeePW = $tg['TollFeeAmount'] ?? 0; // TollFeeAmount from transactiongroup
        $rate_amount = $tg['RateAmount'] ?? 0; // RateAmount from transactiongroup
        $amount = $tg['Amount'] ?? 0; // Amount from transactiongroup

        if (isset($transactionsByGroup[$tg['TransactionGroupID']])) {
            foreach ($transactionsByGroup[$tg['TransactionGroupID']] as $txn) {
                $subtotalQty += $txn['Qty'];
                $subtotalKGs += $txn['KGs'];

                $sheet->setCellValue('A' . $rowNum, date('j-M-Y', strtotime($txn['TransactionDate'])));
                $sheet->setCellValue('B' . $rowNum, $tg['PlateNo']);
                $sheet->setCellValue('C' . $rowNum, $txn['DRno']);
                $sheet->setCellValue('D' . $rowNum, '-');
                $sheet->setCellValue('E' . $rowNum, $txn['OutletName']);
                $sheet->setCellValue('F' . $rowNum, number_format($txn['Qty'], 2));
                $sheet->setCellValue('G' . $rowNum, number_format($txn['KGs'], 2));
                $sheet->setCellValue('H' . $rowNum, '-');
                $sheet->setCellValue('I' . $rowNum, '-');
                $sheet->setCellValue('J' . $rowNum, '-');
                $rowNum++;
            }
        }

        // Subtotal row for the transaction group
        $sheet->setCellValue('A' . $rowNum, 'SUB TOTAL');
        $sheet->mergeCells('A' . $rowNum . ':C' . $rowNum);
        $sheet->setCellValue('D' . $rowNum, '₱' . number_format($fuelUnitPrice, 2));
        $sheet->setCellValue('E' . $rowNum, '');
        $sheet->setCellValue('F' . $rowNum, number_format($subtotalQty, 2));
        $sheet->setCellValue('G' . $rowNum, number_format($subtotalKGs, 2));
        $sheet->setCellValue('H' . $rowNum, '₱' . number_format($rate_amount, 2));
        $sheet->setCellValue('I' . $rowNum, '₱' . number_format($tollFeePW, 2));
        $sheet->setCellValue('J' . $rowNum, '₱' . number_format($amount, 2));
        $sheet->getStyle('A' . $rowNum . ':J' . $rowNum)->getFont()->setBold(true);
        $rowNum++;

        // Accumulate totals
        $totalRate += $rate_amount;
        $totalTollFee += $tollFeePW;
        $totalAmount += $amount;
    }

    // Total row at the bottom
    $sheet->setCellValue('A' . $rowNum, 'TOTAL');
    $sheet->mergeCells('A' . $rowNum . ':G' . $rowNum);
    $sheet->setCellValue('H' . $rowNum, '₱' . number_format($totalRate, 2));
    $sheet->setCellValue('I' . $rowNum, '₱' . number_format($totalTollFee, 2));
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($totalAmount, 2));
    $sheet->getStyle('A' . $rowNum . ':J' . $rowNum)->getFont()->setBold(true);
    $rowNum += 2;

    // Totals Section from Invoices Table
    $grossAmount = $invoice['GrossAmount'];
    $vat = $invoice['VAT'];
    $totalAmountInvoice = $invoice['TotalAmount'];
    $ewt = $invoice['EWT'];
    $amountNetOfTax = $invoice['AmountNetOfTax'];
    $addTollCharges = $invoice['AddTollCharges'];
    $netAmount = $invoice['NetAmount'];

    // Totals
    $sheet->setCellValue('I' . $rowNum, 'GROSS AMOUNT:');
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($grossAmount, 2));
    $rowNum++;

    $sheet->setCellValue('I' . $rowNum, 'ADD: VAT 12%');
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($vat, 2));
    $rowNum++;

    $sheet->setCellValue('I' . $rowNum, 'TOTAL AMOUNT');
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($totalAmountInvoice, 2));
    $rowNum++;

    $sheet->setCellValue('I' . $rowNum, 'LESS: EWT 2%');
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($ewt, 2));
    $rowNum++;

    $sheet->setCellValue('I' . $rowNum, 'AMOUNT NET OF TAX');
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($amountNetOfTax, 2));
    $rowNum++;

    $sheet->setCellValue('I' . $rowNum, 'ADD: TOLL/CHARGES');
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($addTollCharges, 2));
    $rowNum++;

    $sheet->setCellValue('I' . $rowNum, 'NET AMOUNT:');
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($netAmount, 2));
    $sheet->getStyle('I' . $rowNum . ':J' . $rowNum)->getFont()->setBold(true);
    $rowNum += 2;

    // Adjust column widths and styles
    foreach (range('A', 'J') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Set number formats for numeric columns
    $numericColumns = ['F', 'G', 'H', 'I', 'J'];
    for ($i = 1; $i <= $rowNum; $i++) {
        foreach ($numericColumns as $col) {
            $sheet->getStyle($col . $i)->getAlignment()->setHorizontal('right');
        }
    }

    // Output the Excel file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="invoice_' . $billingInvoiceNo . '.xlsx"');
    header('Cache-Control: max-age=0');

    // Write file to output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

    // Close the database connection
    $conn->close();

    exit;
} else {
    die('Invalid format specified.');
}