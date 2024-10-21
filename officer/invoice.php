<?php
session_start();
include '../includes/db_connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['UserID'])) {
  // Redirect to login page if not authenticated
  header("Location: ../login/login.php");
  exit();
}

// Function to insert activity logs
function insert_activity_log($conn, $userID, $action)
{
  $current_timestamp = date("Y-m-d H:i:s"); // Current date and time

  // Prepare the INSERT statement
  $insert_sql = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, ?)";
  if ($insert_stmt = $conn->prepare($insert_sql)) {
    $insert_stmt->bind_param("iss", $userID, $action, $current_timestamp);
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
                SELECT tg.TransactionGroupID, tg.Date, tg.RateAmount, tg.TotalKGs, e.TotalExpense
                FROM transactiongroup tg
                JOIN expenses e ON tg.ExpenseID = e.ExpenseID
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
            $html .= '<td>' . number_format($row['TotalExpense'], 2) . '</td>';
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
        // Query to select transactiongroup records
        $query = "
                    SELECT tg.TransactionGroupID, tg.RateAmount, e.TotalExpense
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
        if (!$stmt) {
          throw new Exception('Failed to prepare invoice insertion statement.');
        }

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
          $updateStmt = $conn->prepare($updateQuery);
          if (!$updateStmt) {
            throw new Exception('Failed to prepare transactiongroup update statement.');
          }
          $updateStmt->bind_param('i', $billingInvoiceNo);
          $updateStmt->execute();
          $updateStmt->close();
        }

        // Commit the transaction
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
                  <input type="text" id="invoiceSearchBar" class="form-control" placeholder="Search..." onkeyup="filterInvoices()">
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

              <div class="table-responsive mt-3">
                <?php if ($invoicesResult->num_rows > 0): ?>
                  <table class="table table-striped table-bordered text-nowrap align-middle text-center" id="invoiceTable">
                    <thead>
                      <tr>
                        <th onclick="sortTable(0)">Invoice No</th>
                        <th onclick="sortTable(1)">Date Range</th>
                        <th onclick="sortTable(2)">Billed To</th>
                        <th onclick="sortTable(3)">Gross Amount</th>
                        <th onclick="sortTable(4)">Net Amount</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody id="invoiceTableBody">
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
                            <!-- Print PDF Button -->
                            <form action="print_invoice.php" method="post" target="_blank" style="display:inline;">
                              <input type="hidden" name="BillingInvoiceNo"
                                value="<?php echo htmlspecialchars($invoice['BillingInvoiceNo']); ?>">
                              <input type="hidden" name="format" value="pdf">
                              <button type="submit" class="btn btn-primary btn-sm">Print PDF</button>
                            </form>
                            <!-- Export Excel Button -->
                            <form action="print_invoice.php" method="post" target="_blank" style="display:inline;">
                              <input type="hidden" name="BillingInvoiceNo"
                                value="<?php echo htmlspecialchars($invoice['BillingInvoiceNo']); ?>">
                              <input type="hidden" name="format" value="excel">
                              <button type="submit" class="btn btn-success btn-sm">Export Excel</button>
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

              <div class="pagination-controls d-flex justify-content-end align-items-center mt-3">
                <button id="prevBtn" class="btn btn-primary me-2" onclick="prevPage()">Previous</button>
                <nav>
                  <ul class="pagination mb-0" id="paginationNumbers"></ul>
                </nav>
                <button id="nextBtn" class="btn btn-primary ms-2" onclick="nextPage()">Next</button>
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
  </div>
</div>
<?php
include '../officer/footer.php';
$conn->close();
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(document).ready(function() {
    $('#btn-add-invoice').click(function() {
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
        success: function(response) {
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
        error: function(xhr, status, error) {
          console.error(xhr.responseText);
          alert('An error occurred while fetching the records.');
        }
      });
    });

    // Handle invoice confirmation
    $('#confirmGenerateInvoice').click(function() {
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
        success: function(response) {
          if (response.success) {
            alert('Invoice generated successfully.');
            // Reload the page to show the new invoice
            location.reload();
          } else {
            alert(response.message);
          }
        },
        error: function(xhr, status, error) {
          console.error(xhr.responseText);
          alert('An error occurred while generating the invoice.');
        }
      });
    });
  });
</script>

