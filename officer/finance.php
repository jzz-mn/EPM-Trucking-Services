<?php
session_start();
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
              <h5 class="modal-title text-white fs-4">Edit Fuel Expense</h5>
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
                              <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
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
                <div class="row align-items-center">
                  <div class="col-md-4">
                    <input type="text" id="searchBar" class="form-control" placeholder="Search..." onkeyup="filterTable()" />
                  </div>
                  <div class="col-md-4 offset-md-4 text-end">
                    <select id="rowsPerPage" class="form-select w-auto d-inline" onchange="changeRowsPerPage()">
                      <option value="5">5 rows</option>
                      <option value="10">10 rows</option>
                      <option value="20">20 rows</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="py-3">
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
                    <tbody id="tableBody">
                      <?php
                      include '../includes/db_connection.php';

                      // Fetch data from the expenses table
                      $query = "SELECT * FROM expenses";
                      $result = mysqli_query($conn, $query);

                      if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                          // Prepare row data as JSON to be used in JavaScript for the modal
                          $rowJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                          echo "<tr>";
                          echo "<td>" . $row['ExpenseID'] . "</td>";
                          echo "<td>" . $row['Date'] . "</td>";
                          echo "<td>" . $row['SalaryAmount'] . "</td>";
                          echo "<td>" . $row['MobileAmount'] . "</td>";
                          echo "<td>" . $row['OtherAmount'] . "</td>";
                          echo "<td>" . $row['TotalExpense'] . "</td>";
                          echo "<td>";
                          // Edit button inside the table row, opens a modal and populates form with selected row data
                          echo "<a href='#' class='me-3 text-primary' data-bs-toggle='modal' data-bs-target='#editFuelExpenseModal' onclick='populateExpenseEditForm(" . $rowJson . ");'><i class='fs-4 ti ti-edit'></i></a>";
                          // Delete button (example)
                          echo "</td>";
                          echo "</tr>";
                        }
                      } else {
                        echo "<tr><td colspan='7'>No records found</td></tr>";
                      }
                      mysqli_close($conn);
                      ?>

                    </tbody>
                  </table>
                </div>

                <div class="pagination-controls d-flex justify-content-end align-items-center mt-3">
                  <!-- Previous button with margin on the right for spacing -->
                  <button id="prevBtn" class="btn btn-primary me-2" onclick="prevPage()">Previous</button>

                  <!-- Bootstrap pagination component for page numbers -->
                  <nav>
                    <ul class="pagination mb-0" id="paginationNumbers"></ul>
                  </nav>

                  <!-- Next button with margin on the left for spacing -->
                  <button id="nextBtn" class="btn btn-primary ms-2" onclick="nextPage()">Next</button>
                </div>

                <script>
                  let currentPage = 1;
                  let rowsPerPage = 5;
                  let totalRows = 0;
                  let totalPages = 0;

                  function changeRowsPerPage() {
                    rowsPerPage = parseInt(document.getElementById("rowsPerPage").value);
                    currentPage = 1;
                    updateTable();
                  }

                  function filterTable() {
                    let input = document.getElementById("searchBar").value.toLowerCase();
                    let table = document.getElementById("expenseTable");
                    let rows = table.getElementsByTagName("tr");
                    let noDataFound = true; // Flag to check if no row matches the search criteria

                    for (let i = 1; i < rows.length; i++) {
                      let row = rows[i];
                      if (row.id !== "noDataRow") {
                        row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";

                        // If any row is found, set the flag to false
                        if (row.style.display === "") {
                          noDataFound = false;
                        }
                      }
                    }

                    // Show or hide the no data found row based on the flag
                    document.getElementById("noDataRow").style.display = noDataFound ? "" : "none";
                  }

                  function sortTable(columnIndex) {
                    let table = document.getElementById("expenseTable");
                    let rows = Array.from(table.getElementsByTagName("tr")).slice(1);
                    let sortedRows = rows.sort((a, b) => {
                      let aValue = a.getElementsByTagName("td")[columnIndex].innerText;
                      let bValue = b.getElementsByTagName("td")[columnIndex].innerText;
                      return aValue.localeCompare(bValue);
                    });

                    let tableBody = document.getElementById("tableBody");
                    tableBody.innerHTML = "";
                    sortedRows.forEach(row => tableBody.appendChild(row));
                    updateTable();
                  }

                  function updateTable() {
                    let table = document.getElementById("expenseTable");
                    let rows = Array.from(table.getElementsByTagName("tr")).slice(1);
                    totalRows = rows.length;
                    totalPages = Math.ceil(totalRows / rowsPerPage);

                    let startIndex = (currentPage - 1) * rowsPerPage;
                    let endIndex = startIndex + rowsPerPage;

                    rows.forEach((row, index) => {
                      row.style.display = index >= startIndex && index < endIndex ? "" : "none";
                    });

                    document.getElementById("prevBtn").disabled = currentPage === 1;
                    document.getElementById("nextBtn").disabled = currentPage === totalPages;

                    // Update the pagination numbers
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

                  // Function to dynamically update pagination numbers
                  function updatePaginationNumbers() {
                    const paginationNumbers = document.getElementById("paginationNumbers");
                    paginationNumbers.innerHTML = ''; // Clear existing numbers

                    for (let i = 1; i <= totalPages; i++) {
                      const pageItem = document.createElement("li");
                      pageItem.classList.add("page-item");
                      if (i === currentPage) {
                        pageItem.classList.add("active");
                      }
                      const pageLink = document.createElement("a");
                      pageLink.classList.add("page-link");
                      pageLink.textContent = i;
                      pageLink.addEventListener('click', () => {
                        currentPage = i;
                        updateTable();
                      });

                      pageItem.appendChild(pageLink);
                      paginationNumbers.appendChild(pageItem);
                    }
                  }

                  // Initial load
                  updateTable();
                </script>



              </div>
            </div>
            <div class="tab-pane" id="profile" role="tabpanel">


              <div class="table-controls mb-3">
                <div class="row align-items-center">
                  <div class="col-md-4">
                    <input type="text" id="fuelSearchBar" class="form-control" placeholder="Search..." onkeyup="filterFuelTable()">
                  </div>
                  <div class="col-md-4 offset-md-4 text-end">
                    <select id="fuelRowsPerPage" class="form-select w-auto d-inline" onchange="changeFuelRowsPerPage()">
                      <option value="5">5 rows</option>
                      <option value="10">10 rows</option>
                      <option value="20">20 rows</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="py-3">
                <div class="table-responsive">
                  <table id="fuelTable" class="table text-center table-striped table-bordered display text-nowrap">
                    <thead>
                      <tr>
                        <th onclick="sortFuelTable(0)">Fuel ID</th>
                        <th onclick="sortFuelTable(1)">Date</th>
                        <th onclick="sortFuelTable(2)">Liters</th>
                        <th onclick="sortFuelTable(3)">Unit Price</th>
                        <th onclick="sortFuelTable(4)">Fuel Type</th>
                        <th onclick="sortFuelTable(5)">Amount</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody id="fuelTableBody">
                      <?php
                      include '../includes/db_connection.php';

                      // Fetch data from the fuel table
                      $sql = "SELECT FuelID, Date, Liters, UnitPrice, FuelType, Amount FROM fuel";
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
                          echo "<a href='#' class='me-3 text-primary' data-bs-toggle='modal' data-bs-target='#editFuelModal' onclick='populateFuelEditForm(" . $fuelData . ");'><i class='fs-4 ti ti-edit'></i></a>";
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

                <div class="pagination-controls d-flex justify-content-end align-items-center mt-3">
                  <!-- Previous button with margin on the right for spacing -->
                  <button id="fuelPrevBtn" class="btn btn-primary me-2" onclick="prevFuelPage()">Previous</button>

                  <!-- Bootstrap pagination component for page numbers -->
                  <nav>
                    <ul class="pagination mb-0" id="fuelPaginationNumbers"></ul>
                  </nav>

                  <!-- Next button with margin on the left for spacing -->
                  <button id="fuelNextBtn" class="btn btn-primary ms-2" onclick="nextFuelPage()">Next</button>
                </div>
              </div>


              <!-- Edit Fuel Modal -->
              <div class="modal fade" id="editFuelModal" tabindex="-1" role="dialog" aria-labelledby="editFuelModalTitle" aria-hidden="true">
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
                                      <input type="text" class="form-control" id="updateFuelID" name="updateFuelID" readonly>
                                    </div>
                                  </div>
                                  <!-- Date -->
                                  <div class="col-lg-4">
                                    <div class="mb-3">
                                      <label for="updateDate" class="form-label">Date</label>
                                      <input type="date" class="form-control" id="updateDate" name="updateDate">
                                    </div>
                                  </div>
                                  <!-- Liters -->
                                  <div class="col-lg-4">
                                    <div class="mb-3">
                                      <label for="updateLiters" class="form-label">Liters</label>
                                      <input type="number" class="form-control" id="updateLiters" name="updateLiters" step="0.01" oninput="computeFuelAmount()">
                                    </div>
                                  </div>
                                  <!-- Unit Price -->
                                  <div class="col-lg-4">
                                    <div class="mb-3">
                                      <label for="updateUnitPrice" class="form-label">Unit Price</label>
                                      <input type="number" class="form-control" id="updateUnitPrice" name="updateUnitPrice" step="0.01" oninput="computeFuelAmount()">
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
                                      <input type="number" class="form-control" id="updateAmount" name="updateAmount" step="0.01" readonly>
                                    </div>
                                  </div>
                                  <div class="col-12">
                                    <div class="d-flex align-items-center justify-content-end mt-4 gap-6">
                                      <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
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


              <script>
                let fuelCurrentPage = 1;
                let fuelRowsPerPage = 5;
                let totalFuelRows = 0;
                let totalFuelPages = 0;

                function changeFuelRowsPerPage() {
                  fuelRowsPerPage = parseInt(document.getElementById("fuelRowsPerPage").value);
                  fuelCurrentPage = 1;
                  updateFuelTable();
                }

                function updateFuelTable() {
                  let table = document.getElementById("fuelTable");
                  let rows = Array.from(table.getElementsByTagName("tr")).slice(1); // Exclude header
                  totalFuelRows = rows.length;
                  totalFuelPages = Math.ceil(totalFuelRows / fuelRowsPerPage);

                  let startIndex = (fuelCurrentPage - 1) * fuelRowsPerPage;
                  let endIndex = startIndex + fuelRowsPerPage;

                  // Display rows based on the current page
                  rows.forEach((row, index) => {
                    row.style.display = index >= startIndex && index < endIndex ? "" : "none";
                  });

                  // Disable prev/next buttons accordingly
                  document.getElementById("fuelPrevBtn").disabled = fuelCurrentPage === 1;
                  document.getElementById("fuelNextBtn").disabled = fuelCurrentPage === totalFuelPages;

                  // Update pagination numbers
                  updateFuelPaginationNumbers();
                }

                function updateFuelPaginationNumbers() {
                  const paginationNumbers = document.getElementById("fuelPaginationNumbers");
                  paginationNumbers.innerHTML = ''; // Clear existing numbers

                  for (let i = 1; i <= totalFuelPages; i++) {
                    const pageItem = document.createElement("li");
                    pageItem.classList.add("page-item");
                    if (i === fuelCurrentPage) {
                      pageItem.classList.add("active");
                    }
                    const pageLink = document.createElement("a");
                    pageLink.classList.add("page-link");
                    pageLink.textContent = i;
                    pageLink.addEventListener('click', () => {
                      fuelCurrentPage = i;
                      updateFuelTable();
                    });

                    pageItem.appendChild(pageLink);
                    paginationNumbers.appendChild(pageItem);
                  }
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

                // Call this function to initialize the table on page load
                document.addEventListener('DOMContentLoaded', updateFuelTable);

                // Populate modal form with the selected row data
                function populateFuelEditForm(fuel) {
                  document.getElementById("updateFuelID").value = fuel.FuelID;
                  document.getElementById("updateDate").value = fuel.Date;
                  document.getElementById("updateLiters").value = fuel.Liters;
                  document.getElementById("updateUnitPrice").value = fuel.UnitPrice;
                  document.getElementById("updateFuelType").value = fuel.FuelType;
                  document.getElementById("updateAmount").value = fuel.Amount;
                }

                function filterFuelTable() {
                  let input = document.getElementById("fuelSearchBar").value.toLowerCase();
                  let table = document.getElementById("fuelTable");
                  let rows = table.getElementsByTagName("tr");
                  let noDataFound = true; // Flag to check if no row matches the search criteria

                  for (let i = 1; i < rows.length; i++) { // Start from 1 to skip the header
                    let row = rows[i];
                    if (row.id !== "noFuelDataRow") {
                      row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";

                      // If any row is found, set the flag to false
                      if (row.style.display === "") {
                        noDataFound = false;
                      }
                    }
                  }

                  // Show or hide the no data found row based on the flag
                  document.getElementById("noFuelDataRow").style.display = noDataFound ? "" : "none";
                }


                // Initial load
                updateFuelTable();
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
      // Set values in the modal based on the selected expense row
      document.getElementById("updateExpenseID").value = expense.ExpenseID;
      document.getElementById("updateDate").value = expense.Date;
      document.getElementById("updateSalaryAmount").value = expense.SalaryAmount;
      document.getElementById("updateMobileAmount").value = expense.MobileAmount;
      document.getElementById("updateOtherAmount").value = expense.OtherAmount;
      document.getElementById("updateTotalExpense").value = expense.TotalExpense;
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
      document.getElementById("updateDate").value = fuel.Date;
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
      document.getElementById("updateDate").value = fuel.Date;
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

  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">


  <?php
  include '../officer/footer.php';
  ?>