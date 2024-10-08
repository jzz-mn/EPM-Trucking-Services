<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Check if it's an AJAX request
  if (isset($_POST['action']) && $_POST['action'] == 'fetch_records') {
    // Fetch selected records
    $billingStartDate = $_POST['billingStartDate'];
    $billingEndDate = $_POST['billingEndDate'];

    // Validate dates
    if (empty($billingStartDate) || empty($billingEndDate)) {
      echo json_encode(['success' => false, 'message' => 'Please provide both start and end dates.']);
      exit;
    } elseif ($billingStartDate > $billingEndDate) {
      echo json_encode(['success' => false, 'message' => 'Billing Start Date cannot be after Billing End Date.']);
      exit;
    }

    // Query to fetch transactiongroup records
    $query = "
            SELECT tg.TransactionGroupID, tg.Date, tg.RateAmount, tg.TotalKGs, e.TotalExpense
            FROM transactiongroup tg
            JOIN expenses e ON tg.ExpenseID = e.ExpenseID
            WHERE tg.Date BETWEEN ? AND ?
        ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $billingStartDate, $billingEndDate);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if records are found
    if ($result->num_rows > 0) {
      $html = '';
      while ($row = $result->fetch_assoc()) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['TransactionGroupID']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['Date']) . '</td>';
        $html .= '<td>' . number_format($row['RateAmount'], 2) . '</td>';
        $html .= '<td>' . number_format($row['TotalKGs'], 2) . '</td>';
        $html .= '<td>' . number_format($row['TotalExpense'], 2) . '</td>';
        $html .= '</tr>';
      }
      echo json_encode(['success' => true, 'html' => $html]);
    } else {
      echo json_encode(['success' => false, 'message' => 'No transactions found for the selected date range.']);
    }
    exit;
  } elseif (isset($_POST['action']) && $_POST['action'] == 'generate_invoice') {
    // Generate invoice
    $billingStartDate = $_POST['billingStartDate'];
    $billingEndDate = $_POST['billingEndDate'];
    $billedTo = $_POST['billedTo'];

    // Validate input data
    if (empty($billingStartDate) || empty($billingEndDate) || empty($billedTo)) {
      echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
      exit;
    } elseif ($billingStartDate > $billingEndDate) {
      echo json_encode(['success' => false, 'message' => 'Billing Start Date cannot be after Billing End Date.']);
      exit;
    }

    // Proceed with generating the invoice
    // Wrap in a transaction
    $conn->begin_transaction();
    try {
      // Query to select transactiongroup records
      $query = "
                SELECT tg.TransactionGroupID, tg.RateAmount, e.TotalExpense
                FROM transactiongroup tg
                JOIN expenses e ON tg.ExpenseID = e.ExpenseID
                WHERE tg.Date BETWEEN ? AND ?
            ";

      $stmt = $conn->prepare($query);
      $stmt->bind_param('ss', $billingStartDate, $billingEndDate);
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
        throw new Exception('No transactions found for the selected date range.');
      }

      // Calculate amounts
      $vat = $grossAmount * 0.12;
      $totalAmount = $grossAmount + $vat;
      $ewt = $totalAmount * 0.02;
      $amountNetOfTax = $totalAmount - $ewt;
      $addTollCharges = $totalExpenses;
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

      // Commit the transaction
      $conn->commit();

      echo json_encode(['success' => true]);
    } catch (Exception $e) {
      // Rollback the transaction
      $conn->rollback();
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
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
                        <th>Invoice No</th>
                        <th>Date Range</th>
                        <th>Billed To</th>
                        <th>Gross Amount</th>
                        <th>Net Amount</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($invoice = $invoicesResult->fetch_assoc()): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($invoice['BillingInvoiceNo']); ?></td>
                          <td><?php echo htmlspecialchars($invoice['BillingStartDate']); ?> -
                            <?php echo htmlspecialchars($invoice['BillingEndDate']); ?>
                          </td>
                          <td><?php echo htmlspecialchars($invoice['BilledTo']); ?></td>
                          <td><?php echo number_format($invoice['GrossAmount'], 2); ?></td>
                          <td><?php echo number_format($invoice['NetAmount'], 2); ?></td>
                          <td>
                            <!-- Print Button -->
                            <form action="print_invoice.php" method="post" target="_blank" style="display:inline;">
                              <input type="hidden" name="BillingInvoiceNo"
                                value="<?php echo htmlspecialchars($invoice['BillingInvoiceNo']); ?>">
                              <button type="submit" class="btn btn-primary btn-sm">Print</button>
                            </form>
                          </td>
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
                    <form id="addInvoiceForm">
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
                          <button id="btn-add-invoice" class="btn btn-primary" type="button">Generate Invoice</button>
                        </div>
                      </div>
                    </form>

                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Selected Records Modal -->
        <div class="modal fade" id="selectedRecordsModal" tabindex="-1" aria-labelledby="selectedRecordsModalLabel"
          aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 90%;">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title fw-bold" id="selectedRecordsModalLabel">Selected Transaction Groups</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <!-- Table to display selected records -->
                <div class="table-responsive">
                  <table class="table table-striped" id="selectedRecordsTable">
                    <thead>
                      <tr>
                        <th>Transaction Group ID</th>
                        <th>Date</th>
                        <th>Rate Amount</th>
                        <th>Total KGs</th>
                        <th>Total Expense</th>
                        <!-- Add other columns as needed -->
                      </tr>
                    </thead>
                    <tbody>
                      <!-- Data will be populated via AJAX -->
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmGenerateInvoice">Confirm and Generate
                  Invoice</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script>
      $(document).ready(function () {
        $('#btn-add-invoice').click(function () {
          // Get form data
          var billingStartDate = $('#billingStartDate').val();
          var billingEndDate = $('#billingEndDate').val();
          var billedTo = $('#billedTo').val();

          // Validate inputs
          if (!billingStartDate || !billingEndDate || !billedTo) {
            alert('Please fill in all required fields.');
            return;
          }

          // Send AJAX request to fetch selected records
          $.ajax({
            url: 'invoice.php',
            type: 'POST',
            data: {
              action: 'fetch_records',
              billingStartDate: billingStartDate,
              billingEndDate: billingEndDate,
              billedTo: billedTo
            },
            dataType: 'json',
            success: function (response) {
              if (response.success) {
                // Populate the modal with the data
                $('#selectedRecordsTable tbody').html(response.html);
                // Show the modal
                $('#selectedRecordsModal').modal('show');
                // Close the add invoice modal
                $('#addInvoiceModal').modal('hide');
              } else {
                alert(response.message);
              }
            },
            error: function (xhr, status, error) {
              console.error(xhr.responseText);
              alert('An error occurred while fetching the records.');
            }
          });
        });

        // Handle invoice confirmation
        $('#confirmGenerateInvoice').click(function () {
          // Send AJAX request to generate the invoice
          $.ajax({
            url: 'invoice.php',
            type: 'POST',
            data: {
              action: 'generate_invoice',
              billingStartDate: $('#billingStartDate').val(),
              billingEndDate: $('#billingEndDate').val(),
              billedTo: $('#billedTo').val()
            },
            dataType: 'json',
            success: function (response) {
              if (response.success) {
                alert('Invoice generated successfully.');
                // Reload the page to show the new invoice
                location.reload();
              } else {
                alert(response.message);
              }
            },
            error: function (xhr, status, error) {
              console.error(xhr.responseText);
              alert('An error occurred while generating the invoice.');
            }
          });
        });
      });
    </script>
    <div class="offcanvas customizer offcanvas-end" tabindex="-1" id="offcanvasExample"
  aria-labelledby="offcanvasExampleLabel">
  <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
    <h4 class="offcanvas-title fw-semibold" id="offcanvasExampleLabel">
      Settings
    </h4>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body" data-simplebar style="height: calc(100vh - 80px)">
    <h6 class="fw-semibold fs-4 mb-2">Theme</h6>

    <div class="d-flex flex-row gap-3 customizer-box" role="group">
      <input type="radio" class="btn-check light-layout" name="theme-layout" id="light-layout" autocomplete="off" />
      <label class="btn p-9 btn-outline-primary rounded-2" for="light-layout">
        <i class="icon ti ti-brightness-up fs-7 me-2"></i>Light
      </label>

      <input type="radio" class="btn-check dark-layout" name="theme-layout" id="dark-layout" autocomplete="off" />
      <label class="btn p-9 btn-outline-primary rounded-2" for="dark-layout">
        <i class="icon ti ti-moon fs-7 me-2"></i>Dark
      </label>
    </div>

    <h6 class="mt-5 fw-semibold fs-4 mb-2">Theme Direction</h6>
    <div class="d-flex flex-row gap-3 customizer-box" role="group">
      <input type="radio" class="btn-check" name="direction-l" id="ltr-layout" autocomplete="off" />
      <label class="btn p-9 btn-outline-primary rounded-2" for="ltr-layout">
        <i class="icon ti ti-text-direction-ltr fs-7 me-2"></i>LTR
      </label>

      <input type="radio" class="btn-check" name="direction-l" id="rtl-layout" autocomplete="off" />
      <label class="btn p-9 btn-outline-primary rounded-2" for="rtl-layout">
        <i class="icon ti ti-text-direction-rtl fs-7 me-2"></i>RTL
      </label>
    </div>

    <h6 class="mt-5 fw-semibold fs-4 mb-2">Theme Colors</h6>

    <div class="d-flex flex-row flex-wrap gap-3 customizer-box color-pallete" role="group">
      <input type="radio" class="btn-check" name="color-theme-layout" id="Blue_Theme" autocomplete="off" />
      <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
        onclick="handleColorTheme('Blue_Theme')" for="Blue_Theme" data-bs-toggle="tooltip" data-bs-placement="top"
        data-bs-title="BLUE_THEME">
        <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-1">
          <i class="ti ti-check text-white d-flex icon fs-5"></i>
        </div>
      </label>

      <input type="radio" class="btn-check" name="color-theme-layout" id="Aqua_Theme" autocomplete="off" />
      <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
        onclick="handleColorTheme('Aqua_Theme')" for="Aqua_Theme" data-bs-toggle="tooltip" data-bs-placement="top"
        data-bs-title="AQUA_THEME">
        <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-2">
          <i class="ti ti-check text-white d-flex icon fs-5"></i>
        </div>
      </label>

      <input type="radio" class="btn-check" name="color-theme-layout" id="Purple_Theme" autocomplete="off" />
      <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
        onclick="handleColorTheme('Purple_Theme')" for="Purple_Theme" data-bs-toggle="tooltip" data-bs-placement="top"
        data-bs-title="PURPLE_THEME">
        <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-3">
          <i class="ti ti-check text-white d-flex icon fs-5"></i>
        </div>
      </label>

      <input type="radio" class="btn-check" name="color-theme-layout" id="green-theme-layout" autocomplete="off" />
      <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
        onclick="handleColorTheme('Green_Theme')" for="green-theme-layout" data-bs-toggle="tooltip"
        data-bs-placement="top" data-bs-title="GREEN_THEME">
        <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-4">
          <i class="ti ti-check text-white d-flex icon fs-5"></i>
        </div>
      </label>

      <input type="radio" class="btn-check" name="color-theme-layout" id="cyan-theme-layout" autocomplete="off" />
      <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
        onclick="handleColorTheme('Cyan_Theme')" for="cyan-theme-layout" data-bs-toggle="tooltip"
        data-bs-placement="top" data-bs-title="CYAN_THEME">
        <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-5">
          <i class="ti ti-check text-white d-flex icon fs-5"></i>
        </div>
      </label>

      <input type="radio" class="btn-check" name="color-theme-layout" id="orange-theme-layout" autocomplete="off" />
      <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
        onclick="handleColorTheme('Orange_Theme')" for="orange-theme-layout" data-bs-toggle="tooltip"
        data-bs-placement="top" data-bs-title="ORANGE_THEME">
        <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-6">
          <i class="ti ti-check text-white d-flex icon fs-5"></i>
        </div>
      </label>
    </div>

    <h6 class="mt-5 fw-semibold fs-4 mb-2">Layout Type</h6>
    <div class="d-flex flex-row gap-3 customizer-box" role="group">
      <div>
        <input type="radio" class="btn-check" name="page-layout" id="vertical-layout" autocomplete="off" />
        <label class="btn p-9 btn-outline-primary rounded-2" for="vertical-layout">
          <i class="icon ti ti-layout-sidebar-right fs-7 me-2"></i>Vertical
        </label>
      </div>
      <div>
        <input type="radio" class="btn-check" name="page-layout" id="horizontal-layout" autocomplete="off" />
        <label class="btn p-9 btn-outline-primary rounded-2" for="horizontal-layout">
          <i class="icon ti ti-layout-navbar fs-7 me-2"></i>Horizontal
        </label>
      </div>
    </div>

    <h6 class="mt-5 fw-semibold fs-4 mb-2">Container Option</h6>

    <div class="d-flex flex-row gap-3 customizer-box" role="group">
      <input type="radio" class="btn-check" name="layout" id="boxed-layout" autocomplete="off" />
      <label class="btn p-9 btn-outline-primary rounded-2" for="boxed-layout">
        <i class="icon ti ti-layout-distribute-vertical fs-7 me-2"></i>Boxed
      </label>

      <input type="radio" class="btn-check" name="layout" id="full-layout" autocomplete="off" />
      <label class="btn p-9 btn-outline-primary rounded-2" for="full-layout">
        <i class="icon ti ti-layout-distribute-horizontal fs-7 me-2"></i>Full
      </label>
    </div>

    <h6 class="fw-semibold fs-4 mb-2 mt-5">Sidebar Type</h6>
    <div class="d-flex flex-row gap-3 customizer-box" role="group">
      <a href="javascript:void(0)" class="fullsidebar">
        <input type="radio" class="btn-check" name="sidebar-type" id="full-sidebar" autocomplete="off" />
        <label class="btn p-9 btn-outline-primary rounded-2" for="full-sidebar">
          <i class="icon ti ti-layout-sidebar-right fs-7 me-2"></i>Full
        </label>
      </a>
      <div>
        <input type="radio" class="btn-check" name="sidebar-type" id="mini-sidebar" autocomplete="off" />
        <label class="btn p-9 btn-outline-primary rounded-2" for="mini-sidebar">
          <i class="icon ti ti-layout-sidebar fs-7 me-2"></i>Collapse
        </label>
      </div>
    </div>

    <h6 class="mt-5 fw-semibold fs-4 mb-2">Card With</h6>

    <div class="d-flex flex-row gap-3 customizer-box" role="group">
      <input type="radio" class="btn-check" name="card-layout" id="card-with-border" autocomplete="off" />
      <label class="btn p-9 btn-outline-primary rounded-2" for="card-with-border">
        <i class="icon ti ti-border-outer fs-7 me-2"></i>Border
      </label>

      <input type="radio" class="btn-check" name="card-layout" id="card-without-border" autocomplete="off" />
      <label class="btn p-9 btn-outline-primary rounded-2" for="card-without-border">
        <i class="icon ti ti-border-none fs-7 me-2"></i>Shadow
      </label>
    </div>
  </div>
</div>

<script>
  function handleColorTheme(e) {
    document.documentElement.setAttribute("data-color-theme", e);
  }
</script>

<!--  Search Bar -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <input type="search" class="form-control" placeholder="Search here" id="search" />
        <a href="javascript:void(0)" data-bs-dismiss="modal" class="lh-1">
          <i class="ti ti-x fs-5 ms-3"></i>
        </a>
      </div>
      <div class="modal-body message-body" data-simplebar="">
        <h5 class="mb-0 fs-5 p-1">Quick Page Links</h5>
        <ul class="list mb-0 py-2">
          <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
            <a href="javascript:void(0)">
              <span class="text-dark fw-semibold d-block">Analytics</span>
              <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard1</span>
            </a>
          </li>
          <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
            <a href="javascript:void(0)">
              <span class="text-dark fw-semibold d-block">eCommerce</span>
              <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard2</span>
            </a>
          </li>
          <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
            <a href="javascript:void(0)">
              <span class="text-dark fw-semibold d-block">CRM</span>
              <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard3</span>
            </a>
          </li>
          <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
            <a href="javascript:void(0)">
              <span class="text-dark fw-semibold d-block">Contacts</span>
              <span class="fs-2 d-block text-body-secondary">/apps/contacts</span>
            </a>
          </li>
          <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
            <a href="javascript:void(0)">
              <span class="text-dark fw-semibold d-block">Posts</span>
              <span class="fs-2 d-block text-body-secondary">/apps/blog/posts</span>
            </a>
          </li>
          <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
            <a href="javascript:void(0)">
              <span class="text-dark fw-semibold d-block">Detail</span>
              <span
                class="fs-2 d-block text-body-secondary">/apps/blog/detail/streaming-video-way-before-it-was-cool-go-dark-tomorrow</span>
            </a>
          </li>
          <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
            <a href="javascript:void(0)">
              <span class="text-dark fw-semibold d-block">Shop</span>
              <span class="fs-2 d-block text-body-secondary">/apps/ecommerce/shop</span>
            </a>
          </li>
          <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
            <a href="javascript:void(0)">
              <span class="text-dark fw-semibold d-block">Modern</span>
              <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard1</span>
            </a>
          </li>
          <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
            <a href="javascript:void(0)">
              <span class="text-dark fw-semibold d-block">Dashboard</span>
              <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard2</span>
            </a>
          </li>
          <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
            <a href="javascript:void(0)">
              <span class="text-dark fw-semibold d-block">Contacts</span>
              <span class="fs-2 d-block text-body-secondary">/apps/contacts</span>
            </a>
          </li>
          <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
            <a href="javascript:void(0)">
              <span class="text-dark fw-semibold d-block">Posts</span>
              <span class="fs-2 d-block text-body-secondary">/apps/blog/posts</span>
            </a>
          </li>
          <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
            <a href="javascript:void(0)">
              <span class="text-dark fw-semibold d-block">Detail</span>
              <span
                class="fs-2 d-block text-body-secondary">/apps/blog/detail/streaming-video-way-before-it-was-cool-go-dark-tomorrow</span>
            </a>
          </li>
          <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
            <a href="javascript:void(0)">
              <span class="text-dark fw-semibold d-block">Shop</span>
              <span class="fs-2 d-block text-body-secondary">/apps/ecommerce/shop</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>
<div class="dark-transparent sidebartoggler"></div>
<!-- Import Js Files -->
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/libs/simplebar/dist/simplebar.min.js"></script>
<script src="../assets/js/theme/app.init.js"></script>
<script src="../assets/js/theme/theme.js"></script>
<script src="../assets/js/theme/app.min.js"></script>
<script src="../assets/js/theme/sidebarmenu-default.js"></script>

<!-- solar icons -->
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
<script src="../assets/js/datatable/datatable-basic.init.js"></script>
<script src="../assets/js/apps/contact.js"></script>
</body>

</html>