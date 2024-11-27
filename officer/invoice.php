<?php
$allowedRoles = ['SuperAdmin', 'Officer'];

// Include the authentication script
require_once '../includes/auth.php';
include '../includes/db_connection.php';


// Function to insert activity logs
function insert_activity_log($conn, $userID, $action)
{
  $current_timestamp = date("Y-m-d H:i:s"); // Current date and time

  // Prepare the INSERT statement
  $insert_sql = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, NOW())";
  if ($insert_stmt = $conn->prepare($insert_sql)) {
    $insert_stmt->bind_param("is", $userID, $action);
    if (!$insert_stmt->execute()) {
      // Handle insertion error (optional)
      error_log("Failed to insert activity log: " . $insert_stmt->error);
    }
    $insert_stmt->close();
  } else {
    // Handle preparation error (optional)
    error_log("Failed to prepare activity log insertion: " . $conn->error);
  }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Check if it's an AJAX request
  if (isset($_POST['action'])) {
    $userID = $_SESSION['UserID']; // Get the UserID from the session

    if ($_POST['action'] == 'fetch_records') {
      // Fetch selected records
      $billingStartDate = $_POST['billingStartDate'] ?? '';
      $billingEndDate = $_POST['billingEndDate'] ?? '';

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
                SELECT tg.TransactionGroupID, tg.Date, tg.RateAmount, tg.TotalKGs, tg.TollFeeAmount, tg.Amount
                FROM transactiongroup tg
                WHERE tg.Date BETWEEN ? AND ?
            ";

      $stmt = $conn->prepare($query);
      if ($stmt) {
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
            $html .= '<td>' . number_format($row['TollFeeAmount'], 2) . '</td>';
            $html .= '<td>' . number_format($row['Amount'], 2) . '</td>'; // Added Amount column
            $html .= '</tr>';
          }
          echo json_encode(['success' => true, 'html' => $html]);

          // Insert activity log for fetching records
          insert_activity_log($conn, $userID, 'Fetch Records');
        } else {
          echo json_encode(['success' => false, 'message' => 'No transactions found for the selected date range.']);
        }
        $stmt->close();
      } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare the records fetch statement.']);
        error_log("Failed to prepare fetch_records statement: " . $conn->error);
      }
      exit;
    } elseif ($_POST['action'] == 'generate_invoice') {
      // Generate invoice
      $billingStartDate = $_POST['billingStartDate'] ?? '';
      $billingEndDate = $_POST['billingEndDate'] ?? '';
      $billedTo = $_POST['billedTo'] ?? '';

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
        // **Step 1: Check for Overlapping Date Ranges**
        $overlapQuery = "
          SELECT COUNT(*) as overlap_count
          FROM invoices
          WHERE (BillingStartDate <= ?)
            AND (BillingEndDate >= ?)
        ";

        $overlapStmt = $conn->prepare($overlapQuery);
        if (!$overlapStmt) {
          throw new Exception('Failed to prepare overlap check statement: ' . $conn->error);
        }

        // Bind parameters: new BillingEndDate and new BillingStartDate
        $overlapStmt->bind_param('ss', $billingEndDate, $billingStartDate);
        $overlapStmt->execute();
        $overlapResult = $overlapStmt->get_result();
        $overlapRow = $overlapResult->fetch_assoc();
        $overlapCount = $overlapRow['overlap_count'];
        $overlapStmt->close();

        if ($overlapCount > 0) {
          throw new Exception('The selected date range overlaps with an existing invoice.');
        }

        // **Step 2: Fetch TransactionGroup Records**
        $query = "
          SELECT tg.TransactionGroupID, tg.RateAmount, tg.TollFeeAmount, tg.Amount
          FROM transactiongroup tg
          JOIN expenses e ON tg.ExpenseID = e.ExpenseID
          WHERE tg.Date BETWEEN ? AND ?
        ";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
          throw new Exception('Failed to prepare transaction selection statement.');
        }

        $stmt->bind_param('ss', $billingStartDate, $billingEndDate);
        $stmt->execute();
        $result = $stmt->get_result();

        // Initialize totals
        $grossAmount = 0;
        $amount = 0;
        $totalExpenses = 0;
        $transactionGroupIDs = array();

        while ($row = $result->fetch_assoc()) {
          $grossAmount += $row['RateAmount'];
          $amount += $row['Amount'];
          $totalExpenses += $row['TollFeeAmount'];
          $transactionGroupIDs[] = $row['TransactionGroupID'];
        }

        if (empty($transactionGroupIDs)) {
          throw new Exception('No transactions found for the selected date range.');
        }

        // Calculate amounts based on billedTo
        $ewt = 0;
        switch ($billedTo) {
          case 'BOUNTY AGRO VENTURES INC.':
          case 'BOUNTY FRESH FOOD INC.':
            $ewt = $grossAmount * 0.02;
            break;
          case 'CHOOKS TO GO INC.':
            $ewt = $amount * 0.02; // Using the correct variable 'amount'
            break;
        }

        $vat = $grossAmount * 0.12;
        $totalAmount = $grossAmount + $vat;
        $amountNetOfTax = $totalAmount - $ewt;
        $addTollCharges = $totalExpenses;
        $netAmount = $amountNetOfTax + $addTollCharges;

        // **Step 3: Handle ServiceNo**
        // Fetch the current maximum ServiceNo
        $serviceNoQuery = "SELECT MAX(ServiceNo) as MaxServiceNo FROM invoices";
        $serviceNoResult = $conn->query($serviceNoQuery);

        if ($serviceNoResult) {
          $serviceNoRow = $serviceNoResult->fetch_assoc();
          $maxServiceNo = $serviceNoRow['MaxServiceNo'];
          $newServiceNo = ($maxServiceNo !== null) ? $maxServiceNo + 1 : 1;
        } else {
          throw new Exception('Failed to fetch the current maximum ServiceNo.');
        }

        // **Step 4: Insert into 'invoices' table with ServiceNo**
        $invoiceQuery = "
          INSERT INTO invoices (
            ServiceNo, BillingStartDate, BillingEndDate, BilledTo,
            GrossAmount, TotalAmount, VAT, EWT, AddTollCharges,
            AmountNetOfTax, NetAmount
          )
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($invoiceQuery);
        if (!$stmt) {
          throw new Exception('Failed to prepare invoice insertion statement.');
        }

        $stmt->bind_param(
          'isssddddddd',
          $newServiceNo,
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
        if (!$stmt->execute()) {
          throw new Exception('Failed to insert into invoices table: ' . $stmt->error);
        }

        // Get the inserted BillingInvoiceNo
        $billingInvoiceNo = $stmt->insert_id;
        $stmt->close();

        // **Step 5: Update 'transactiongroup' Records**
        if (!empty($transactionGroupIDs)) {
          // Sanitize IDs and prepare the IN clause
          $ids = implode(',', array_map('intval', $transactionGroupIDs));
          $updateQuery = "UPDATE transactiongroup SET BillingInvoiceNo = ? WHERE TransactionGroupID IN ($ids)";
          $updateStmt = $conn->prepare($updateQuery);
          if (!$updateStmt) {
            throw new Exception('Failed to prepare transactiongroup update statement.');
          }
          $updateStmt->bind_param('i', $billingInvoiceNo);
          if (!$updateStmt->execute()) {
            throw new Exception('Failed to execute transactiongroup update: ' . $updateStmt->error);
          }
          $updateStmt->close();
        }

        // **Optional Step 6: Link Invoices and TransactionGroups**
        // If you have an 'invoices_transactions' table, you can insert records here.
        // Skipping this step as it's optional.

        // **Step 7: Commit the Transaction**
        $conn->commit();

        echo json_encode(['success' => true]);

        // Insert activity log for generating invoice
        insert_activity_log($conn, $userID, 'Generate Invoice');
      } catch (Exception $e) {
        // Rollback the transaction
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
      }
      exit;
    }
  }
}

