<?php
session_start();

// Apply cache-control headers
header("Cache-Control: public, max-age=3600"); // Cache for 1 hour
header("Expires: " . gmdate("D, d M Y H:i:s", time() + 3600) . " GMT"); // Expiry time
header("Pragma: cache"); // HTTP 1.0 compatibility

include '../officer/header.php';
include '../includes/db_connection.php';
?>

<div class="body-wrapper">
  <div class="container-fluid">
    <div class="card card-body py-3">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="d-sm-flex align-items-center justify-space-between">
            <h4 class="mb-4 mb-sm-0 card-title">Records</h4>
            <nav aria-label="breadcrumb" class="ms-auto">
              <ol class="breadcrumb">
                <li class="breadcrumb-item d-flex align-items-center">
                  <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                  </a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                  <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                    Finance
                  </span>
                </li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>
    <?php
    // Include the database connection


    // Get the last ExpenseID
    $query = "SELECT ExpenseID FROM expenses ORDER BY ExpenseID DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $nextExpenseId = isset($row['ExpenseID']) ? $row['ExpenseID'] + 1 : 1;
    ?>
    <div class="widget-content searchable-container list">
      <h5 class="border-bottom py-2 px-4 mb-4">Finances</h5>
      <!-- Add Expense Modal -->


      <script>
        // Function to calculate and display fuel amount
        function calculateFuelAmount() {
          const liters = parseFloat(document.getElementById('liters').value) || 0;
          const unitPrice = parseFloat(document.getElementById('unitPrice').value) || 0;
          const fuelAmount = (liters * unitPrice).toFixed(2); // Calculating and keeping two decimal places
          document.getElementById('fuelAmount').value = fuelAmount; // Display in the Fuel Amount field
        }

        // Attach event listeners to the 'liters' and 'unitPrice' inputs to trigger calculation
        document.getElementById('liters').addEventListener('input', calculateFuelAmount);
        document.getElementById('unitPrice').addEventListener('input', calculateFuelAmount);
      </script>


      <!-- Edit Fuel Expenses -->
      <div class="modal fade" id="editFuelExpenseModal" tabindex="-1" role="dialog"
        aria-labelledby="editFuelExpenseModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header d-flex align-items-center bg-primary">
              <h5 class="modal-title text-white fs-4">Edit Expense</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-12">
                  <div class="card w-100 border position-relative overflow-hidden mb-0">
                    <div class="card-body p-4">
                      <h4 class="card-title">Edit Fuel Expense</h4>
                      <p class="card-subtitle mb-4">Fill out the form to update an expense.</p>
                      <form id="updateExpenseForm" method="POST" action="update_expense.php">
                        <div class="row">
                          <!-- ExpenseID -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateExpenseID" class="form-label">Expense ID</label>
                              <input type="text" class="form-control" id="updateExpenseID" name="updateExpenseID"
                                readonly>
                            </div>
                          </div>
                          <!-- Date -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateDate" class="form-label">Date</label>
                              <input type="date" class="form-control" id="updateDate" name="updateDate">
                            </div>
                          </div>

                          <!-- Salary Amount -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateSalaryAmount" class="form-label">Salary Amount</label>
                              <input type="number" class="form-control" id="updateSalaryAmount"
                                name="updateSalaryAmount" step="0.01" oninput="computeTotalExpense()">
                            </div>
                          </div>
                          <!-- Mobile -->
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="updateMobile" class="form-label">Mobile Amount</label>
                              <input type="number" class="form-control" id="updateMobileAmount"
                                name="updateMobileAmount" step="0.01" oninput="computeTotalExpense()">
                            </div>
                          </div>
                          <!-- Other Amount -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="updateOtherAmount" class="form-label">Other Amount</label>
                              <input type="number" class="form-control" id="updateOtherAmount" name="updateOtherAmount"
                                step="0.01" oninput="computeTotalExpense()">
                            </div>
                          </div>

                          <!-- Total Amount -->
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="updateTotalExpense" class="form-label">Total Expense</label>
                              <input type="number" class="form-control" id="updateTotalExpense"
                                name="updateTotalExpense" step="0.01" readonly>
                            </div>
                          </div>

                          <div class="col-12">
                            <div class="d-flex align-items-center justify-content-end mt-4 gap-6">
                              <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-primary">Save</button>
                            </div>
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
    </div>
    <div class="card">
      <div class="card-body p-0">
        <div class>
          <!-- Nav tabs -->
          <ul class="nav nav-tabs p-4 border-bottom" role="tablist">
            <li class="nav-item">
              <a class="nav-link active me-3" data-bs-toggle="tab" href="#home" role="tab">
                <span>Expenses</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="tab" href="#profile" role="tab">
                <span>Fuel</span>
              </a>
            </li>
          </ul>
          <!-- Tab panes -->
          <div class="tab-content p-4">
            <div class="tab-pane active" id="home" role="tabpanel">


              <!-- Expense Table -->
              <div class="table-controls mb-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div class="col-md-4">
                    <input type="text" id="searchBar" class="form-control" placeholder="Search..." />
                  </div>
                  <div class="col-md-4 text-end">
                    <select id="rowsPerPage" class="form-select w-auto d-inline">
                      <option value="5">5 rows</option>
                      <option value="10">10 rows</option>
                      <option value="20">20 rows</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="py-3 pb-0">
                <div class="table-responsive">
                  <table id="expenseTable" class="table text-center table-striped table-bordered">
                    <thead>
                      <tr>
                        <th onclick="sortTable(0)">Expense ID</th>
                        <th onclick="sortTable(1)">Date</th>
                        <th onclick="sortTable(2)">SalaryAmount</th>
                        <th onclick="sortTable(3)">MobileAmount</th>
                        <th onclick="sortTable(4)">OtherAmount</th>
                        <th onclick="sortTable(5)">TotalExpense</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                  </table>
                </div>
                <div
                  class="pagination-controls d-flex justify-content-between align-items-center mt-3 flex-column flex-md-row">
                  <div class="order-2 order-md-1 mt-3 mt-md-0">
                    Number of pages: <span id="totalPages"></span>
                  </div>
                  <nav aria-label="Page navigation" class="order-1 order-md-2 w-100">
                    <ul class="pagination justify-content-center justify-content-md-end mb-0" id="paginationNumbers">
                      <!-- Pagination buttons will be dynamically generated here -->
                    </ul>
                  </nav>
                </div>


                <script>
                  let currentPage = 1;
                  let rowsPerPage = 5;
                  let totalRows = 0;
                  let totalPages = 0;
                  let filteredRows = [];
                  let allRows = [];

                  document.addEventListener('DOMContentLoaded', () => {
                    initializeTable();
                  });

                  function initializeTable() {
                    const tableBody = document.getElementById("tableBody");
                    if (!tableBody) {
                      console.error("Table body not found");
                      return;
                    }
                    allRows = Array.from(tableBody.getElementsByTagName("tr"));
                    filteredRows = [...allRows]; // Initialize with all rows
                    updateTotalRowsAndPages();
                    updateTable();
                  }

                  function changeRowsPerPage() {
                    rowsPerPage = parseInt(document.getElementById("rowsPerPage").value);
                    currentPage = 1;
                    updateTotalRowsAndPages();
                    updateTable();
                  }

                  function filterTable() {
                    const input = document.getElementById("searchBar").value.toLowerCase();
                    filteredRows = allRows.filter(row => row.innerText.toLowerCase().includes(input));
                    updateTotalRowsAndPages();

                    // Show or hide "No data found" row
                    const noDataRow = document.getElementById("noDataRow");
                    if (filteredRows.length === 0 && noDataRow) {
                      noDataRow.style.display = "";
                    } else if (noDataRow) {
                      noDataRow.style.display = "none";
                    }

                    currentPage = 1;
                    updateTable();
                  }

                  function updateTotalRowsAndPages() {
                    totalRows = filteredRows.length;
                    totalPages = Math.ceil(totalRows / rowsPerPage) || 1;
                  }

                  function updateTable() {
                    const startIndex = (currentPage - 1) * rowsPerPage;
                    const endIndex = startIndex + rowsPerPage;

                    allRows.forEach(row => row.style.display = "none"); // Hide all rows initially
                    filteredRows.forEach((row, index) => {
                      if (index >= startIndex && index < endIndex) {
                        row.style.display = ""; // Display rows within the current page range
                      }
                    });

                    document.getElementById("totalPages").textContent = totalPages;
                    updatePaginationNumbers();
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

                  function updatePaginationNumbers() {
                    const paginationNumbers = document.getElementById("paginationNumbers");
                    if (!paginationNumbers) {
                      console.error("Pagination container not found");
                      return;
                    }
                    paginationNumbers.innerHTML = '';

                    const maxVisiblePages = window.innerWidth <= 768 ? 3 : 5;
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

                    if (!isDisabled) pageLink.onclick = onClick;

                    pageItem.appendChild(pageLink);
                    return pageItem;
                  }
                </script>

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
                  }

                  .pagination .page-item.disabled .page-link {
                    opacity: 0.5;
                    cursor: not-allowed;
                  }

                  .pagination .page-link:hover {
                    background-color: #e9ecef;
                  }
                </style>


              </div>
            </div>
            <div class="tab-pane" id="profile" role="tabpanel">


              <div class="table-controls mb-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div class="col-md-4">
                    <input type="text" id="fuelSearchBar" class="form-control" placeholder="Search..."
                      onkeyup="filterFuelTable()">
                  </div>
                  <div class="col-md-4 text-end">
                    <select id="fuelRowsPerPage" class="form-select w-auto d-inline" onchange="changeFuelRowsPerPage()">
                      <option value="5">5 rows</option>
                      <option value="10">10 rows</option>
                      <option value="20">20 rows</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="py-3 pb-0 mb-0">
                <div class="table-responsive">
                  <table id="fuelTable" class="table text-center table-striped table-bordered">
                    <thead>
                      <tr>
                        <th onclick="sortFuelTable(0)">Fuel ID</th>
                        <th onclick="sortFuelTable(1)">Date</th>
                        <th onclick="sortFuelTable(2)">Liters</th>
                        <th onclick="sortFuelTable(3)">Fuel Price</th>
                        <th onclick="sortFuelTable(4)">Fuel Type</th>
                        <th onclick="sortFuelTable(5)">Amount</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody id="fuelTableBody">
                      <?php
                      include '../includes/db_connection.php';

                      // Fetch data from the fuel table
                      $sql = "SELECT FuelID, Date, Liters, UnitPrice, FuelType, Amount FROM fuel
                      ORDER BY FuelID DESC";
                      $result = $conn->query($sql);

                      if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                          $fuelData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');

                          echo "<tr>";
                          echo "<td>" . $row['FuelID'] . "</td>";
                          echo "<td>" . $row['Date'] . "</td>";
                          echo "<td>" . $row['Liters'] . "</td>";
                          echo "<td>" . $row['UnitPrice'] . "</td>";
                          echo "<td>" . $row['FuelType'] . "</td>";
                          echo "<td>" . $row['Amount'] . "</td>";
                          echo "<td>";
                          echo "<a href='#' class='me-3 text-primary' data-bs-toggle='modal' data-bs-target='#editFuelModal' onclick='populateEditForm(" . $fuelData . ");'><i class='fs-4 ti ti-edit'></i></a>";
                          echo "</td>";
                          echo "</tr>";
                        }
                      } else {
                        echo "<tr><td colspan='7'>No records found</td></tr>";
                      }
                      $conn->close();
                      ?>
                    </tbody>
                  </table>
                </div>


              </div>


              <!-- Edit Fuel Modal -->
              <div class="modal fade" id="editFuelModal" tabindex="-1" role="dialog"
                aria-labelledby="editFuelModalTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header d-flex align-items-center bg-primary">
                      <h5 class="modal-title text-white fs-4">Edit Fuel Record</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="row">
                        <div class="col-12">
                          <div class="card w-100 border position-relative overflow-hidden mb-0">
                            <div class="card-body p-4">
                              <h4 class="card-title">Edit Fuel Record</h4>
                              <form id="updateFuelForm" method="POST" action="update_fuel.php">
                                <div class="row">
                                  <!-- Fuel ID -->
                                  <div class="col-lg-4">
                                    <div class="mb-3">
                                      <label for="updateFuelID" class="form-label">Fuel ID</label>
                                      <input type="text" class="form-control" id="updateFuelID" name="updateFuelID"
                                        readonly>
                                    </div>
                                  </div>
                                  <!-- Date -->
                                  <div class="col-lg-4">
                                    <div class="mb-3">
                                      <label for="updateFuelDate" class="form-label">Date</label>
                                      <input type="date" class="form-control" id="updateFuelDate" name="updateFuelDate" required>
                                    </div>
                                  </div>
                                  <!-- Liters -->
                                  <div class="col-lg-4">
                                    <div class="mb-3">
                                      <label for="updateLiters" class="form-label">Liters</label>
                                      <input type="number" class="form-control" id="updateLiters" name="updateLiters"
                                        step="0.01" oninput="computeFuelAmount()">
                                    </div>
                                  </div>
                                  <!-- Unit Price -->
                                  <div class="col-lg-4">
                                    <div class="mb-3">
                                      <label for="updateUnitPrice" class="form-label">Fuel Price</label>
                                      <input type="number" class="form-control" id="updateUnitPrice"
                                        name="updateUnitPrice" step="0.01" oninput="computeFuelAmount()">
                                    </div>
                                  </div>
                                  <!-- Fuel Type -->
                                  <div class="col-lg-4">
                                    <div class="mb-3">
                                      <label for="updateFuelType" class="form-label">Fuel Type</label>
                                      <select class="form-select" id="updateFuelType" name="updateFuelType">
                                        <option value="Diesel">Diesel</option>
                                        <option value="Gasoline">Gasoline</option>
                                      </select>
                                    </div>
                                  </div>
                                  <!-- Amount -->
                                  <div class="col-lg-6">
                                    <div class="mb-3">
                                      <label for="updateAmount" class="form-label">Amount</label>
                                      <input type="number" class="form-control" id="updateAmount" name="updateAmount"
                                        step="0.01" readonly>
                                    </div>
                                  </div>
                                  <div class="col-12">
                                    <div class="d-flex align-items-center justify-content-end mt-4 gap-6">
                                      <button class="btn bg-danger-subtle text-danger"
                                        data-bs-dismiss="modal">Cancel</button>
                                      <button type="submit" class="btn btn-primary">Save</button>
                                    </div>
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

              <div
                class="pagination-controls d-flex justify-content-between align-items-center mt-3 flex-column flex-md-row">
                <div class="order-2 order-md-1 mt-3 mt-md-0">
                  Number of pages: <span id="totalFuelPages"></span>
                </div>
                <nav aria-label="Page navigation" class="order-1 order-md-2 w-100">
                  <ul class="pagination justify-content-center justify-content-md-end mb-0" id="fuelPaginationNumbers">
                    <!-- Pagination buttons will be dynamically generated here -->
                  </ul>
                </nav>
              </div>



              <script>
                let fuelCurrentPage = 1;
                let fuelRowsPerPage = 5;
                let totalFuelRows = 0;
                let totalFuelPages = 0;
                let allFuelRows = [];
                let filteredFuelRows = [];

                document.addEventListener('DOMContentLoaded', () => {
                  initializeFuelTable();
                });

                function initializeFuelTable() {
                  const fuelTableBody = document.getElementById("fuelTableBody");
                  if (!fuelTableBody) {
                    console.error("Fuel table body not found");
                    return;
                  }
                  allFuelRows = Array.from(fuelTableBody.getElementsByTagName("tr"));
                  filteredFuelRows = [...allFuelRows]; // Start with all rows as filtered
                  updateTotalFuelRowsAndPages();
                  updateFuelTable();
                }

                function changeFuelRowsPerPage() {
                  fuelRowsPerPage = parseInt(document.getElementById("fuelRowsPerPage").value);
                  fuelCurrentPage = 1;
                  updateTotalFuelRowsAndPages();
                  updateFuelTable();
                }

                function filterFuelTable() {
                  const input = document.getElementById("fuelSearchBar").value.toLowerCase();
                  filteredFuelRows = allFuelRows.filter(row => row.innerText.toLowerCase().includes(input));

                  // Show or hide "No data found" row
                  const noFuelDataRow = document.getElementById("noFuelDataRow");
                  if (filteredFuelRows.length === 0 && noFuelDataRow) {
                    noFuelDataRow.style.display = "";
                  } else if (noFuelDataRow) {
                    noFuelDataRow.style.display = "none";
                  }

                  fuelCurrentPage = 1; // Reset to the first page after filtering
                  updateTotalFuelRowsAndPages();
                  updateFuelTable();
                }

                function updateTotalFuelRowsAndPages() {
                  totalFuelRows = filteredFuelRows.length;
                  totalFuelPages = Math.ceil(totalFuelRows / fuelRowsPerPage) || 1;
                }

                function updateFuelTable() {
                  const startIndex = (fuelCurrentPage - 1) * fuelRowsPerPage;
                  const endIndex = startIndex + fuelRowsPerPage;

                  allFuelRows.forEach(row => row.style.display = "none"); // Hide all rows initially
                  filteredFuelRows.slice(startIndex, endIndex).forEach(row => {
                    row.style.display = ""; // Display rows within the current page range
                  });

                  document.getElementById("totalFuelPages").textContent = totalFuelPages;
                  updateFuelPaginationNumbers();
                }

                function nextFuelPage() {
                  if (fuelCurrentPage < totalFuelPages) {
                    fuelCurrentPage++;
                    updateFuelTable();
                  }
                }

                function prevFuelPage() {
                  if (fuelCurrentPage > 1) {
                    fuelCurrentPage--;
                    updateFuelTable();
                  }
                }

                function updateFuelPaginationNumbers() {
                  const paginationNumbers = document.getElementById("fuelPaginationNumbers");
                  if (!paginationNumbers) {
                    console.error("Pagination container not found");
                    return;
                  }
                  paginationNumbers.innerHTML = ''; // Clear existing pagination numbers

                  const maxVisiblePages = window.innerWidth <= 768 ? 3 : 5;
                  const halfVisible = Math.floor(maxVisiblePages / 2);
                  let startPage, endPage;

                  if (totalFuelPages <= maxVisiblePages) {
                    startPage = 1;
                    endPage = totalFuelPages;
                  } else if (fuelCurrentPage <= halfVisible) {
                    startPage = 1;
                    endPage = maxVisiblePages;
                  } else if (fuelCurrentPage + halfVisible >= totalFuelPages) {
                    startPage = totalFuelPages - maxVisiblePages + 1;
                    endPage = totalFuelPages;
                  } else {
                    startPage = fuelCurrentPage - halfVisible;
                    endPage = fuelCurrentPage + halfVisible;
                  }

                  paginationNumbers.appendChild(createFuelPaginationItem('«', fuelCurrentPage === 1, () => {
                    fuelCurrentPage = 1;
                    updateFuelTable();
                  }));

                  paginationNumbers.appendChild(createFuelPaginationItem('‹', fuelCurrentPage === 1, prevFuelPage));

                  for (let i = startPage; i <= endPage; i++) {
                    const pageItem = document.createElement("li");
                    pageItem.classList.add("page-item");
                    if (i === fuelCurrentPage) {
                      pageItem.classList.add("active");
                    }

                    const pageLink = document.createElement("button");
                    pageLink.classList.add("page-link");
                    pageLink.textContent = i;
                    pageLink.onclick = () => {
                      fuelCurrentPage = i;
                      updateFuelTable();
                    };

                    pageItem.appendChild(pageLink);
                    paginationNumbers.appendChild(pageItem);
                  }

                  paginationNumbers.appendChild(createFuelPaginationItem('›', fuelCurrentPage === totalFuelPages, nextFuelPage));

                  paginationNumbers.appendChild(createFuelPaginationItem('»', fuelCurrentPage === totalFuelPages, () => {
                    fuelCurrentPage = totalFuelPages;
                    updateFuelTable();
                  }));
                }

                function createFuelPaginationItem(label, isDisabled, onClick) {
                  const pageItem = document.createElement("li");
                  pageItem.classList.add("page-item");
                  if (isDisabled) pageItem.classList.add("disabled");

                  const pageLink = document.createElement("button");
                  pageLink.classList.add("page-link");
                  pageLink.textContent = label;

                  if (!isDisabled) pageLink.onclick = onClick;

                  pageItem.appendChild(pageLink);
                  return pageItem;
                }
              </script>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


  <script>
    document.getElementById("nextExpenseID").value = "<?php echo $nextExpenseId; ?>";

    function populateExpenseEditForm(expense) {
      // Populate the modal fields with the data from the selected row
      document.getElementById("updateExpenseID").value = expense.ExpenseID || "";
      document.getElementById("updateDate").value = expense.Date || "";
      document.getElementById("updateSalaryAmount").value = expense.SalaryAmount || 0;
      document.getElementById("updateMobileAmount").value = expense.MobileAmount || 0;
      document.getElementById("updateOtherAmount").value = expense.OtherAmount || 0;
      document.getElementById("updateTotalExpense").value = expense.TotalExpense || 0;
    }


    function computeTotalExpense() {
      // Get the values from the input fields
      const salaryAmount = parseFloat(document.getElementById("updateSalaryAmount").value) || 0;
      const mobileAmount = parseFloat(document.getElementById("updateMobileAmount").value) || 0;
      const otherAmount = parseFloat(document.getElementById("updateOtherAmount").value) || 0;

      // Calculate the total amount
      const totalExpense = salaryAmount + mobileAmount + otherAmount;

      // Set the total amount in the totalAmount input field
      document.getElementById("updateTotalExpense").value = totalExpense.toFixed(2); // Rounds to 2 decimal places
    }

    function populateEditForm(fuel) {
      document.getElementById("updateFuelID").value = fuel.FuelID;
      document.getElementById("updateFuelDate").value = fuel.Date;
      document.getElementById("updateLiters").value = fuel.Liters;
      document.getElementById("updateUnitPrice").value = fuel.UnitPrice;
      document.getElementById("updateFuelType").value = fuel.FuelType;
      document.getElementById("updateAmount").value = fuel.Amount;
    }

    function computeFuelAmount() {
      const liters = parseFloat(document.getElementById("updateLiters").value) || 0;
      const unitPrice = parseFloat(document.getElementById("updateUnitPrice").value) || 0;
      const amount = liters * unitPrice;
      document.getElementById("updateAmount").value = amount.toFixed(2); // Rounds to 2 decimal places
    }

    function populateEditForm(fuel) {
      // Set the form fields in the modal using the fuel data passed from the table row
      document.getElementById("updateFuelID").value = fuel.FuelID;
      document.getElementById("updateFuelDate").value = fuel.Date;
      document.getElementById("updateLiters").value = fuel.Liters;
      document.getElementById("updateUnitPrice").value = fuel.UnitPrice;
      document.getElementById("updateFuelType").value = fuel.FuelType;
      document.getElementById("updateAmount").value = fuel.Amount;
    }
  </script>

  <script>
    let currentSortColumn = -1; // Track the currently sorted column
    let isAscending = true; // Track the sorting direction

    function sortTable(columnIndex) {
      let table = document.getElementById("expenseTable");
      let rows = Array.from(table.getElementsByTagName("tr")).slice(1); // Skip the header row
      let isNumeric = columnIndex === 0 || columnIndex >= 2; // Set columns that require numeric sorting

      // Toggle sorting direction
      if (currentSortColumn === columnIndex) {
        isAscending = !isAscending;
      } else {
        currentSortColumn = columnIndex;
        isAscending = true; // Default to ascending order
      }

      // Sort the rows
      let sortedRows = rows.sort((a, b) => {
        let aValue = a.getElementsByTagName("td")[columnIndex].innerText.trim();
        let bValue = b.getElementsByTagName("td")[columnIndex].innerText.trim();

        if (isNumeric) {
          aValue = parseFloat(aValue) || 0;
          bValue = parseFloat(bValue) || 0;
        }

        if (isAscending) {
          return aValue > bValue ? 1 : -1;
        } else {
          return aValue < bValue ? 1 : -1;
        }
      });

      // Update the table with sorted rows
      let tableBody = document.getElementById("tableBody");
      tableBody.innerHTML = ""; // Clear the current table body
      sortedRows.forEach(row => tableBody.appendChild(row));

      // Update the sorting icons
      updateSortingIcons(columnIndex);
    }

    function updateSortingIcons(columnIndex) {
      let headers = document.querySelectorAll("th");
      headers.forEach((header, index) => {
        header.classList.remove('ascending', 'descending');
        if (index === columnIndex) {
          header.classList.add(isAscending ? 'ascending' : 'descending');
        }
      });
    }
  </script>

  <script>
    let currentFuelSortColumn = -1; // Track the currently sorted column for the fuel table
    let isFuelAscending = true; // Track the sorting direction for the fuel table

    function sortFuelTable(columnIndex) {
      let table = document.getElementById("fuelTable");
      let rows = Array.from(table.getElementsByTagName("tr")).slice(1); // Skip the header row
      let isNumeric = columnIndex === 0 || columnIndex >= 2 && columnIndex <= 5; // Define numeric columns (FuelID, Liters, Unit Price, Amount)

      // Toggle sorting direction
      if (currentFuelSortColumn === columnIndex) {
        isFuelAscending = !isFuelAscending;
      } else {
        currentFuelSortColumn = columnIndex;
        isFuelAscending = true; // Default to ascending order
      }

      // Sort the rows
      let sortedRows = rows.sort((a, b) => {
        let aValue = a.getElementsByTagName("td")[columnIndex].innerText.trim();
        let bValue = b.getElementsByTagName("td")[columnIndex].innerText.trim();

        if (isNumeric) {
          aValue = parseFloat(aValue) || 0;
          bValue = parseFloat(bValue) || 0;
        }

        if (isFuelAscending) {
          return aValue > bValue ? 1 : -1;
        } else {
          return aValue < bValue ? 1 : -1;
        }
      });

      // Update the table with sorted rows
      let tableBody = document.getElementById("fuelTableBody");
      tableBody.innerHTML = ""; // Clear the current table body
      sortedRows.forEach(row => tableBody.appendChild(row));

      // Update the sorting icons
      updateFuelSortingIcons(columnIndex);
    }

    function updateFuelSortingIcons(columnIndex) {
      let headers = document.querySelectorAll("#fuelTable th");
      headers.forEach((header, index) => {
        header.classList.remove('ascending', 'descending');
        if (index === columnIndex) {
          header.classList.add(isFuelAscending ? 'ascending' : 'descending');
        }
      });
    }
  </script>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const tableBody = document.getElementById("tableBody");
      const rowsPerPageSelect = document.getElementById("rowsPerPage");
      const searchBar = document.getElementById("searchBar");
      const paginationNumbers = document.getElementById("paginationNumbers");

      let currentPage = 1;
      let rowsPerPage = parseInt(rowsPerPageSelect.value);
      let totalRecords = 0;

      function loadTableData(page = 1, rowsPerPage = 10, search = "") {
        const offset = (page - 1) * rowsPerPage;

        fetch(`fetch_expenses.php?limit=${rowsPerPage}&offset=${offset}&search=${encodeURIComponent(search)}`)
          .then((response) => response.json())
          .then(({
            data,
            total
          }) => {
            totalRecords = total;

            // Dynamically update totalPages
            totalPages = Math.ceil(totalRecords / rowsPerPage);
            document.getElementById("totalPages").textContent = totalPages;

            if (data.length > 0) {
              tableBody.innerHTML = ""; // Clear the table
              // Populate the table with fetched data
              data.forEach((row) => {
                const tableRow = document.createElement("tr");
                tableRow.innerHTML = `
    <td>${row.ExpenseID}</td>
    <td>${row.Date}</td>
    <td>${row.SalaryAmount}</td>
    <td>${row.MobileAmount}</td>
    <td>${row.OtherAmount}</td>
    <td>${row.TotalExpense}</td>
    <td>
      <a href="#" class="me-3 text-primary" data-bs-toggle="modal"
         data-bs-target="#editFuelExpenseModal" onclick='populateExpenseEditForm(${JSON.stringify(row)})'>
        <i class="fs-4 ti ti-edit"></i>
      </a>
    </td>
  `;
                tableBody.appendChild(tableRow);
              });

              updatePagination(); // Update pagination
            } else {
              tableBody.innerHTML = "<tr><td colspan='7'>No records found</td></tr>";
              paginationNumbers.innerHTML = ""; // Clear pagination
            }
          })
          .catch((error) => console.error("Error fetching data:", error));
      }


      function updatePagination() {
        paginationNumbers.innerHTML = ""; // Clear pagination
        const totalPages = Math.ceil(totalRecords / rowsPerPage);

        for (let i = 1; i <= totalPages; i++) {
          const pageItem = document.createElement("li");
          pageItem.classList.add("page-item");
          if (i === currentPage) pageItem.classList.add("active");

          const pageLink = document.createElement("button");
          pageLink.classList.add("page-link");
          pageLink.textContent = i;

          pageLink.onclick = () => {
            currentPage = i;
            loadTableData(currentPage, rowsPerPage, searchBar.value);
          };

          pageItem.appendChild(pageLink);
          paginationNumbers.appendChild(pageItem);
        }
      }

      rowsPerPageSelect.addEventListener("change", () => {
        rowsPerPage = parseInt(rowsPerPageSelect.value);
        currentPage = 1; // Reset to the first page
        loadTableData(currentPage, rowsPerPage, searchBar.value);
      });

      searchBar.addEventListener("input", () => {
        currentPage = 1; // Reset to the first page
        loadTableData(currentPage, rowsPerPage, searchBar.value);
      });

      loadTableData(currentPage, rowsPerPage); // Initial load

      function updatePagination() {
        paginationNumbers.innerHTML = ""; // Clear existing pagination numbers

        totalPages = Math.ceil(totalRecords / rowsPerPage); // Ensure totalPages is calculated
        document.getElementById("totalPages").textContent = totalPages; // Update the span

        const maxVisiblePages = window.innerWidth <= 768 ? 3 : 5; // Adjust visible pages for mobile view
        const halfVisible = Math.floor(maxVisiblePages / 2);

        let startPage = Math.max(currentPage - halfVisible, 1);
        let endPage = Math.min(startPage + maxVisiblePages - 1, totalPages);

        // Adjust if near start or end
        if (endPage - startPage + 1 < maxVisiblePages) {
          startPage = Math.max(endPage - maxVisiblePages + 1, 1);
        }

        // Add First and Previous Buttons (<< and <)
        paginationNumbers.appendChild(
          createPaginationItem("«", currentPage === 1, () => {
            currentPage = 1;
            loadTableData(currentPage, rowsPerPage, searchBar.value);
          })
        );

        paginationNumbers.appendChild(
          createPaginationItem("‹", currentPage === 1, () => {
            currentPage--;
            loadTableData(currentPage, rowsPerPage, searchBar.value);
          })
        );

        // Add numbered page buttons
        for (let i = startPage; i <= endPage; i++) {
          const pageItem = document.createElement("li");
          pageItem.classList.add("page-item");
          if (i === currentPage) pageItem.classList.add("active");

          const pageLink = document.createElement("button");
          pageLink.classList.add("page-link");
          pageLink.textContent = i;

          pageLink.onclick = () => {
            currentPage = i;
            loadTableData(currentPage, rowsPerPage, searchBar.value);
          };

          pageItem.appendChild(pageLink);
          paginationNumbers.appendChild(pageItem);
        }

        // Add Next and Last Buttons (> and >>)
        paginationNumbers.appendChild(
          createPaginationItem("›", currentPage === totalPages, () => {
            currentPage++;
            loadTableData(currentPage, rowsPerPage, searchBar.value);
          })
        );

        paginationNumbers.appendChild(
          createPaginationItem("»", currentPage === totalPages, () => {
            currentPage = totalPages;
            loadTableData(currentPage, rowsPerPage, searchBar.value);
          })
        );
      }

      function createPaginationItem(label, isDisabled, onClick) {
        const pageItem = document.createElement("li");
        pageItem.classList.add("page-item");
        if (isDisabled) pageItem.classList.add("disabled");

        const pageLink = document.createElement("button");
        pageLink.classList.add("page-link");
        pageLink.textContent = label;

        if (!isDisabled) pageLink.onclick = onClick;

        pageItem.appendChild(pageLink);
        return pageItem;
      }


    });
  </script>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
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

    .sortable {
      cursor: pointer;
    }

    .ascending::after {
      content: ' ↑';
    }

    .descending::after {
      content: ' ↓';
    }

    table td,
    table th {
      min-width: 100px;
      /* Adjust as needed */
      max-width: 200px;
      /* Adjust based on your design */
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
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
    }

    .pagination .page-link:hover {
      background-color: #e9ecef;
    }
  </style>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

</div>
<?php
include '../officer/footer.php';
?>