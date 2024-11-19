<?php
session_start();
include '../employee/header.php';
include '../includes/db_connection.php';
// Check if the user is logged in (redundant if already handled in header.php)
if (!isset($_SESSION['UserID'])) {
  header('Location: ../index.php');
  exit();
}

// Fetch truck details for display
if (isset($_SESSION['truck_id']) && !isset($truck_display)) {
  $truck_query = "SELECT TruckID, PlateNo, TruckBrand FROM trucksinfo WHERE TruckID = ?";
  $stmt = $conn->prepare($truck_query);
  $stmt->bind_param("i", $_SESSION['truck_id']);
  $stmt->execute();
  $truck_result_display = $stmt->get_result();
  $truck_display = $truck_result_display->fetch_assoc();
  $stmt->close();
}
?>

<?php
include '../includes/db_connection.php';

// Fetch logged-in user's UserID
$loggedInUserID = $_SESSION['UserID'];

// Query to fetch maintenance records created by the logged-in user
$query = "SELECT MaintenanceID, Year, Month, Category, Description, TruckID 
          FROM truckmaintenance 
          WHERE LoggedBy = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $loggedInUserID);
$stmt->execute();
$result = $stmt->get_result();
?>


<div class="body-wrapper">
  <div class="container-fluid">
    <div class="card card-body py-3">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="d-sm-flex align-items-center justify-space-between">
            <h4 class="mb-4 mb-sm-0 card-title">Maintenance</h4>
            <nav aria-label="breadcrumb" class="ms-auto">
              <ol class="breadcrumb">
                <li class="breadcrumb-item d-flex align-items-center">
                  <a class="text-muted text-decoration-none d-flex" href="../employee/home.php">
                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                  </a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                  <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">Maintenance</span>
                </li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <div class="widget-content searchable-container list">
      <!-- Add Maintenance Modal -->
      <div class="modal fade" id="addMaintenanceRecordModal" tabindex="-1" role="dialog"
        aria-labelledby="addMaintenanceRecordModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header d-flex align-items-center bg-primary">
              <h5 class="modal-title text-white fs-4">Add Maintenance Record</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-12">
                  <div class="card w-100 border position-relative overflow-hidden mb-0">
                    <div class="card-body p-4">
                      <h4 class="card-title">Add Maintenance Record</h4>
                      <p class="card-subtitle mb-4">Fill out the form to record a maintenance expense.</p>
                      <form id="addMaintenanceForm" action="add_maintenance.php" method="POST">
                        <div class="row">
                          <div class="col-lg-6 d-none">
                            <div class="mb-3">
                              <label for="maintenanceId" class="form-label">Maintenance ID</label>
                              <input type="text" class="form-control" id="maintenanceId" name="maintenanceId"
                                value="<?php echo $nextMaintenanceID; ?>" readonly>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="maintenanceDate" class="form-label">Date</label>
                              <input type="date" class="form-control" id="maintenanceDate" name="maintenanceDate"
                                placeholder="Select Date" required>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label for="truck-select" class="form-label">Select Truck</label>
                            <select class="form-select" id="truck-select" name="truck_id" required>
                              <option value="" disabled selected>Select a truck</option>
                              <?php
                              $truck_query = "SELECT TruckID, PlateNo, TruckBrand, TruckStatus FROM trucksinfo";
                              $truck_result = $conn->query($truck_query);
                              if ($truck_result->num_rows > 0) {
                                while ($truck = $truck_result->fetch_assoc()) {
                                  $disabled = $truck['TruckStatus'] === 'Deactivated' ? 'disabled' : '';
                                  echo '<option value="' . $truck['TruckID'] . '" ' . $disabled . '>';
                                  echo $truck['PlateNo'] . ' - ' . $truck['TruckBrand'] . ' (' . $truck['TruckStatus'] . ')';
                                  echo '</option>';
                                }
                              } else {
                                echo '<option value="">No trucks available</option>';
                              }
                              ?>
                            </select>
                          </div>

                          <div class="col-lg-12">
                            <div class="mb-3">
                              <label for="maintenanceCategory" class="form-label">Category</label>
                              <select class="form-control" id="maintenanceCategory" name="maintenanceCategory" required>
                                <option value="Cool Air Maintenance">Cool Air Maintenance</option>
                                <option value="Legalization Fee">Legalization Fee</option>
                                <option value="Office Fee">Office Fee</option>
                              </select>
                            </div>
                          </div>
                          <div class="col-lg-12">
                            <div class="mb-3">
                              <label for="maintenanceDescription" class="form-label">Description</label>
                              <input type="text" class="form-control" id="maintenanceDescription"
                                name="maintenanceDescription" placeholder="Enter Description" required>
                            </div>
                          </div>
                          <div class="col-lg-6 d-none">
                            <div class="mb-3">
                              <label for="loggedBy" class="form-label">Logged By</label>
                              <!-- Hidden field not necessary; directly use session -->
                              <input type="hidden" id="loggedBy" name="loggedBy"
                                value="<?php echo $_SESSION['UserID']; ?>">
                            </div>
                          </div>

                          <div class="col-12">
                            <div class="d-flex align-items-center justify-content-end mt-4 gap-6">
                              <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
                              <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#confirmationModal" onclick="reviewData()">Save</button>
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

      <!-- Confirmation Modal -->
      <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="confirmationModalLabel">Confirm Submission</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p>Please review the details below before submitting:</p>
              <ul class="list-group">
                <li class="list-group-item"><strong>Date:</strong> <span id="reviewDate"></span></li>
                <li class="list-group-item"><strong>Category:</strong> <span id="reviewCategory"></span></li>
                <li class="list-group-item"><strong>Description:</strong> <span id="reviewDescription"></span></li>
              </ul>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-primary" id="confirmSubmit">Yes, Confirm</button>
            </div>
          </div>
        </div>
      </div>


      <!-- Maintenance Table -->
      <?php
      include '../includes/db_connection.php';

      // Fetch logged-in user's UserID
      $loggedInUserID = $_SESSION['UserID'];

      // Query to fetch maintenance records created by the logged-in user
      $query = "SELECT MaintenanceID, Year, Month, TruckID, Category, Description 
          FROM truckmaintenance 
          WHERE LoggedBy = ?
          ORDER BY MaintenanceID DESC";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("i", $loggedInUserID);
      $stmt->execute();
      $result = $stmt->get_result();
      ?>

      <?php
      include '../includes/db_connection.php';

      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $truckId = $_POST['truck_id'];

        // Check if the selected truck is deactivated
        $truck_check_query = "SELECT TruckStatus FROM trucksinfo WHERE TruckID = ?";
        $stmt = $conn->prepare($truck_check_query);
        $stmt->bind_param("i", $truckId);
        $stmt->execute();
        $result = $stmt->get_result();
        $truck = $result->fetch_assoc();

        if ($truck && $truck['TruckStatus'] === 'Deactivated') {
          // Return an error if the truck is deactivated
          echo "Error: The selected truck is deactivated and cannot be used.";
          exit;
        }

        // Proceed with adding the maintenance record
        // Your code to insert the maintenance record into the database
      }
      ?>


      <div class="card">
        <div class="card-body p-3">
          <div class="row">
            <div class="maintenance-header d-flex align-items-center border-bottom pb-3">
              <a href="#" class="btn btn-primary d-flex align-items-center ms-auto" data-bs-toggle="modal"
                data-bs-target="#addMaintenanceRecordModal">
                <i class="ti ti-users text-white me-1 fs-5"></i> Add Maintenance Record
              </a>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center my-4">
            <div class="col-md-4">
              <input type="text" id="maintenanceSearchBar" class="form-control" placeholder="Search..."
                onkeyup="filterMaintenance()">
            </div>
            <div class="col-md-4 text-end">
              <select id="rowsPerPage" class="form-select w-auto d-inline m-1" onchange="changeRowsPerPage()">
                <option value="5">5 rows</option>
                <option value="10">10 rows</option>
                <option value="20">20 rows</option>
              </select>
            </div>
          </div>



          <div class="table-responsive">
            <table class="table table-striped table-bordered text-nowrap align-middle text-center"
              id="maintenanceTable">
              <thead>
                <tr>
                  <th class="sortable" onclick="sortTable(0, true)">Maintenance ID</th>
                  <th class="sortable" onclick="sortTable(1)">Year</th>
                  <th class="sortable" onclick="sortTable(2)">Month</th>
                  <th class="sortable" onclick="sortTable(3)">Truck ID</th>
                  <th class="sortable" onclick="sortTable(4)">Category</th>
                  <th class="sortable" onclick="sortTable(5)">Description</th>
                </tr>
              </thead>
              <tbody id="maintenanceBody">
                <?php
                if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$row['MaintenanceID']}</td>";
                    echo "<td>{$row['Year']}</td>";
                    echo "<td>{$row['Month']}</td>";
                    echo "<td>{$row['TruckID']}</td>";
                    echo "<td>{$row['Category']}</td>";
                    echo "<td>{$row['Description']}</td>";
                    echo "</tr>";
                  }
                } else {
                  echo "<tr><td colspan='6' class='text-center'>No records found</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
          <div
            class="pagination-controls d-flex justify-content-between align-items-center mt-3 flex-column flex-md-row">
            <div class="order-2 order-md-1 mt-3 mt-md-0">
              <span>Number of pages: <span id="totalPagesMaintenance"></span></span>
            </div>
            <nav aria-label="Page navigation" class="order-1 order-md-2 w-100">
              <ul class="pagination justify-content-center justify-content-md-end mb-0"
                id="maintenancePaginationNumbers">
                <!-- Pagination buttons will be dynamically generated -->
              </ul>
            </nav>

          </div>


          <script>
            // Function to review data before submitting
            function reviewData() {
              const maintenanceDate = document.getElementById('maintenanceDate').value;
              const maintenanceCategory = document.getElementById('maintenanceCategory').value;
              const maintenanceDescription = document.getElementById('maintenanceDescription').value;

              // Populate the confirmation modal with the form data
              document.getElementById('reviewDate').innerText = maintenanceDate;
              document.getElementById('reviewCategory').innerText = maintenanceCategory;
              document.getElementById('reviewDescription').innerText = maintenanceDescription;
            }

            // Submit form if user confirms
            document.getElementById('confirmSubmit').addEventListener('click', function () {
              document.getElementById('addMaintenanceForm').submit(); // Submit the form
            });
          </script>

          <script>
            let maintenanceCurrentPage = 1;
            let maintenanceRowsPerPage = 5;
            let allMaintenanceRows = [];
            let filteredMaintenanceRows = [];

            document.addEventListener('DOMContentLoaded', () => {
              // Capture all rows in the table
              allMaintenanceRows = Array.from(document.querySelectorAll('#maintenanceTable tbody tr'));
              filteredMaintenanceRows = [...allMaintenanceRows]; // Initially, all rows are included
              updateMaintenanceTable(); // Initialize the table display
            });

            // Change rows per page
            function changeRowsPerPage() {
              maintenanceRowsPerPage = parseInt(document.getElementById("rowsPerPage").value);
              maintenanceCurrentPage = 1; // Reset to the first page
              updateMaintenanceTable();
            }

            // Filter the table based on search input
            function filterMaintenance() {
              const searchValue = document.getElementById("maintenanceSearchBar").value.toLowerCase();
              filteredMaintenanceRows = allMaintenanceRows.filter(row =>
                row.innerText.toLowerCase().includes(searchValue)
              );

              maintenanceCurrentPage = 1; // Reset to the first page after filtering
              updateMaintenanceTable();

              // Show "No Records Found" message if no rows match
              if (filteredMaintenanceRows.length === 0) {
                document.getElementById("maintenanceBody").innerHTML = `
      <tr id="noMaintenanceDataRow">
        <td colspan="6" class="text-center">No records found</td>
      </tr>`;
              } else {
                document.getElementById("noMaintenanceDataRow")?.remove(); // Remove "No Records Found" row if rows exist
              }
            }

            // Update the table display based on pagination and filtering
            function updateMaintenanceTable() {
              const totalRows = filteredMaintenanceRows.length;
              const totalPages = Math.ceil(totalRows / maintenanceRowsPerPage) || 1;

              document.getElementById("totalPagesMaintenance").textContent = totalPages;

              // Calculate the start and end indices for the current page
              const startIndex = (maintenanceCurrentPage - 1) * maintenanceRowsPerPage;
              const endIndex = startIndex + maintenanceRowsPerPage;

              // Hide all rows and show only the rows for the current page
              allMaintenanceRows.forEach(row => (row.style.display = 'none'));
              filteredMaintenanceRows.slice(startIndex, endIndex).forEach(row => (row.style.display = ''));

              updateMaintenancePaginationNumbers(totalPages);
            }

            // Update pagination buttons
            function updateMaintenancePaginationNumbers(totalPages) {
              const paginationNumbers = document.getElementById("maintenancePaginationNumbers");
              paginationNumbers.innerHTML = ''; // Clear existing pagination buttons

              const maxVisiblePages = window.innerWidth <= 768 ? 3 : 5;
              let startPage = Math.max(1, maintenanceCurrentPage - Math.floor(maxVisiblePages / 2));
              let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

              if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
              }

              // Add "First Page" button
              paginationNumbers.appendChild(createPaginationItem('«', maintenanceCurrentPage === 1, () => {
                maintenanceCurrentPage = 1;
                updateMaintenanceTable();
              }));

              // Add "Previous Page" button
              paginationNumbers.appendChild(createPaginationItem('‹', maintenanceCurrentPage === 1, () => {
                if (maintenanceCurrentPage > 1) {
                  maintenanceCurrentPage--;
                  updateMaintenanceTable();
                }
              }));

              // Add page numbers
              for (let i = startPage; i <= endPage; i++) {
                const pageItem = document.createElement("li");
                pageItem.classList.add("page-item");
                if (i === maintenanceCurrentPage) {
                  pageItem.classList.add("active");
                }

                const pageLink = document.createElement("a");
                pageLink.classList.add("page-link");
                pageLink.textContent = i;
                pageLink.addEventListener('click', () => {
                  maintenanceCurrentPage = i;
                  updateMaintenanceTable();
                });

                pageItem.appendChild(pageLink);
                paginationNumbers.appendChild(pageItem);
              }

              // Add "Next Page" button
              paginationNumbers.appendChild(createPaginationItem('›', maintenanceCurrentPage === totalPages, () => {
                if (maintenanceCurrentPage < totalPages) {
                  maintenanceCurrentPage++;
                  updateMaintenanceTable();
                }
              }));

              // Add "Last Page" button
              paginationNumbers.appendChild(createPaginationItem('»', maintenanceCurrentPage === totalPages, () => {
                maintenanceCurrentPage = totalPages;
                updateMaintenanceTable();
              }));
            }

            // Create a pagination item
            function createPaginationItem(label, isDisabled, onClick) {
              const pageItem = document.createElement("li");
              pageItem.classList.add("page-item");
              if (isDisabled) {
                pageItem.classList.add("disabled");
              }

              const pageLink = document.createElement("a");
              pageLink.classList.add("page-link");
              pageLink.textContent = label;
              pageLink.style.cursor = isDisabled ? 'default' : 'pointer';

              if (!isDisabled) {
                pageLink.addEventListener('click', onClick);
              }

              pageItem.appendChild(pageLink);
              return pageItem;
            }

            document.addEventListener("DOMContentLoaded", () => {
              allMaintenanceRows = Array.from(document.querySelectorAll("#maintenanceTable tbody tr"));
              filteredMaintenanceRows = [...allMaintenanceRows]; // Initially, all rows are included
              updateMaintenanceTable(); // Initialize the table display

              // Add click event listeners to table headers for sorting
              document.querySelectorAll("#maintenanceTable th").forEach((header, index) => {
                header.addEventListener("click", () => {
                  const isAscending = header.classList.contains("ascending");
                  sortTable(index, !isAscending);
                  updateSortIcons(header, isAscending);
                });
              });
            });

            // Function to sort the table by a specific column
            function sortTable(columnIndex, ascending) {
              filteredMaintenanceRows.sort((a, b) => {
                const aText = a.cells[columnIndex].textContent.trim().toLowerCase();
                const bText = b.cells[columnIndex].textContent.trim().toLowerCase();

                if (!isNaN(aText) && !isNaN(bText)) {
                  // Compare numbers
                  return ascending ? aText - bText : bText - aText;
                } else {
                  // Compare strings
                  return ascending ? aText.localeCompare(bText) : bText.localeCompare(aText);
                }
              });

              maintenanceCurrentPage = 1; // Reset to the first page after sorting
              updateMaintenanceTable();
            }

            // Function to update the sort icons (ascending/descending)
            function updateSortIcons(clickedHeader, isCurrentlyAscending) {
              const headers = document.querySelectorAll("#maintenanceTable th");

              headers.forEach(header => {
                header.classList.remove("ascending", "descending");
              });

              if (isCurrentlyAscending) {
                clickedHeader.classList.add("descending");
              } else {
                clickedHeader.classList.add("ascending");
              }
            }
          </script>

          <style>
            .dark-mode .pagination .page-item .page-link {
              /* Dark background for pagination items */
              color: #fff;
              /* Light text for readability */
            }

            .dark-mode .pagination .page-item.active .page-link {
              background-color: #fa896b;
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

            /* Add ascending and descending arrow icons */
            .ascending::after {
              content: ' ↑';
              /* Unicode up arrow */
            }

            .descending::after {
              content: ' ↓';
              /* Unicode down arrow */
            }

            .pagination .page-item .page-link {
              border: none;
              /* Remove border from non-highlighted items */
              margin: 0 2px;
              /* Add spacing between items */
            }

            .pagination .page-item.active .page-link {
              background-color: #fa896b;
              /* Blue background for the active page */
              color: #fff;
              /* White text for the active page */
              border-radius: 50%;
              /* Make the active page a circle */
              min-width: 35px;
              /* Set width for the circle */
              height: 35px;
              /* Set height for the circle */
              display: flex;
              /* Center text */
              align-items: center;
              /* Center text vertically */
              justify-content: center;
              /* Center text horizontally */
            }

            @media (max-width: 768px) {
              .pagination .page-item .page-link {
                font-size: 12px;
                /* Reduce font size for mobile */
                padding: 0.5rem;
                /* Adjust padding */

              }
            }

            .pagination .page-item.active .page-link {
              min-width: 35px;
              /* Adjust width for smaller screen */
              height: 35px;
              /* Adjust height for smaller screen */
              font-size: 12px;
              /* Reduce font size for active page */
            }

            .pagination-controls {
              flex-direction: column;
              /* Stack elements vertically on mobile */
              align-items: center;
              /* Center align items */
            }



            .pagination .page-link:hover {
              background-color: #e9ecef;
              /* Hover background color */
              color: #000;
              /* Text color on hover */
            }
          </style>

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

          <?php
          include '../employee/footer.php';
          ?>