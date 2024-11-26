<?php
session_start();
include '../employee/header.php';
include '../includes/db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['UserID'])) {
  header('Location: ../index.php');
  exit();
}

// Fetch truck details for display
$truck_query = "SELECT TruckID, PlateNo, TruckBrand, TruckStatus FROM trucksinfo";
$truck_result = $conn->query($truck_query);
?>

<div class="body-wrapper">
  <div class="container-fluid">
    <div class="card card-body py-3">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="d-sm-flex align-items-center justify-space-between">
            <h4 class="mb-4 mb-sm-0 card-title">Add Maintenance Record</h4>
            <nav aria-label="breadcrumb" class="ms-auto">
              <ol class="breadcrumb">

                <li class="breadcrumb-item" aria-current="page">
                  <span class="badge bg-primary-subtle text-primary">Maintenance</span>
                </li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Maintenance Form -->
    <div class="card">
      <div class="card-body p-4">
        <form id="addMaintenanceForm" action="add_maintenance.php" method="POST">
          <div class="row">
            <div class="col-lg-6">
              <div class="mb-3">
                <label for="maintenanceDate" class="form-label">Date</label>
                <input type="date" class="form-control" id="maintenanceDate" name="maintenanceDate" required>
              </div>
            </div>
            <div class="col-md-6">
              <label for="truck-select" class="form-label">Select Truck</label>
              <select class="form-select" id="truck-select" name="truck_id" required>
                <option value="" disabled selected>Select a truck</option>
                <?php
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
                <select class="form-control" id="maintenanceDescription" name="maintenanceDescription" required>
                  <option value="" disabled selected>Select a description</option>
                  <option value="PURCHASE MATERIALS (COOL AIR SPARE PARTS)">PURCHASE MATERIALS (COOL AIR SPARE PARTS)</option>
                  <option value="REEFER BAN REPAIR LABOR">REEFER BAN REPAIR LABOR</option>
                  <option value="FREON">FREON</option>
                  <option value="COMPRESSOR">COMPRESSOR</option>
                  <option value="FUEL & OIL EXP">FUEL & OIL EXP</option>
                  <option value="GEAR OIL EXP">GEAR OIL EXP</option>
                  <option value="TIRE 2T 750X15">TIRE 2T 750X15</option>
                  <option value="TUBE 2T 750X15">TUBE 2T 750X15</option>
                  <option value="TIRE 2T 700X16">TIRE 2T 700X16</option>
                  <option value="TUBE 2T 700X16">TUBE 2T 700X16</option>
                  <option value="TUBE 4T 700X16">TUBE 4T 700X16</option>
                  <option value="TOLL GATE">TOLL GATE</option>
                  <option value="PARKING FEE">PARKING FEE</option>
                  <option value="PARKING FEE/TOLL GATE">PARKING FEE/TOLL GATE</option>
                  <option value="TRUCK INSURANCE">TRUCK INSURANCE</option>
                  <option value="LEGALIZATION">LEGALIZATION</option>
                </select>
              </div>
            </div>
            <div class="col-12">
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-primary" onclick="reviewData()">Review & Save</button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
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
        <button type="button" class="btn btn-primary" onclick="confirmSubmission()">Submit</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Function to populate confirmation modal
  function reviewData() {
    const maintenanceDate = document.getElementById('maintenanceDate').value;
    const maintenanceCategory = document.getElementById('maintenanceCategory').value;
    const maintenanceDescription = document.getElementById('maintenanceDescription').value;

    // Populate the modal
    document.getElementById('reviewDate').textContent = maintenanceDate;
    document.getElementById('reviewCategory').textContent = maintenanceCategory;
    document.getElementById('reviewDescription').textContent = maintenanceDescription;

    // Show confirmation modal
    const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    confirmationModal.show();
  }

  // Function to submit the form and show SweetAlert
  function confirmSubmission() {
    Swal.fire({
      title: 'Are you sure?',
      text: "You are about to submit this maintenance record.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Submit',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          title: 'Submitted!',
          text: 'Your maintenance record has been saved.',
          icon: 'success',
          timer: 2000, // Display success alert for 2 seconds
          showConfirmButton: false
        });

        // Add a delay of 2 seconds (2000ms) before submitting the form
        setTimeout(() => {
          document.getElementById('addMaintenanceForm').submit();
        }, 2000);
      }
    });
  }
</script>

<?php
include '../employee/footer.php';
?>