<script>
  let currentPage = 1;
  let rowsPerPage = 5; // Default rows per page
  let totalRows = 0;
  let totalPages = 0;
  let allRows = []; // Array to store all rows initially
  let filteredRows = []; // Array to store filtered rows
  let currentSortColumn = -1; // Track currently sorted column
  let isAscending = true; // Track sort direction (ascending/descending)

  // Initialize the table and pagination on page load
  document.addEventListener('DOMContentLoaded', () => {
    initializeRows(); // Initialize all rows
    updateFilteredRows(); // Initialize filtered rows
    updateTable(); // Populate the table with rows
  });

  // Function to initialize all rows
  function initializeRows() {
    const table = document.getElementById("invoiceTableBody");
    allRows = Array.from(table.getElementsByTagName("tr")); // Store all rows initially
  }

  // Function to filter rows based on search input
  function filterInvoices() {
    const input = document.getElementById("invoiceSearchBar").value.toLowerCase();

    // Filter rows based on the search input
    filteredRows = allRows.filter(row => {
      const rowText = row.innerText.toLowerCase();
      return rowText.includes(input); // Keep rows that match the search query
    });

    currentPage = 1; // Reset to the first page after filtering
    updateTable(); // Update table with filtered results
  }

  // Sorting Function
  function sortTable(columnIndex) {
    const isInvoiceNumber = columnIndex === 0; // Specifically for "Invoice No" column
    const isNumeric = isInvoiceNumber || columnIndex === 3 || columnIndex === 4; // Numeric columns (Invoice No, Gross Amount, Net Amount)

    // Toggle sorting direction
    if (currentSortColumn === columnIndex) {
      isAscending = !isAscending;
    } else {
      currentSortColumn = columnIndex;
      isAscending = true; // Default to ascending order
    }

    filteredRows.sort((a, b) => {
      let aValue = a.getElementsByTagName("td")[columnIndex].innerText.trim();
      let bValue = b.getElementsByTagName("td")[columnIndex].innerText.trim();

      // For "Invoice No", make sure we are sorting as integers
      if (isInvoiceNumber) {
        aValue = parseInt(aValue) || 0; // Convert to integer
        bValue = parseInt(bValue) || 0; // Convert to integer
        return isAscending ? aValue - bValue : bValue - aValue;
      }

      // For other numeric columns
      if (isNumeric) {
        aValue = parseFloat(aValue.replace(/,/g, '')) || 0; // Handle commas for large numbers
        bValue = parseFloat(bValue.replace(/,/g, '')) || 0;
        return isAscending ? aValue - bValue : bValue - aValue;
      }

      // For string columns
      return isAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
    });

    currentPage = 1; // Reset to the first page after sorting
    updateTable(); // Update table with sorted results

    // Update the sorting icons
    updateSortingIcons(columnIndex);
  }

  // Function to update sorting icons (arrow indicators)
  function updateSortingIcons(columnIndex) {
    const headers = document.querySelectorAll("th");
    headers.forEach((header, index) => {
      header.classList.remove('ascending', 'descending');
      if (index === columnIndex) {
        header.classList.add(isAscending ? 'ascending' : 'descending');
      }
    });
  }

  // Update the table to show rows for the current page
  function updateTable() {
    const table = document.getElementById("invoiceTableBody");
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;

    // Clear the table and display rows for the current page
    table.innerHTML = "";
    filteredRows.slice(startIndex, endIndex).forEach(row => {
      table.appendChild(row);
    });

    totalRows = filteredRows.length;
    totalPages = Math.ceil(totalRows / rowsPerPage);

    // Disable/Enable next/previous buttons
    document.getElementById("prevBtn").disabled = currentPage === 1;
    document.getElementById("nextBtn").disabled = currentPage === totalPages || totalPages === 0;

    // Update pagination numbers
    updatePaginationNumbers();
  }

  // Function to update pagination numbers
  function updatePaginationNumbers() {
    const paginationNumbers = document.getElementById("paginationNumbers");
    paginationNumbers.innerHTML = ""; // Clear existing pagination numbers

    // Loop through the total number of pages and create page links
    for (let i = 1; i <= totalPages; i++) {
      const pageItem = document.createElement("li");
      pageItem.classList.add("page-item");
      if (i === currentPage) {
        pageItem.classList.add("active"); // Highlight current page
      }

      const pageLink = document.createElement("a");
      pageLink.classList.add("page-link");
      pageLink.textContent = i;
      pageLink.addEventListener('click', () => {
        currentPage = i; // Change to clicked page number
        updateTable();
      });

      pageItem.appendChild(pageLink);
      paginationNumbers.appendChild(pageItem);
    }
  }

  // Move to the next page
  function nextPage() {
    if (currentPage < totalPages) {
      currentPage++;
      updateTable();
    }
  }

  // Move to the previous page
  function prevPage() {
    if (currentPage > 1) {
      currentPage--;
      updateTable();
    }
  }

  // Change the number of rows per page
  function changeRowsPerPage() {
    rowsPerPage = parseInt(document.getElementById("rowsPerPage").value);
    currentPage = 1; // Reset to the first page
    updateTable();
  }

  // Helper function to update filtered rows based on search input
  function updateFilteredRows() {
    filteredRows = [...allRows]; // Initially, all rows are visible
  }
</script>



<!-- Add the following styles to support the sorting arrows -->
<style>
  th {
    cursor: pointer;
  }

  /* Adding arrow after the column header text */
  .ascending::after {
    content: ' ↑';
    /* Unicode for up arrow */
  }

  .descending::after {
    content: ' ↓';
    /* Unicode for down arrow */
  }
</style>