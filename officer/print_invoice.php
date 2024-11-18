<?php
require_once '../includes/db_connection.php';
require_once '../vendor/autoload.php'; // Ensure the path is correct

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// --- Step 1: Retrieve BillingInvoiceNo and Format from POST ---
if (!isset($_POST['BillingInvoiceNo'])) {
    die('No invoice number provided.');
}

$billingInvoiceNo = intval($_POST['BillingInvoiceNo']);

// Get the format from POST
$format = isset($_POST['format']) ? $_POST['format'] : 'pdf'; // default to 'pdf'

// --- Step 2: Handle Logo Image ---
$logoPath = '../assetsEPM/logos/epm-logo-no-bg.png'; // Adjusted the path as per your requirement
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $base64Logo = base64_encode($logoData);
} else {
    $base64Logo = ''; // Optional: Provide a default image or leave blank
}

// --- Step 3: Handle Signature Image ---
$signaturePath = '../assetsEPM/images/sample.png'; // Signature image path
if (file_exists($signaturePath)) {
    $signatureData = file_get_contents($signaturePath);
    $signatureBase64 = base64_encode($signatureData);
} else {
    $signatureBase64 = ''; // Optional: Handle as needed
}

// --- Step 4: Fetch Invoice Details ---
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

// --- Step 5: Generate Filename as per Requirement ---
$billingStartDate = $invoice['BillingStartDate'];
$billingEndDate = $invoice['BillingEndDate'];

$startMonth = strtoupper(date('M', strtotime($billingStartDate)));
$startDay = date('j', strtotime($billingStartDate));
$endDay = date('j', strtotime($billingEndDate));
$year = date('Y', strtotime($billingEndDate)); // Assuming both dates are in the same year

$dateRangeStr = $startMonth . ' ' . $startDay . '-' . $endDay . ' ' . $year;

$filename = 'INV ' . $invoice['BillingInvoiceNo'] . 'E ' . $dateRangeStr . ' BILLING INVOICE';

// --- Step 6: Fetch Transaction Groups Associated with the Invoice ---
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

// --- Step 7: Fetch Transactions Associated with These Transaction Groups ---
$transactionGroupIDs = array_column($transactionGroups, 'TransactionGroupID');