// Rest of your PHP code to display the page
include '../officer/header.php';
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

              <div class="d-flex justify-content-between align-items-center m-4">
                <!-- Search Bar on the left -->
                <div class="col-md-4">
                  <input type="text" id="invoiceSearchBar" class="form-control" placeholder="Search..."
                    onkeyup="filterInvoices()">
                </div>

                <!-- Rows per page dropdown on the right -->
                <div class="col-md-4 text-end">
                  <select id="rowsPerPage" class="form-select w-auto d-inline m-1" onchange="changeRowsPerPage()">
                    <option value="5">5 rows</option>
                    <option value="10">10 rows</option>
                    <option value="20">20 rows</option>
                  </select>
                </div>
              </div>

              <?php
              // Fetch invoices from the 'invoices' table
              $invoicesQuery = "SELECT * FROM invoices ORDER BY BillingInvoiceNo DESC";
              $invoicesResult = $conn->query($invoicesQuery);
              ?>

              <div class="table-responsive mt-3 px-4">
                <?php if ($invoicesResult->num_rows > 0): ?>
                  <table class="table table-striped table-bordered text-nowrap align-middle text-center"
                    id="invoiceTable">
                    <thead>
                      <tr>
                        <th onclick="sortTable(0)">Invoice No</th>
                        <th onclick="sortTable(1)">Service No</th>
                        <th onclick="sortTable(2)">Date Range</th>
                        <th onclick="sortTable(3)">Billed To</th>
                        <th onclick="sortTable(4)">Gross Amount</th>
                        <th onclick="sortTable(5)">Net Amount</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody id="invoiceTableBody">
                      <?php while ($invoice = $invoicesResult->fetch_assoc()): ?>
                        <tr>
                          <td><?php echo 'SOA#' . htmlspecialchars($invoice['BillingInvoiceNo']) . '-E'; ?></td>
                          <td><?php echo htmlspecialchars($invoice['ServiceNo']); ?></td>
                          <td>
                            <?php echo htmlspecialchars($invoice['BillingStartDate']); ?> -
                            <?php echo htmlspecialchars($invoice['BillingEndDate']); ?>
                          </td>
                          <td><?php echo htmlspecialchars($invoice['BilledTo']); ?></td>
                          <td><?php echo number_format($invoice['GrossAmount'], 2); ?></td>
                          <td><?php echo number_format($invoice['NetAmount'], 2); ?></td>
                          <td>
                            <!-- Dropdown Button -->
                            <div class="dropdown">
                              <button class="btn btn-secondary btn-sm dropdown-toggle" type="button"
                                id="actionMenu<?php echo $invoice['BillingInvoiceNo']; ?>" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                Actions
                              </button>
                              <ul class="dropdown-menu"
                                aria-labelledby="actionMenu<?php echo $invoice['BillingInvoiceNo']; ?>">
                                <li>
                                  <!-- Print PDF Option -->
                                  <form action="print_invoice.php" method="post" target="_blank"
                                    class="dropdown-item p-0 m-0">
                                    <input type="hidden" name="BillingInvoiceNo"
                                      value="<?php echo htmlspecialchars($invoice['BillingInvoiceNo']); ?>">
                                    <input type="hidden" name="format" value="pdf">
                                    <button type="submit" class="btn btn-link dropdown-item"
                                      style="text-decoration: none;">Print PDF</button>
                                  </form>
                                </li>
                                <li>
                                  <!-- Export Excel Option -->
                                  <form action="print_invoice.php" method="post" target="_blank"
                                    class="dropdown-item p-0 m-0">
                                    <input type="hidden" name="BillingInvoiceNo"
                                      value="<?php echo htmlspecialchars($invoice['BillingInvoiceNo']); ?>">
                                    <input type="hidden" name="format" value="excel">
                                    <button type="submit" class="btn btn-link dropdown-item"
                                      style="text-decoration: none;">Export Excel</button>
                                  </form>
                                </li>
                                <li>
                                  <hr class="dropdown-divider">
                                </li>
                                <li>
                                  <!-- Edit Invoice Option -->
                                  <a href="edit_invoice.php?BillingInvoiceNo=<?php echo urlencode($invoice['BillingInvoiceNo']); ?>"
                                    class="dropdown-item">
                                    Edit Invoice
                                  </a>
                                </li>
                              </ul>
                            </div>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                <?php else: ?>
                  <p>No invoices found.</p>
                <?php endif; ?>
              </div>

              <div
                class="pagination-controls d-flex justify-content-between align-items-center my-3 flex-column flex-md-row p-3">
                <div class="order-2 order-md-1 mt-3 mt-md-0">
                  Number of pages: <span id="totalPages"></span>
                </div>
                <nav aria-label="Page navigation" class="order-1 order-md-2 w-100">
                  <ul class="pagination justify-content-center justify-content-md-end mb-0" id="paginationNumbers">
                    <!-- Pagination buttons will be dynamically generated here -->
                  </ul>
                </nav>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Invoice Modal -->
        <div class="modal fade" id="addInvoiceModal" tabindex="-1" aria-labelledby="addInvoiceModalTitle"
          aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-sm" role="document"> <!-- Corrected class -->
            <div class="modal-content">
              <div class="modal-header d-flex align-items-center bg-primary">
                <h5 class="modal-title text-white fs-4">Add Invoice Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <!-- Close Button -->
              </div>
              <div class="modal-body">
                <!-- Modal Body Content -->
                <form id="addInvoiceForm">
                  <!-- Form Fields -->
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
                      <?php
                      // Fetch clients from the database
                      $clientQuery = "SELECT clientID, clientName FROM client";
                      $clientResult = $conn->query($clientQuery);
                      if ($clientResult && $clientResult->num_rows > 0) {
                        while ($client = $clientResult->fetch_assoc()) {
                          echo '<option value="' . htmlspecialchars($client['clientName']) . '">' . htmlspecialchars($client['clientName']) . '</option>';
                        }
                      }
                      ?>
                    </select>
                  </div>
                  <div class="col-12 mb-3">
                    <div class="d-flex gap-6 m-0 justify-content-end">
                      <button type="button" class="btn bg-danger-subtle text-danger"
                        data-bs-dismiss="modal">Discard</button> <!-- Discard Button -->
                      <button id="btn-add-invoice" class="btn btn-primary" type="button">Generate Invoice</button>
                      <!-- Generate Invoice Button -->
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- Selected Records Modal -->
        <div class="modal fade" id="selectedRecordsModal" tabindex="-1" aria-labelledby="selectedRecordsModalLabel"
          aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width: 70%;">
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
                        <th>Amount</th> <!-- Added Amount column -->
                      </tr>
                    </thead>
                    <tbody>
                      <!-- Data will be populated via AJAX -->
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="modal-footer ">
                <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmGenerateInvoice">Confirm and Generate
                  Invoice</button>
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
<!-- Bootstrap Bundle JS (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+ADa0j5j2zYzMEaXkvoE3kR18j4" crossorigin="anonymous"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
          billingEndDate: billingEndDate
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

<script>
  let currentPage = 1;
  let rowsPerPage = 5;
  let totalRows = 0;
  let totalPages = 0;
  let allRows = [];
  let filteredRows = [];
  let currentSortColumn = -1; // Track the current column for sorting
  let isAscending = true; // Track sort direction

  // Initialize rows and set event listeners after the DOM is loaded
  document.addEventListener('DOMContentLoaded', () => {
    initializeRows();
    updateTable();
    updatePaginationNumbers();
    setEventListeners();
  });

  // Function to set event listeners for search and rows per page
  function setEventListeners() {
    document.getElementById("rowsPerPage").addEventListener('change', changeRowsPerPage);
    document.getElementById("invoiceSearchBar").addEventListener('input', filterInvoices);
  }

  function initializeRows() {
    const tableBody = document.getElementById("invoiceTableBody");
    if (!tableBody) {
      console.error("Table body element with ID 'invoiceTableBody' not found.");
      return;
    }
    allRows = Array.from(tableBody.getElementsByTagName("tr"));
    filteredRows = [...allRows];
    totalRows = filteredRows.length;
    totalPages = Math.ceil(totalRows / rowsPerPage);
  }

  function updateTable() {
    const tableBody = document.getElementById("invoiceTableBody");
    if (!tableBody) {
      console.error("Table body element with ID 'invoiceTableBody' not found.");
      return;
    }
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;

    tableBody.innerHTML = "";
    filteredRows.slice(startIndex, endIndex).forEach(row => {
      tableBody.appendChild(row);
    });

    totalPages = Math.ceil(filteredRows.length / rowsPerPage);
    document.getElementById("totalPages").textContent = totalPages;
    updatePaginationNumbers();
  }

  function updatePaginationNumbers() {
    const paginationNumbers = document.getElementById("paginationNumbers");
    if (!paginationNumbers) {
      console.error("Pagination element with ID 'paginationNumbers' not found.");
      return;
    }
    paginationNumbers.innerHTML = "";

    const isMobile = window.innerWidth <= 768; // Check if it's mobile view
    const maxVisiblePages = isMobile ? 3 : 5; // Show 3 pages on mobile, 5 on desktop
    const halfVisible = Math.floor(maxVisiblePages / 2);
    let startPage, endPage;

    if (totalPages <= maxVisiblePages) {
      startPage = 1;
      endPage = totalPages;
    } else if (currentPage <= halfVisible) {
      startPage = 1;
      endPage = maxVisiblePages;
    } else if (currentPage + halfVisible >= totalPages) {
      startPage = totalPages - maxVisiblePages + 1;
      endPage = totalPages;
    } else {
      startPage = currentPage - halfVisible;
      endPage = currentPage + halfVisible;
    }

    paginationNumbers.appendChild(createPaginationItem('«', currentPage === 1, () => {
      currentPage = 1;
      updateTable();
    }));
    paginationNumbers.appendChild(createPaginationItem('‹', currentPage === 1, prevPage));

    for (let i = startPage; i <= endPage; i++) {
      const pageItem = document.createElement("li");
      pageItem.classList.add("page-item");
      if (i === currentPage) {
        pageItem.classList.add("active");
      }

      const pageLink = document.createElement("button");
      pageLink.classList.add("page-link");
      pageLink.textContent = i;
      pageLink.onclick = () => {
        currentPage = i;
        updateTable();
      };

      pageItem.appendChild(pageLink);
      paginationNumbers.appendChild(pageItem);
    }

    paginationNumbers.appendChild(createPaginationItem('›', currentPage === totalPages, nextPage));
    paginationNumbers.appendChild(createPaginationItem('»', currentPage === totalPages, () => {
      currentPage = totalPages;
      updateTable();
    }));
  }

  function createPaginationItem(label, isDisabled, onClick) {
    const pageItem = document.createElement("li");
    pageItem.classList.add("page-item");
    if (isDisabled) pageItem.classList.add("disabled");

    const pageLink = document.createElement("button");
    pageLink.classList.add("page-link");
    pageLink.textContent = label;

    if (onClick) pageLink.onclick = onClick;

    pageItem.appendChild(pageLink);
    return pageItem;
  }

  function nextPage() {
    if (currentPage < totalPages) {
      currentPage++;
      updateTable();
    }
  }

  function prevPage() {
    if (currentPage > 1) {
      currentPage--;
      updateTable();
    }
  }

  function changeRowsPerPage() {
    rowsPerPage = parseInt(document.getElementById("rowsPerPage").value);
    currentPage = 1;
    updateTable();
  }

  function filterInvoices() {
    const searchValue = document.getElementById("invoiceSearchBar").value.toLowerCase();
    filteredRows = allRows.filter(row => row.innerText.toLowerCase().includes(searchValue));
    currentPage = 1;
    updateTable();
  }

  // Sorting function
  function sortTable(columnIndex) {
    isAscending = currentSortColumn === columnIndex ? !isAscending : true;
    currentSortColumn = columnIndex;

    filteredRows.sort((a, b) => {
      let aValue = a.getElementsByTagName("td")[columnIndex].innerText.trim();
      let bValue = b.getElementsByTagName("td")[columnIndex].innerText.trim();

      if (columnIndex === 4 || columnIndex === 5) { // Adjust column index as needed for numeric columns
        aValue = parseFloat(aValue.replace(/[^0-9.-]+/g, '')) || 0;
        bValue = parseFloat(bValue.replace(/[^0-9.-]+/g, '')) || 0;
      }

      return isNaN(aValue) || isNaN(bValue) ?
        isAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue) :
        isAscending ? aValue - bValue : bValue - aValue;
    });

    currentPage = 1;
    updateTable();
    updateSortIcons(columnIndex);
  }

  function updateSortIcons(columnIndex) {
    const headers = document.querySelectorAll("th");
    headers.forEach((header, index) => {
      header.classList.remove('ascending', 'descending');
      if (index === columnIndex) {
        header.classList.add(isAscending ? 'ascending' : 'descending');
      }
    });
  }
