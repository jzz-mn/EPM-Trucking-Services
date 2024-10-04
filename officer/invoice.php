<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Handle form submission
  // Get the form data
  $billingStartDate = $_POST['billingStartDate'];
  $billingEndDate = $_POST['billingEndDate'];
  $billedTo = $_POST['billedTo'];

  // Validate the input data
  if (empty($billingStartDate) || empty($billingEndDate) || empty($billedTo)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Proceed with generating the invoice

    // Prepare and execute the query to select 'transactiongroup' records
    $query = "
            SELECT tg.TransactionGroupID, tg.RateAmount, e.TotalExpense
            FROM transactiongroup tg
            JOIN transactions t ON tg.TransactionGroupID = t.TransactionGroupID
            JOIN customers c ON t.OutletName = c.CustomerName
            JOIN clusters cl ON c.ClusterID = cl.ClusterID
            JOIN expenses e ON tg.ExpenseID = e.ExpenseID
            WHERE tg.Date BETWEEN ? AND ?
            AND tg.BillingInvoiceNo IS NULL
            AND cl.ClusterCategory = ?
            GROUP BY tg.TransactionGroupID
        ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('sss', $billingStartDate, $billingEndDate, $billedTo);
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize totals
    $grossAmount = 0;
    $totalExpenses = 0;
    $transactionGroupIDs = array();

    while ($row = $result->fetch_assoc()) {
      $grossAmount += $row['RateAmount'];
      $totalExpenses += $row['TotalExpense'];
      $transactionGroupIDs[] = $row['TransactionGroupID'];
    }

    if (empty($transactionGroupIDs)) {
      $error_message = "No transactions found for the selected date range and client.";
    } else {
      // Calculate VAT (12% of GrossAmount)
      $vat = $grossAmount * 0.12;

      // Calculate TotalAmount (GrossAmount + VAT)
      $totalAmount = $grossAmount + $vat;

      // Calculate EWT (2% of TotalAmount)
      $ewt = $totalAmount * 0.02;

      // Calculate AmountNetOfTax (TotalAmount - EWT)
      $amountNetOfTax = $totalAmount - $ewt;

      // AddTollCharges is total of TotalExpense
      $addTollCharges = $totalExpenses;

      // Calculate NetAmount (AmountNetOfTax + AddTollCharges)
      $netAmount = $amountNetOfTax + $addTollCharges;

      // Insert into 'invoices' table
      $invoiceQuery = "
                INSERT INTO invoices (BillingStartDate, BillingEndDate, BilledTo, GrossAmount, TotalAmount, VAT, EWT, AddTollCharges, AmountNetOfTax, NetAmount)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
      $stmt = $conn->prepare($invoiceQuery);
      $stmt->bind_param(
        'sssddddddd',
        $billingStartDate,
        $billingEndDate,
        $billedTo,
        $grossAmount,
        $totalAmount,
        $vat,
        $ewt,
        $addTollCharges,
        $amountNetOfTax,
        $netAmount
      );
      $stmt->execute();

      // Get the BillingInvoiceNo of the inserted invoice
      $billingInvoiceNo = $stmt->insert_id;

      // Update 'transactiongroup' records to set 'BillingInvoiceNo' to the new 'BillingInvoiceNo'
      if (!empty($transactionGroupIDs)) {
        // Sanitize IDs and prepare the IN clause
        $ids = implode(',', array_map('intval', $transactionGroupIDs));
        $updateQuery = "UPDATE transactiongroup SET BillingInvoiceNo = ? WHERE TransactionGroupID IN ($ids)";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('i', $billingInvoiceNo);
        $stmt->execute();
      }

      // Display success message
      $success_message = "Invoice generated successfully.";
    }
  }
}
?>