$transactionsByGroup = [];
if (!empty($transactionGroupIDs)) {
    // Secure the IDs by ensuring they are integers
    $ids = implode(',', array_map('intval', $transactionGroupIDs));
    // Modified Query: Join with customers to get CustomerLocCode and CustomerCode
    $txnQuery = "
        SELECT t.*, c.CustomerLocCode, c.CustomerCode 
        FROM transactions t
        LEFT JOIN customers c ON t.OutletName = c.CustomerName
        WHERE t.TransactionGroupID IN ($ids) 
        ORDER BY t.DRno ASC
    ";
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

// --- Step 8: Generate Output Based on Format ---
if ($format === 'pdf') {
    // Generate the HTML content for the PDF
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title><?php echo htmlspecialchars($filename); ?></title>
        <style>
            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 8px;
                /* Reduced font size */
                text-transform: uppercase;
                margin: 0;
                /* Removed default margins */
                padding: 0;
                /* Removed default padding */
            }

            .invoice-header {
                margin-bottom: 0;
                /* Removed bottom margin */
                padding: 0;
                /* Removed padding */
            }

            .header-content {
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
                margin: 0;
                padding: 0;
            }

            .logo {
                position: absolute;
                left: 0;
                margin: 0;
                padding: 0;
            }

            .company-info {
                text-align: center;
                margin: 0;
                padding: 0;
            }

            .company-info h2 {
                margin: 0;
                padding: 0;
            }

            .company-info p {
                margin: 0;
                padding: 0;
            }

            .invoice-details {
                text-align: left;
                margin-top: 0;
                /* Removed top margin */
                padding: 0;
            }

            .invoice-details p {
                margin: 0;
                padding: 0;
            }

            .transactions-table {
                border-collapse: collapse;
                width: 100%;
                margin-top: 0;
                /* Removed top margin */
                margin-bottom: 0;
                /* Removed bottom margin */
                padding: 0;
            }

            .transactions-table th,
            .transactions-table td {
                border: 1px solid #000;
                padding: 2px;
                /* Reduced padding */
                text-align: center; /* Center-align all data */
                vertical-align: middle; /* Vertically center-align */
                margin: 0;
            }

            .transactions-table th {
                background-color: #f0f0f0;
                margin: 0;
                padding: 2px;
            }

            /* Remove numeric class alignment */
            /* .transactions-table td.numeric,
            .transactions-table th.numeric {
                text-align: right;
            } */

            .transaction-group {
                page-break-inside: avoid;
                border: 2px solid #000; /* Bold border for SUB TOTAL */
                font-weight: bold; /* Bold text for SUB TOTAL */
            }

            .totals-row {
                page-break-inside: avoid;
            }

            .totals {
                margin-top: 0;
                /* Removed top margin */
                padding: 0;
                page-break-inside: avoid;
            }

            .totals-table {
                width: 50%;
                margin-left: auto;
                border-collapse: collapse;
                margin-top: 0;
                padding: 0;
            }

            .totals-table td {
                padding: 2px;
                /* Reduced padding */
                margin: 0;
                text-align: center; /* Center-align totals */
            }

            .totals-table td.numeric {
                text-align: center;
            }

            .totals-table td:first-child {
                text-align: left;
            }

            .signatures {
                margin-top: 30px;
                /* Reduced top margin */
                page-break-inside: avoid;
            }

            .signature-table {
                width: 100%;
            }

            .signature-table td {
                width: 50%;
                vertical-align: top;
                padding: 5px;
            }

            .signature-table p {
                margin: 5px 0;
                padding: 0;
                text-align: center; /* Center-align signature text */
            }

            .signature-container {
                display: flex;
                align-items: center;
                justify-content: flex-start;
            }

            .signature-container p {
                margin: 0 10px 0 0;
                padding: 0;
            }

            .signature-container img {
                margin-right: 10px;
                padding: 0;
            }

            /* Style for TOTAL row */
            .totals-row td {
                border: 2px solid #000; /* Bold border for TOTAL */
                font-weight: bold; /* Bold text for TOTAL */
            }

            /* Add empty row styling if needed */
        </style>
    </head>

    <body>
        <!-- Header Section -->
        <div class="invoice-header">
            <div class="header-content">
                <div class="logo">
                    <?php if ($base64Logo): ?>
                        <img src="data:image/png;base64,<?php echo $base64Logo; ?>" alt="Logo" width="80">
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
            </div>
            <div class="invoice-details">
                <p><strong>BILLED TO:</strong> <?php echo htmlspecialchars($invoice['BilledTo']); ?></p>
                <p><strong>BILLING INVOICE #:</strong> SOA# <?php echo htmlspecialchars($invoice['BillingInvoiceNo']); ?>-E
                </p>
                <p><strong>SERVICE NO:</strong> <?php echo htmlspecialchars($invoice['ServiceNo']); ?></p>
                <p><strong>BILLING DATE:</strong> <?php echo htmlspecialchars($dateRangeStr); ?></p>
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
                <tr class="header">
                    <th>DATE</th>
                    <th>PLATE #</th>
                    <th>DR #</th>
                    <th>DIESEL</th>
                    <th colspan="2">CUSTOMER CODE</th>
                    <th>QTY</th>
                    <th>KGS</th>
                    <th>RATE</th>
                    <th>TOLLFEE/PW</th>
                    <th>AMOUNT</th>
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
                                <td>-</td>
                                <td><?php echo htmlspecialchars($txn['CustomerLocCode'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($txn['CustomerCode'] ?? '-'); ?></td>
                                <td><?php echo number_format($txn['Qty'], 2, '.', ''); ?></td>
                                <td><?php echo number_format($txn['KGs'], 2, '.', ''); ?></td>
                                <!-- Do not show values in Rate, TollFee/PW, and Amount columns -->
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <?php
                        endforeach;
                    endif;

                    // Subtotal row for the transaction group
                    ?>
                    <tr class="transaction-group">
                        <td colspan="4"><strong>SUB TOTAL</strong></td>
                        <td colspan="2"></td>
                        <td><?php echo number_format($subtotalQty, 2, '.', ''); ?></td>
                        <td><?php echo number_format($subtotalKGs, 2, '.', ''); ?></td>
                        <td><?php echo '₱' . number_format($rate_amount, 2, '.', ''); ?></td>
                        <td><?php echo '₱' . number_format($tollFeePW, 2, '.', ''); ?></td>
                        <td><?php echo '₱' . number_format($amount, 2, '.', ''); ?></td>
                    </tr>
                    <!-- Empty row after SUB TOTAL -->
                    <tr>
                        <td colspan="11">&nbsp;</td>
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
                    <td colspan="8"><strong>TOTAL</strong></td>
                    <td><strong>₱<?php echo number_format($totalRate, 2, '.', ''); ?></strong></td>
                    <td><strong>₱<?php echo number_format($totalTollFee, 2, '.', ''); ?></strong></td>
                    <td><strong>₱<?php echo number_format($totalAmount, 2, '.', ''); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Add a space after totals -->
        <div style="height: 10px;"></div>

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
                    <td>₱<?php echo number_format($grossAmount, 2, '.', ''); ?></td>
                </tr>
                <tr>
                    <td><strong>ADD: VAT 12%</strong></td>
                    <td>₱<?php echo number_format($vat, 2, '.', ''); ?></td>
                </tr>
                <tr>
                    <td><strong>TOTAL AMOUNT</strong></td>
                    <td>₱<?php echo number_format($totalAmountInvoice, 2, '.', ''); ?></td>
                </tr>
                <tr>
                    <td><strong>LESS: EWT 2%</strong></td>
                    <td>₱<?php echo number_format($ewt, 2, '.', ''); ?></td>
                </tr>
                <tr>
                    <td><strong>AMOUNT NET OF TAX</strong></td>
                    <td>₱<?php echo number_format($amountNetOfTax, 2, '.', ''); ?></td>
                </tr>
                <tr>
                    <td><strong>ADD: TOLL/CHARGES</strong></td>
                    <td>₱<?php echo number_format($addTollCharges, 2, '.', ''); ?></td>
                </tr>
                <tr>
                    <td><strong>NET AMOUNT:</strong></td>
                    <td>₱<?php echo number_format($netAmount, 2, '.', ''); ?></td>
                </tr>
            </table>
        </div>

        <!-- Signatures Section -->
        <div class="signatures">
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="signature-container">
                            <p>PREPARED BY:</p>
                            <?php if ($signatureBase64): ?>
                                <img src="data:image/png;base64,<?php echo $signatureBase64; ?>" alt="Signature" width="80">
                            <?php endif; ?>
                            <p><strong>MANNY MONTALBO</strong></p>
                            <p>E.P.MONTALBO TRUCKING</p>
                        </div>
                    </td>
                    <td>
                        <p>RECEIVED BY: __________________________</p>
                        <p>DATE:</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p>CHECKED BY: __________________________ DP ASSISTANT</p>
                    </td>
                    <td>
                        <p>APPROVED BY: __________________________ DP HEAD</p>
                    </td>
                </tr>
            </table>
        </div>
    </body>

    </html>
    <?php
    $html = ob_get_clean();

    // --- Step 9: Configure Dompdf ---
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

    // Output the generated PDF to Browser with the specified filename
    $dompdf->stream($filename . '.pdf', array('Attachment' => false));

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
        ->setTitle($filename)
        ->setSubject('Invoice')
        ->setDescription('Generated Invoice');

    $sheet = $spreadsheet->getActiveSheet();

    // --- Insert the Logo ---
    if (file_exists($logoPath)) {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Company Logo');
        $drawing->setPath($logoPath); // Path to your image file
        $drawing->setHeight(80); // Adjust the height as needed
        $drawing->setCoordinates('A1');
        $drawing->setWorksheet($sheet);
    }

    // --- Populate the spreadsheet with data ---

    // Header Section
    $rowNum = 1;

    // Adjust rowNum if logo is inserted
    if (file_exists($logoPath)) {
        $rowNum = 6; // Adjust the starting row after the logo
    }

    // Company Info
    $sheet->setCellValue('A' . $rowNum, 'E.P.MONTALBO TRUCKING');
    $sheet->mergeCells('A' . $rowNum . ':J' . $rowNum); // Adjusted to J for 10 columns
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

    $sheet->setCellValue('A' . $rowNum, 'SERVICE NO:');
    $sheet->setCellValue('B' . $rowNum, $invoice['ServiceNo']);
    $rowNum++;

    $sheet->setCellValue('A' . $rowNum, 'BILLING DATE:');
    $sheet->setCellValue('B' . $rowNum, $dateRangeStr);
    $rowNum += 2;

    // Transactions Table Headers
    // Single Header Row
    $sheet->setCellValue('A' . $rowNum, 'DATE');
    $sheet->setCellValue('B' . $rowNum, 'PLATE #');
    $sheet->setCellValue('C' . $rowNum, 'DR #');
    $sheet->setCellValue('D' . $rowNum, 'DIESEL');
    $sheet->setCellValue('E' . $rowNum, 'CUSTOMER CODE');
    $sheet->setCellValue('F' . $rowNum, 'QTY');
    $sheet->setCellValue('G' . $rowNum, 'KGS');
    $sheet->setCellValue('H' . $rowNum, 'RATE');
    $sheet->setCellValue('I' . $rowNum, 'TOLLFEE/PW');
    $sheet->setCellValue('J' . $rowNum, 'AMOUNT');
    // Merge cells for 'CUSTOMER CODE' header
    $sheet->mergeCells('E' . $rowNum . ':F' . $rowNum);
    $sheet->setCellValue('E' . $rowNum, 'CUSTOMER CODE');
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

                // Combine CustomerLocCode and CustomerCode in separate cells
                $customerLocCode = $txn['CustomerLocCode'] ?? '-';
                $customerCode = $txn['CustomerCode'] ?? '-';

                $sheet->setCellValue('A' . $rowNum, date('j-M-Y', strtotime($txn['TransactionDate'])));
                $sheet->setCellValue('B' . $rowNum, $tg['PlateNo']);
                $sheet->setCellValue('C' . $rowNum, $txn['DRno']);
                $sheet->setCellValue('D' . $rowNum, '-');
                $sheet->setCellValue('E' . $rowNum, $customerLocCode);
                $sheet->setCellValue('F' . $rowNum, $customerCode);
                $sheet->setCellValue('G' . $rowNum, number_format($txn['Qty'], 2, '.', ''));
                $sheet->setCellValue('H' . $rowNum, number_format($txn['KGs'], 2, '.', ''));
                $sheet->setCellValue('I' . $rowNum, '-');
                $sheet->setCellValue('J' . $rowNum, '-');
                $rowNum++;
            }
        }

        // Subtotal row for the transaction group
        $sheet->setCellValue('A' . $rowNum, 'SUB TOTAL');
        $sheet->mergeCells('A' . $rowNum . ':D' . $rowNum);
        $sheet->setCellValue('E' . $rowNum, '');
        $sheet->setCellValue('F' . $rowNum, '');
        $sheet->setCellValue('G' . $rowNum, number_format($subtotalQty, 2, '.', ''));
        $sheet->setCellValue('H' . $rowNum, number_format($subtotalKGs, 2, '.', ''));
        $sheet->setCellValue('I' . $rowNum, '₱' . number_format($rate_amount, 2, '.', ''));
        $sheet->setCellValue('J' . $rowNum, '₱' . number_format($tollFeePW, 2, '.', ''));
        // Bold the SUB TOTAL row text
        $sheet->getStyle('A' . $rowNum . ':J' . $rowNum)->getFont()->setBold(true);
        // Apply bold borders to SUB TOTAL row
        $sheet->getStyle('A' . $rowNum . ':J' . $rowNum)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $rowNum++;

        // Add an empty row after SUB TOTAL
        $sheet->setCellValue('A' . $rowNum, '');
        $sheet->mergeCells('A' . $rowNum . ':J' . $rowNum);
        $rowNum++;

        // Accumulate totals
        $totalRate += $rate_amount;
        $totalTollFee += $tollFeePW;
        $totalAmount += $amount;
    }

    // Total row at the bottom
    $sheet->setCellValue('A' . $rowNum, 'TOTAL');
    $sheet->mergeCells('A' . $rowNum . ':F' . $rowNum); // Merge first six columns for 'TOTAL' label
    $sheet->setCellValue('G' . $rowNum, '₱' . number_format($totalRate, 2, '.', ''));
    $sheet->setCellValue('H' . $rowNum, '₱' . number_format($totalTollFee, 2, '.', ''));
    $sheet->setCellValue('I' . $rowNum, '₱' . number_format($totalAmount, 2, '.', ''));
    // Bold the TOTAL row text
    $sheet->getStyle('A' . $rowNum . ':J' . $rowNum)->getFont()->setBold(true);
    // Apply bold borders to TOTAL row
    $sheet->getStyle('A' . $rowNum . ':J' . $rowNum)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
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
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($grossAmount, 2, '.', ''));
    $rowNum++;

    $sheet->setCellValue('I' . $rowNum, 'ADD: VAT 12%');
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($vat, 2, '.', ''));
    $rowNum++;

    $sheet->setCellValue('I' . $rowNum, 'TOTAL AMOUNT');
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($totalAmountInvoice, 2, '.', ''));
    $rowNum++;

    $sheet->setCellValue('I' . $rowNum, 'LESS: EWT 2%');
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($ewt, 2, '.', ''));
    $rowNum++;

    $sheet->setCellValue('I' . $rowNum, 'AMOUNT NET OF TAX');
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($amountNetOfTax, 2, '.', ''));
    $rowNum++;

    $sheet->setCellValue('I' . $rowNum, 'ADD: TOLL/CHARGES');
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($addTollCharges, 2, '.', ''));
    $rowNum++;

    $sheet->setCellValue('I' . $rowNum, 'NET AMOUNT:');
    $sheet->setCellValue('J' . $rowNum, '₱' . number_format($netAmount, 2, '.', ''));
    $sheet->getStyle('I' . $rowNum . ':J' . $rowNum)->getFont()->setBold(true);
    $rowNum += 2;

    // --- Insert Signature Image ---
    if (file_exists($signaturePath)) {
        $signatureDrawing = new Drawing();
        $signatureDrawing->setName('Signature');
        $signatureDrawing->setDescription('Signature Image');
        $signatureDrawing->setPath($signaturePath); // Path to your signature image file
        $signatureDrawing->setHeight(60); // Adjust the height as needed
        $signatureDrawing->setCoordinates('A' . $rowNum);
        $signatureDrawing->setOffsetX(10);
        $signatureDrawing->setOffsetY(10);
        $signatureDrawing->setWorksheet($sheet);
    }

    // Signatures Section
    $sheet->setCellValue('A' . ($rowNum + 4), 'PREPARED BY:');
    $sheet->setCellValue('A' . ($rowNum + 5), 'MANNY MONTALBO');
    $sheet->setCellValue('A' . ($rowNum + 6), 'E.P.MONTALBO TRUCKING');

    // Adjust column widths and styles
    foreach (range('A', 'J') as $columnID) { // Adjusted to 'J' for 10 columns
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Set number formats for numeric columns
    $numericColumns = ['G', 'H', 'I', 'J'];
    for ($i = 1; $i <= $rowNum + 6; $i++) {
        foreach ($numericColumns as $col) {
            $sheet->getStyle($col . $i)->getAlignment()->setHorizontal('center');
            // Set number format to number with 2 decimals
            $sheet->getStyle($col . $i)->getNumberFormat()->setFormatCode('#,##0.00');
        }
    }

    // Output the Excel file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
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
?>