</script>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const theme = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-bs-theme", theme);
    document.body.classList.toggle("dark-mode", theme === "dark");

    document.querySelectorAll(".dark-layout").forEach((element) => {
      element.addEventListener("click", () => {
        localStorage.setItem("theme", "dark");
        document.documentElement.setAttribute("data-bs-theme", "dark");
        document.body.classList.add("dark-mode");
      });
    });

    document.querySelectorAll(".light-layout").forEach((element) => {
      element.addEventListener("click", () => {
        localStorage.setItem("theme", "light");
        document.documentElement.setAttribute("data-bs-theme", "light");
        document.body.classList.remove("dark-mode");
      });
    });
  });
</script>

<!-- Add the following styles to support the sorting arrows -->
<style>
  .dark-mode .pagination .page-item .page-link {
    /* Dark background for pagination items */
    color: #fff;
    /* Light text for readability */
  }

  .dark-mode .pagination .page-item.active .page-link {
    background-color: #0d6efd;
    /* Highlight color for active page */
    color: #fff;
  }

  .dark-mode .pagination .page-link:hover {
    background-color: #555;
    /* Slightly lighter on hover */
  }

  th {
    cursor: pointer;
  }

  .ascending::after {
    content: ' ↑';
  }

  .descending::after {
    content: ' ↓';
  }

  .pagination .page-item .page-link {
    min-width: 35px;
    height: 35px;
    display: flex;
    justify-content: center;
    align-items: center;
    border: none;
    color: #000;
    margin: 0 2px;
  }

  .pagination .page-item.active .page-link {
    background-color: #0d6efd;
    color: #fff;
    border-radius: 50%;
    border: none;
  }

  .pagination .page-link:hover {
    background-color: #e9ecef;
  }
</style>