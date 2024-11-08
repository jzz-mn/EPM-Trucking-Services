<?php
session_start();
include '../employee/header.php';
include '../includes/db_connection.php';

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
                  <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">Trucks</span>
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
                              $truck_query = "SELECT TruckID, PlateNo, TruckBrand FROM trucksinfo";
                              $truck_result = $conn->query($truck_query);
                              if ($truck_result->num_rows > 0) {
                                while ($truck = $truck_result->fetch_assoc()) {
                                  echo '<option value="' . $truck['TruckID'] . '">' . $truck['PlateNo'] . ' - ' . $truck['TruckBrand'] . '</option>';
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
                              <input type="hidden" id="loggedBy" name="loggedBy" value="<?php echo $_SESSION['UserID']; ?>">
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

      <h5 class="border-bottom py-2 px-4 mb-4">Trucks</h5>
      <div class="card">
        <div class="card-body p-0">
          <div class="tab-content p-4">
            <div class="tab-pane active" id="home" role="tabpanel">
              <div class="row mt-3">
                <div class="col-md-4 col-xl-3">
                  <form class="position-relative">
                    <input type="text" class="form-control product-search" id="input-search" placeholder="Search" onkeyup="searchMaintenance()" />
                    <i class="ti position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                  </form>
                </div>
                <div class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                  <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
                    data-bs-target="#addMaintenanceRecordModal">
                    <i class="ti ti-users text-white me-1 fs-5"></i> Add Maintenance Record
                  </a>
                </div>
              </div>
              <div class="py-3">
                <!-- Maintenance Table -->
                <?php
                include '../includes/db_connection.php';

                // Query to fetch data from truckmaintenance table
                $query = "SELECT MaintenanceID, Year, Month, Category, Description, TruckID FROM truckmaintenance";
                $result = $conn->query($query);
                ?>
                <div class="table-responsive">
                  <table class="table table-striped table-bordered text-nowrap align-middle text-center">
                    <thead>
                      <tr>
                        <th>Maintenance ID</th>
                        <th>Year</th>
                        <th>Month</th>
                        <th>Truck ID</th>
                        <th>Category</th>
                        <th>Description</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                          echo "<tr>";
                          echo "<td>" . $row['MaintenanceID'] . "</td>";
                          echo "<td>" . $row['Year'] . "</td>";
                          echo "<td>" . $row['Month'] . "</td>";
                          echo "<td>" . $row['TruckID'] . "</td>";
                          echo "<td>" . $row['Category'] . "</td>";
                          echo "<td>" . $row['Description'] . "</td>";
                          echo "</tr>";
                        }
                      } else {
                        echo "<tr><td colspan='6'>No records found</td></tr>";
                      }
                      ?>
                    </tbody>
                  </table>
                </div>

                <?php
                // Close the database connection
                $conn->close();
                ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
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
  document.getElementById('confirmSubmit').addEventListener('click', function() {
    document.getElementById('addMaintenanceForm').submit(); // Submit the form
  });
</script>

<script>
  // Function to search and filter maintenance table
  function searchMaintenance() {
    // Get the search input and filter value
    const searchInput = document.getElementById("input-search").value.toLowerCase();
    const tableRows = document.querySelectorAll(".table tbody tr");

    // Loop through all table rows
    tableRows.forEach((row) => {
      const cells = row.querySelectorAll("td");
      let match = false;

      // Check if any cell in the row contains the search term
      cells.forEach((cell) => {
        if (cell.innerText.toLowerCase().includes(searchInput)) {
          match = true;
        }
      });

      // Toggle the visibility of the row based on the match result
      row.style.display = match ? "" : "none";
    });
  }
</script>

<?php
include '../employee/footer.php';
?>