<div class="body-wrapper">
  <div class="container-fluid">
    <?php if (isset($error_message)): ?>
      <div class="alert alert-danger mt-3">
        <?php echo htmlspecialchars($error_message); ?>
      </div>
    <?php endif; ?>

    <?php if (isset($success_message)): ?>
      <div class="alert alert-success mt-3">
        <?php echo htmlspecialchars($success_message); ?>
      </div>
    <?php endif; ?>
    <div class="card card-body py-3">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="d-sm-flex align-items-center justify-space-between">
            <h4 class="mb-4 mb-sm-0 card-title">Invoices</h4>
            <nav aria-label="breadcrumb" class="ms-auto">
              <ol class="breadcrumb">
                <li class="breadcrumb-item d-flex align-items-center">
                  <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                  </a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                  <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                    Invoice
                  </span>
                </li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <div class="card overflow-hidden invoice-application">
      <div class="d-flex">
        <div class="w-100 w-xs-100 chat-container">
          <div class="invoice-inner-part h-100">
            <div class="invoiceing-box">
              <div class="invoice-header d-flex align-items-center border-bottom p-3">
                <a href="#" class="btn btn-primary d-flex align-items-center ms-auto" data-bs-toggle="modal"
                  data-bs-target="#addInvoiceModal">
                  <i class="ti ti-users text-white me-1 fs-5"></i> Add Invoice
                </a>
              </div>
              <?php
              // Fetch invoices from the 'invoices' table
              $invoicesQuery = "SELECT * FROM invoices ORDER BY BillingInvoiceNo DESC";
              $invoicesResult = $conn->query($invoicesQuery);
              ?>

              <div class="table-responsive mt-3">
                <?php if ($invoicesResult->num_rows > 0): ?>
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Billing Invoice No</th>
                        <th>Billing Start Date</th>
                        <th>Billing End Date</th>
                        <th>Billed To</th>
                        <th>Gross Amount</th>
                        <th>VAT</th>
                        <th>Total Amount</th>
                        <th>EWT</th>
                        <th>Add Toll Charges</th>
                        <th>Amount Net Of Tax</th>
                        <th>Net Amount</th>
                        <!-- Add other columns as needed -->
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($invoice = $invoicesResult->fetch_assoc()): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($invoice['BillingInvoiceNo']); ?></td>
                          <td><?php echo htmlspecialchars($invoice['BillingStartDate']); ?></td>
                          <td><?php echo htmlspecialchars($invoice['BillingEndDate']); ?></td>
                          <td><?php echo htmlspecialchars($invoice['BilledTo']); ?></td>
                          <td><?php echo number_format($invoice['GrossAmount'], 2); ?></td>
                          <td><?php echo number_format($invoice['VAT'], 2); ?></td>
                          <td><?php echo number_format($invoice['TotalAmount'], 2); ?></td>
                          <td><?php echo number_format($invoice['EWT'], 2); ?></td>
                          <td><?php echo number_format($invoice['AddTollCharges'], 2); ?></td>
                          <td><?php echo number_format($invoice['AmountNetOfTax'], 2); ?></td>
                          <td><?php echo number_format($invoice['NetAmount'], 2); ?></td>
                          <!-- Add other columns as needed -->
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                <?php else: ?>
                  <p>No invoices found.</p>
                <?php endif; ?>
              </div>

            </div>
          </div>
        </div>

        <!-- Add Invoice Modal -->
        <div class="modal fade" id="addInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="addInvoiceModalTitle"
          aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
              <div class="modal-header d-flex align-items-center bg-primary">
                <h5 class="modal-title text-white fs-4">Add Invoice Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="add-contact-box">
                  <div class="add-contact-content">
                    <!-- Add the method and action attributes -->
                    <form id="addInvoiceForm" method="post" action="invoice.php">
                      <div class="mb-3">
                        <label for="billingStartDate" class="form-label">Billing Start Date</label>
                        <input type="date" class="form-control" id="billingStartDate" name="billingStartDate" required>
                      </div>
                      <div class="mb-3">
                        <label for="billingEndDate" class="form-label">Billing End Date</label>
                        <input type="date" class="form-control" id="billingEndDate" name="billingEndDate" required>
                      </div>
                      <div class="mb-3">
                        <label for="billedTo" class="form-label">Billed To</label>
                        <select class="form-select" id="billedTo" name="billedTo" required>
                          <option value="">Select Client</option>
                          <option value="Bounty Plus">Bounty Plus</option>
                          <option value="Chooks to Go">Chooks to Go</option>
                        </select>
                      </div>
                      <div class="col-12 mb-3">
                        <div class="d-flex gap-6 m-0 justify-content-end">
                          <button type="button" class="btn bg-danger-subtle text-danger"
                            data-bs-dismiss="modal">Discard</button>
                          <button id="btn-add-invoice" class="btn btn-primary" type="submit">Generate Invoice</button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
    <?php
    include '../officer/footer.php';
    $conn->close();
    ?>