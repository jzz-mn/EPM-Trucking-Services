<?php
$allowedRoles = ['SuperAdmin', 'Officer'];

// Include the authentication script
require_once '../includes/auth.php';

// Set Cache-Control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include '../officer/header.php';
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
                    Trucks
                  </span>
                </li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Transaction Modal -->
    <div class="modal fade" id="updateTransactionModal" tabindex="-1" role="dialog"
      aria-labelledby="updateTransactionModalTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header d-flex align-items-center bg-primary">
            <h5 class="modal-title text-white fs-4">Edit Transaction</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-12">
                <div class="card w-100 border position-relative overflow-hidden mb-0">
                  <div class="card-body p-4">
                    <h4 class="card-title">Edit Transaction</h4>
                    <p class="card-subtitle mb-4">Fill out the details to update the transaction.</p>
                    <form action="update_transaction.php" method="POST">
                      <div class="row">
                        <!-- Transaction ID (Read-only) -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateTransactionID" class="form-label">Transaction ID</label>
                            <input type="text" class="form-control" id="updateTransactionID" name="transactionID"
                              readonly>
                          </div>
                        </div>


                        <!-- Date -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateTransactionDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="updateTransactionDate" name="transactionDate">
                          </div>
                        </div>

                        <!-- Billing Invoice Number (Dropdown) -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateInvoiceID" class="form-label">Billing Invoice Number</label>
                            <select class="form-select" id="updateInvoiceID" name="invoiceID">
                              <option value="" disabled selected>Select Billing Invoice Number</option>
                              <?php
                              // Populate the dropdown as before
                              include '../includes/db_connection.php';
                              $invoiceQuery = "SELECT DISTINCT BillingInvoiceNo FROM invoices ORDER BY BillingInvoiceNo DESC";
                              $invoiceResult = mysqli_query($conn, $invoiceQuery);
                              while ($row = mysqli_fetch_assoc($invoiceResult)) {
                                echo "<option value='{$row['BillingInvoiceNo']}'>{$row['BillingInvoiceNo']}</option>";
                              }
                              ?>
                            </select>
                          </div>
                        </div>

                        <!-- Expense ID (Dropdown) -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateExpenseID" class="form-label">Expense ID</label>
                            <select class="form-select" id="updateExpenseID" name="expenseID">
                              <option value="" disabled selected>Select Expense ID</option>
                              <?php
                              $expenseQuery = "SELECT ExpenseID FROM expenses ORDER BY ExpenseID DESC";
                              $expenseResult = mysqli_query($conn, $expenseQuery);
                              while ($row = mysqli_fetch_assoc($expenseResult)) {
                                echo "<option value='{$row['ExpenseID']}'>{$row['ExpenseID']}</option>";
                              }
                              ?>
                            </select>
                          </div>
                        </div>

                        <!-- Plate Number -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updatePlateNumber" class="form-label">Plate Number</label>
                            <input type="text" class="form-control" id="updatePlateNumber" name="plateNumber"
                              placeholder="Enter Plate Number">
                          </div>
                        </div>

                        <!-- DR Number -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateDRNumber" class="form-label">DR Number</label>
                            <input type="text" class="form-control" id="updateDRNumber" name="drNumber"
                              placeholder="Enter DR Number">
                          </div>
                        </div>

                        <!-- Source Customer Code -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateSourceCustomerCode" class="form-label">Source Customer Code</label>
                            <input type="text" class="form-control" id="updateSourceCustomerCode"
                              name="sourceCustomerCode" placeholder="Enter Source Customer Code">
                          </div>
                        </div>

                        <!-- Customer Name -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateCustomerName" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="updateCustomerName" name="customerName"
                              placeholder="Enter Customer Name">
                          </div>
                        </div>

                        <!-- Destination Customer Code -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateDestinationCustomerCode" class="form-label">Destination Customer
                              Code</label>
                            <input type="text" class="form-control" id="updateDestinationCustomerCode"
                              name="destinationCustomerCode" placeholder="Enter Destination Customer Code">
                          </div>
                        </div>

                        <!-- Quantity -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateQuantityQtl" class="form-label">Quantity (Qtl)</label>
                            <input type="number" class="form-control" id="updateQuantityQtl" name="quantityQtl"
                              step="0.01" placeholder="Enter Quantity">
                          </div>
                        </div>

                        <!-- Weight (Kgs) -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="updateWeightKgs" class="form-label">Weight (Kgs)</label>
                            <input type="number" class="form-control" id="updateWeightKgs" name="weightKgs" step="0.01"
                              placeholder="Enter Weight">

                          </div>
                        </div>

                        <!-- Submit and Cancel Buttons -->
                        <div class="col-12">
                          <div class="d-flex align-items-center justify-content-end mt-4 gap-6">
                            <button type="button" class="btn bg-danger-subtle text-danger"
                              data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                          </div>
                        </div>
                      </div> <!-- End of row -->
                    </form> <!-- End of form -->
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Edit Maintenance Record Modal -->
    <div class="modal fade" id="updateMaintenanceRecordModal" tabindex="-1" role="dialog"
      aria-labelledby="updateMaintenanceRecordModalTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header d-flex align-items-center bg-primary">
            <h5 class="modal-title text-white fs-4">Edit Maintenance Record</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-12">
                <div class="card w-100 border position-relative overflow-hidden mb-0">
                  <div class="card-body p-4">
                    <h4 class="card-title">Edit Maintenance Record</h4>
                    <p class="card-subtitle mb-4">Fill out the form to record a maintenance expense.</p>

                    <!-- Updated Form -->
                    <form id="editMaintenanceForm" method="POST" action="update_maintenance.php">
                      <div class="row">
                        <!-- Maintenance ID -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="maintenanceId" class="form-label">Maintenance ID</label>
                            <input type="text" class="form-control" id="maintenanceId" name="maintenanceId" readonly>
                          </div>
                        </div>

                        <!-- Year -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="maintenanceYear" class="form-label">Year</label>
                            <input type="number" class="form-control" id="maintenanceYear" name="maintenanceYear"
                              placeholder="Enter Year" min="1900" max="2100" step="1">
                          </div>
                        </div>

                        <!-- Month -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="maintenanceMonth" class="form-label">Month</label>
                            <select class="form-select" id="maintenanceMonth" name="maintenanceMonth">
                              <option value="">Select Month</option>
                              <option value="1">January</option>
                              <option value="2">February</option>
                              <option value="3">March</option>
                              <option value="4">April</option>
                              <option value="5">May</option>
                              <option value="6">June</option>
                              <option value="7">July</option>
                              <option value="8">August</option>
                              <option value="9">September</option>
                              <option value="10">October</option>
                              <option value="11">November</option>
                              <option value="12">December</option>
                            </select>
                          </div>
                        </div>

                        <!-- Category -->
                        <div class="col-lg-4">
                          <div class="mb-3">
                            <label for="maintenanceCategory" class="form-label">Category</label>
                            <input type="text" class="form-control" id="maintenanceCategory" name="maintenanceCategory"
                              placeholder="Enter Category">
                          </div>
                        </div>

                        <!-- Description -->
                        <div class="col-lg-4">
                          <div class="mb-3">
                            <label for="maintenanceDescription" class="form-label">Description</label>
                            <input type="text" class="form-control" id="maintenanceDescription"
                              name="maintenanceDescription" placeholder="Enter Description">
                          </div>
                        </div>

                        <!-- Amount -->
                        <div class="col-lg-4">
                          <div class="mb-3">
                            <label for="maintenanceAmount" class="form-label">Amount</label>
                            <input type="number" class="form-control" id="maintenanceAmount" name="maintenanceAmount"
                              placeholder="Enter Amount" step="0.01" min="0" max="1000000000000">
                          </div>
                        </div>

                        <!-- Logged By -->
                        <div class="col-lg-4">
                          <div class="mb-3">
                            <label for="maintenanceLoggedBy" class="form-label">Logged By</label>
                            <input type="text" class="form-control" id="maintenanceLoggedBy" name="maintenanceLoggedBy" readonly>
                          </div>
                        </div>

                        <!-- Form Buttons -->
                        <div class="col-12">
                          <div class="d-flex align-items-center justify-content-end mt-4 gap-6">
                            <button type="button" class="btn bg-danger-subtle text-danger"
                              data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                          </div>
                        </div>
                      </div>
                    </form>
                    <!-- End of Form -->
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Edit Transaction Modal -->
    <div class="modal fade" id="editTransactionModal" tabindex="-1" role="dialog"
      aria-labelledby="editTransactionModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header d-flex align-items-center bg-primary">
            <h5 class="modal-title text-white fs-4" id="editTransactionModalLabel">Edit Transaction</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-12">
                <div class="card w-100 border position-relative overflow-hidden mb-0">
                  <div class="card-body p-4">
                    <h4 class="card-title">Edit Transaction</h4>
                    <p class="card-subtitle mb-4">Fill out the form to update the transaction.</p>

                    <form id="updateTransactionForm" method="POST" action="update_transaction.php">
                      <div class="row">

                        <!-- Transaction ID (hidden) -->
                        <input type="hidden" id="transactionId" name="transactionId">

                        <!-- Transaction Date -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="transactionDate" class="form-label">Transaction Date</label>
                            <input type="date" class="form-control" id="transactionDate" name="transactionDate"
                              required>
                          </div>
                        </div>

                        <!-- DR No -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="drNo" class="form-label">DR No</label>
                            <input type="text" class="form-control" id="drNo" name="drNo" readonly>
                          </div>
                        </div>

                        <!-- Outlet Name -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="outletName" class="form-label">Outlet Name</label>
                            <input type="text" class="form-control" id="outletName" name="outletName" required>
                          </div>
                        </div>

                        <!-- Quantity -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="qty" class="form-label">Qty</label>
                            <input type="number" class="form-control" id="qty" name="qty" required>
                          </div>
                        </div>

                        <!-- KGs -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="kgs" class="form-label">KGs</label>
                            <input type="number" class="form-control" id="kgs" name="kgs" required>
                          </div>
                        </div>

                        <!-- Submit and Cancel Buttons -->
                        <div class="col-12">
                          <div class="d-flex justify-content-end mt-4 gap-3">
                            <button type="button" class="btn bg-danger-subtle text-danger"
                              data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                          </div>
                        </div>
                      </div> <!-- End of row -->
                    </form> <!-- End of form -->

                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="updateTrucksRecordModal" tabindex="-1" role="dialog"
      aria-labelledby="updateTrucksRecordModalTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header d-flex align-items-center bg-primary">
            <h5 class="modal-title text-white fs-4">Edit Trucks Record</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-12">
                <div class="card w-100 border position-relative overflow-hidden mb-0">
                  <div class="card-body p-4">
                    <h4 class="card-title">Edit Trucks Record</h4>
                    <p class="card-subtitle mb-4">Fill out the form to record a truck information.</p>

                    <!-- Place the updated form here -->
                    <form method="POST" action="edit_truck.php">
                      <div class="row">
                        <!-- Maintenance ID -->
                        <div class="col-lg-6">
                          <div class="mb-3">
                            <label for="maintenanceId" class="form-label">Truck ID</label>
                            <input type="text" class="form-control" id="truckId" name="truckId" readonly>
                          </div>
                        </div>

                        <!-- Plate Number -->
                        <div class="col-lg-4">
                          <div class="mb-3">
                            <label for="plateNumber" class="form-label">Plate Number</label>
                            <input type="text" class="form-control" id="plateNumber" name="plateNumber"
                              placeholder="Enter Plate Number">
                          </div>
                        </div>

                        <!-- Truck Brand -->
                        <div class="col-lg-4">
                          <div class="mb-3">
                            <label for="truckBrand" class="form-label">Truck Brand</label>
                            <input type="text" class="form-control" id="truckBrand" name="truckBrand"
                              placeholder="Enter Truck Brand">
                          </div>
                        </div>

                        <!-- Truck Status -->
                        <div class="col-lg-4">
                          <div class="mb-3">
                            <label for="truckStatus" class="form-label">Truck Status</label>
                            <select class="form-select" id="truckStatus" name="truckStatus" required>
                              <option value="" disabled selected>Select Truck Status</option>
                              <option value="Activated">Activated</option>
                              <option value="Deactivated">Deactivated</option>
                            </select>
                          </div>
                        </div>

                        <!-- Submit and Cancel Buttons -->
                        <div class="col-12">
                          <div class="d-flex justify-content-end mt-4 gap-3">
                            <button type="button" class="btn bg-danger-subtle text-danger"
                              data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
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

    <!-- Edit Cluster Modal -->
    <div class="modal fade" id="editClusterModal" tabindex="-1" role="dialog" aria-labelledby="editClusterModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header d-flex align-items-center bg-primary">
            <h5 class="modal-title text-white fs-4" id="editClusterModalLabel">Edit Cluster</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="editClusterForm" method="POST" action="update_cluster.php">
              <input type="hidden" id="uniqueClusterId" name="uniqueClusterId">
              <div class="mb-3">
                <label for="clusterID" class="form-label">Cluster ID</label>
                <input type="text" class="form-control" id="clusterID" name="clusterID" required>
              </div>
              <div class="mb-3">
                <label for="clusterCategory" class="form-label">Cluster Category</label>
                <input type="text" class="form-control" id="clusterCategory" name="clusterCategory" required>
              </div>
              <div class="mb-3">
                <label for="locationsInCluster" class="form-label">Locations in Cluster</label>
                <input type="text" class="form-control" id="locationsInCluster" name="locationsInCluster" required>
              </div>
              <div class="mb-3">
                <label for="tonner" class="form-label">Tonner</label>
                <select class="form-select" id="tonner" name="tonner" required>
                  <option value="" disabled selected>Select Tonner</option>
                  <option value="1000">1000</option>
                  <option value="2000">2000</option>
                  <option value="3000">3000</option>
                  <option value="4000">4000</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="kmRadius" class="form-label">KM Radius</label>
                <input type="number" class="form-control" id="kmRadius" name="kmRadius" required>
              </div>
              <div class="mb-3">
                <label for="fuelPrice" class="form-label">Fuel Price</label>
                <input type="number" class="form-control" id="fuelPrice" name="fuelPrice" step="0.01" required>
              </div>
              <div class="mb-3">
                <label for="rateAmount" class="form-label">Rate Amount</label>
                <input type="number" class="form-control" id="rateAmount" name="rateAmount" step="0.01" required>
              </div>
              <div class="d-flex justify-content-end">
                <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary ms-2">Save Changes</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>


    <?php
    // Fetch the latest UniqueClusterID for the new cluster modal
    $lastClusterIdQuery = "SELECT UniqueClusterID FROM clusters ORDER BY UniqueClusterID DESC LIMIT 1";
    $lastClusterIdResult = mysqli_query($conn, $lastClusterIdQuery);
    $lastClusterIdRow = mysqli_fetch_assoc($lastClusterIdResult);
    $newUniqueClusterID = $lastClusterIdRow ? $lastClusterIdRow['UniqueClusterID'] + 1 : 1;

    $clusterIdQuery = "SELECT MAX(ClusterID) AS maxID FROM clusters";
    $clusterIdResult = mysqli_query($conn, $clusterIdQuery);
    $nextClusterID = ($clusterIdResult && mysqli_num_rows($clusterIdResult) > 0) ? mysqli_fetch_assoc($clusterIdResult)['maxID'] + 1 : 1;
    ?>

    <div class="modal fade" id="addClusterModal" tabindex="-1" role="dialog" aria-labelledby="addClusterModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header d-flex align-items-center bg-primary">
            <h5 class="modal-title text-white fs-4" id="addClusterModalLabel">Add Cluster</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="addClusterForm" method="POST" action="add_cluster.php">
              <!-- Display auto-incremented UniqueClusterID -->
              <div class="mb-3">
                <label for="uniqueClusterID" class="form-label">Unique Cluster ID</label>
                <input type="text" class="form-control" id="uniqueClusterID" name="uniqueClusterID" value="<?php echo $newUniqueClusterID; ?>" readonly>
              </div>

              <!-- Choose Existing or New Cluster -->
              <div class="mb-3">
                <label for="clusterSelection" class="form-label">Choose Cluster Type</label>
                <select class="form-select" id="clusterSelection" name="clusterSelection" required>
                  <option value="" disabled selected>Select Cluster Type</option>
                  <option value="existing">Existing Cluster</option>
                  <option value="new">New Cluster</option>
                </select>
              </div>

              <!-- Existing Cluster Fields -->
              <div id="existingClusterFields" style="display: none;">
                <div class="mb-3">
                  <label for="existingClusterCategory" class="form-label">Select Existing Cluster Category</label>
                  <select class="form-select" id="existingClusterCategory" name="existingClusterCategory">
                    <option value="" disabled selected>Select Cluster Category</option>
                    <?php
                    // Fetch unique clusters from the database
                    $clusterQuery = "SELECT DISTINCT ClusterID, ClusterCategory, LocationsInCluster FROM clusters";
                    $clusterResult = mysqli_query($conn, $clusterQuery);
                    while ($row = mysqli_fetch_assoc($clusterResult)) {
                      echo "<option value='{$row['ClusterCategory']}' data-clusterID='{$row['ClusterID']}' data-locations='{$row['LocationsInCluster']}'>
                      {$row['ClusterCategory']}
                      </option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label for="existingClusterID" class="form-label">Cluster ID</label>
                  <input type="text" class="form-control" id="existingClusterID" name="existingClusterID" readonly>
                </div>
                <div class="mb-3">
                  <label for="existingLocationsInCluster" class="form-label">Locations in Cluster</label>
                  <input type="text" class="form-control" id="existingLocationsInCluster" name="existingLocationsInCluster" readonly>
                </div>
              </div>

              <!-- New Cluster Fields -->
              <div id="newClusterFields" style="display: none;">
                <div class="mb-3">
                  <label for="newClusterID" class="form-label">Cluster ID</label>
                  <input type="text" class="form-control" id="newClusterID" name="newClusterID" value="<?php echo $nextClusterID; ?>" readonly>
                </div>
                <div class="mb-3">
                  <label for="newClusterCategory" class="form-label">Cluster Category</label>
                  <input type="text" class="form-control" id="newClusterCategory" name="newClusterCategory">
                </div>
                <div class="mb-3">
                  <label for="newLocationsInCluster" class="form-label">Locations in Cluster</label>
                  <input type="text" class="form-control" id="newLocationsInCluster" name="newLocationsInCluster">
                </div>
              </div>

              <!-- Shared Fields -->
              <div class="mb-3">
                <label for="tonner" class="form-label">Tonner</label>
                <select class="form-select" id="tonner" name="tonner" required>
                  <option value="" disabled selected>Select Tonner</option>
                  <option value="1000">1000</option>
                  <option value="2000">2000</option>
                  <option value="3000">3000</option>
                  <option value="4000">4000</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="kmRadius" class="form-label">KM Radius</label>
                <input type="number" class="form-control" id="kmRadius" name="kmRadius" required>
              </div>
              <div class="mb-3">
                <label for="fuelPrice" class="form-label">Fuel Price</label>
                <input type="number" class="form-control" id="fuelPrice" name="fuelPrice" step="0.01" required>
              </div>
              <div class="mb-3">
                <label for="rateAmount" class="form-label">Rate Amount</label>
                <input type="number" class="form-control" id="rateAmount" name="rateAmount" step="0.01" required>
              </div>

              <div id="clusterWarning" style="display: none;"></div>


              <div class="d-flex justify-content-end">
                <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary ms-2">Add Cluster</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>




    <h5 class="border-bottom py-2 px-4 mb-4">Trucks</h5>
    <div class="card">
      <div class="card-body p-0">
        <div class>
          <!-- Nav tabs -->
          <ul class="nav nav-tabs p-4 border-bottom" role="tablist">
            <li class="nav-item">
              <a class="nav-link active me-3" data-bs-toggle="tab" href="#maintenance" role="tab">
                <span>Maintenance</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="tab" href="#transactions" role="tab">
                <span>Transactions</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="tab" href="#trucks" role="tab">
                <span>Trucks</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="tab" href="#clusters" role="tab">
                <span>Clusters</span>
              </a>
            </li>
          </ul>

          <!-- Transactions Tab -->


          <!-- Single Tab Content Wrapper -->
          <div class="tab-content p-4">
            <!-- Maintenance Tab -->
            <div class="tab-pane active" id="maintenance" role="tabpanel">
              <div class="table-controls mb-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div class="col-md-4">
                    <input type="text" id="maintenanceSearchBar" class="form-control" placeholder="Search...">
                  </div>
                  <div class="col-md-4 text-end">
                    <select id="maintenanceRowsPerPage" class="form-select w-auto d-inline">
                      <option value="5">5 rows</option>
                      <option value="10">10 rows</option>
                      <option value="20">20 rows</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="py-3">
                <!-- Maintenance Table -->
                <div class="table-responsive">
                  <table id="maintenanceTable" class="table text-center table-striped table-bordered display text-nowrap">
                    <thead>
                      <tr>
                        <th onclick="sortMaintenanceTable(0)">Maintenance ID</th>
                        <th onclick="sortMaintenanceTable(1)">Year</th>
                        <th onclick="sortMaintenanceTable(2)">Month</th>
                        <th onclick="sortMaintenanceTable(3)">Category</th>
                        <th onclick="sortMaintenanceTable(4)">Description</th>
                        <th onclick="sortMaintenanceTable(5)">Amount</th>
                        <th onclick="sortMaintenanceTable(6)">Logged by</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody id="maintenanceTableBody">
                      <tr>
                        <td colspan="8" class="text-center">Loading...</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="pagination-controls d-flex justify-content-between align-items-center mt-3 flex-column flex-md-row">
                  <div class="order-2 order-md-1 mt-3 mt-md-0">
                    <span>Number of pages: <span id="totalPagesMaintenance"></span></span>
                  </div>
                  <nav aria-label="Page navigation" class="order-1 order-md-2 w-100">
                    <ul class="pagination justify-content-center justify-content-md-end mb-0" id="maintenancePaginationNumbers">
                      <!-- Pagination buttons will be dynamically generated here -->
                    </ul>
                  </nav>
                </div>
              </div>
            </div>



            <!-- Transactions Tab -->
            <div class="tab-pane" id="transactions" role="tabpanel">
              <div class="table-controls mb-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div class="col-md-4">
                    <input type="text" id="transactionsSearchBar" class="form-control" placeholder="Search..."
                      onkeyup="filterTransactionsTable()" />
                  </div>
                  <div class="col-md-4 text-end">
                    <select id="transactionsRowsPerPage" class="form-select w-auto d-inline"
                      onchange="changeTransactionsRowsPerPage()">
                      <option value="5">5 rows</option>
                      <option value="10">10 rows</option>
                      <option value="20">20 rows</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="py-3">
                <!-- Transactions Table -->
                <?php
                include '../includes/db_connection.php';
                $query = "SELECT TransactionID, TransactionGroupID, TransactionDate, DRno, OutletName, Qty, KGs FROM transactions
                    ORDER BY TransactionID DESC";
                $result = $conn->query($query);
                ?>
                <div class="table-responsive">
                  <table id="transactionsTable"
                    class="table text-center table-striped table-bordered display text-nowrap">
                    <thead>
                      <tr>
                        <th onclick="sortTransactionsTable(0)">Transaction ID</th>
                        <th onclick="sortTransactionsTable(1)">Transaction Date</th>
                        <th onclick="sortTransactionsTable(2)">DR No</th>
                        <th onclick="sortTransactionsTable(3)">Outlet Name</th>
                        <th onclick="sortTransactionsTable(4)">Qty</th>
                        <th onclick="sortTransactionsTable(5)">KGs</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody id="transactionsTableBody">
                      <?php
                      while ($row = $result->fetch_assoc()) {
                        $transactionData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        echo "<tr>";
                        echo "<td>" . $row['TransactionID'] . "</td>";
                        echo "<td>" . $row['TransactionDate'] . "</td>";
                        echo "<td>" . $row['DRno'] . "</td>";
                        echo "<td>" . $row['OutletName'] . "</td>";
                        echo "<td>" . $row['Qty'] . "</td>";
                        echo "<td>" . $row['KGs'] . "</td>";
                        echo "<td>";
                        echo "<a href='#' class='me-3 text-primary edit-transaction-btn' data-bs-toggle='modal' data-transaction='" . $transactionData . "'>";
                        echo "<i class='fs-4 ti ti-edit'></i></a>";
                        echo "</td>";
                        echo "</tr>";
                      }

                      ?>
                    </tbody>
                  </table>
                </div>

                <div class="pagination-controls d-flex justify-content-between align-items-center mt-3 flex-column flex-md-row">
                  <div class="order-2 order-md-1 mt-3 mt-md-0">
                    <span>Number of pages: <span id="totalPagesTransactions"></span></span>
                  </div>
                  <nav aria-label="Page navigation" class="order-1 order-md-2 w-100">
                    <ul class="pagination justify-content-center justify-content-md-end mb-0" id="transactionsPaginationNumbers">
                      <!-- Pagination buttons will be dynamically generated here -->
                    </ul>
                  </nav>
                </div>


              </div>
            </div>

            <div class="tab-pane" id="trucks" role="tabpanel">
              <div class="table-controls mb-3">
                <div class="row align-items-center">
                  <div class="col-md-4">
                    <!-- Search Bar -->
                    <input type="text" id="trucksSearchBar" class="form-control" placeholder="Search..."
                      onkeyup="filterTrucksTable()" />
                  </div>
                  <div class="col-md-4 offset-md-4 text-end">
                  </div>
                </div>
              </div>

              <!-- Trucks Table -->
              <?php
              include '../includes/db_connection.php';
              $query = "SELECT TruckID, PlateNo, TruckBrand, TruckStatus FROM trucksinfo
                  ORDER BY TruckID DESC";
              $result = $conn->query($query);
              ?>
              <div class="py-3">
                <div class="table-responsive">
                  <table id="trucksTable" class="table text-center table-striped table-bordered display text-nowrap">
                    <thead>
                      <tr>
                        <th onclick="sortTrucksTable(0)">Truck ID</th>
                        <th onclick="sortTrucksTable(1)">Plate Number</th>
                        <th onclick="sortTrucksTable(2)">Truck Brand</th>
                        <th onclick="sortTrucksTable(3)">Truck Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody id="trucksTableBody">
                      <?php
                      if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                          $truckData = json_encode($row);  // Prepare the row data as a JSON object
                          echo "<tr>";
                          echo "<td>" . $row['TruckID'] . "</td>";
                          echo "<td>" . $row['PlateNo'] . "</td>";
                          echo "<td>" . $row['TruckBrand'] . "</td>";
                          echo "<td>" . $row['TruckStatus'] . "</td>";
                          echo "<td>";
                          // Add a data-attribute to store the row data and attach the edit button
                          echo "<a href='#' class='me-3 text-primary edit-truck-btn' data-bs-toggle='modal' data-truck='" . htmlspecialchars($truckData) . "'>";
                          echo "<i class='fs-4 ti ti-edit'></i></a>";
                          echo "</td>";
                          echo "</tr>";
                        }
                      } else {
                        echo "<tr><td colspan='4' class='text-center'>No data available</td></tr>";
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- CLUSTER Tab -->
            <div class="tab-pane" id="clusters" role="tabpanel">
              <div class="table-controls mb-3">
                <div class="invoice-header d-flex align-items-center border-bottom pb-3">
                  <a href="#" class="btn btn-primary d-flex align-items-center ms-auto" data-bs-toggle="modal"
                    data-bs-target="#addClusterModal">
                    <i class="ti ti-users text-white me-1 fs-5"></i> Add Cluster
                  </a>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                  <div class="col-md-4">
                    <input type="text" id="clustersSearchBar" class="form-control" placeholder="Search..." onkeyup="filterClustersTable()" />
                  </div>
                  <div class="col-md-4 text-end">
                    <select id="clustersRowsPerPage" class="form-select w-auto d-inline" onchange="changeClustersRowsPerPage()">
                      <option value="5">5 rows</option>
                      <option value="10">10 rows</option>
                      <option value="20">20 rows</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="py-3">
                <div class="table-responsive">
                  <table id="clustersTable" class="table text-center table-striped table-bordered display text-nowrap">
                    <thead>
                      <tr>
                        <th>UniqueClusterID</th>
                        <th>ClusterID</th>
                        <th>ClusterCategory</th>
                        <th>LocationsInCluster</th>
                        <th>Tonner</th>
                        <th>KMRADIUS</th>
                        <th>FuelPrice</th>
                        <th>RateAmount</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody id="clustersTableBody">
                      <?php
                      $query = "SELECT UniqueClusterID, ClusterID, ClusterCategory, LocationsInCluster, Tonner, KMRADIUS, FuelPrice, RateAmount FROM clusters
                          ORDER BY UniqueClusterID DESC";
                      $result = $conn->query($query);
                      if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                          echo "<tr>";
                          echo "<td>" . $row['UniqueClusterID'] . "</td>";
                          echo "<td>" . $row['ClusterID'] . "</td>";
                          echo "<td>" . $row['ClusterCategory'] . "</td>";
                          echo "<td>" . $row['LocationsInCluster'] . "</td>";
                          echo "<td>" . $row['Tonner'] . "</td>";
                          echo "<td>" . $row['KMRADIUS'] . "</td>";
                          echo "<td>" . $row['FuelPrice'] . "</td>";
                          echo "<td>" . $row['RateAmount'] . "</td>";
                          echo "<td>
                              <div class='btn-group'>
                                  <button type='button' class='btn btn-secondary btn-sm dropdown-toggle' data-bs-toggle='dropdown' aria-expanded='false'>
                                      Actions
                                  </button>
                                  <ul class='dropdown-menu'>
                                      <li><a href='#' class='dropdown-item edit-cluster-btn' data-bs-toggle='modal'>Edit Cluster</a></li>
                                      <li><a href='#' class='dropdown-item delete-cluster-btn'>Delete Cluster</a></li>
                                  </ul>
                              </div>
                            </td>";
                          echo "</tr>";
                        }
                      } else {
                        echo "<tr><td colspan='8' class='text-center'>No data available</td></tr>";
                      }
                      ?>
                    </tbody>
                  </table>
                </div>

                <div class="pagination-controls d-flex justify-content-between align-items-center mt-3 flex-column flex-md-row">
                  <div class="order-2 order-md-1 mt-3 mt-md-0">
                    <span>Number of pages: <span id="totalPagesClusters"></span></span>
                  </div>
                  <nav aria-label="Page navigation" class="order-1 order-md-2 w-100">
                    <ul class="pagination justify-content-center justify-content-md-end mb-0" id="clustersPaginationNumbers">
                      <!-- Pagination buttons generated here -->
                    </ul>
                  </nav>
                </div>
              </div>
            </div>


          </div>
        </div>
      </div>
    </div>


    <!----------- button/fetching etc. ---------->

    <script>
      // Function to populate the Edit Transaction modal with the selected record data1
      function populateEditTransactionForm(transaction) {
        // Set values in the modal based on the selected transaction
        document.getElementById("transactionId").value = transaction.TransactionID;
        document.getElementById("transactionDate").value = transaction.TransactionDate;
        document.getElementById("drNo").value = transaction.DRno;
        document.getElementById("outletName").value = transaction.OutletName;
        document.getElementById("qty").value = transaction.Qty;
        document.getElementById("kgs").value = transaction.KGs;
      }

      // Attach the 'populateEditTransactionForm' function to the edit button in your table
      function attachTransactionEditButtons() {
        const editButtons = document.querySelectorAll('.edit-transaction-btn');
        editButtons.forEach(button => {
          button.addEventListener('click', function() {
            const transactionData = JSON.parse(this.dataset.transaction); // Get data from the data attribute
            populateEditTransactionForm(transactionData); // Populate modal with transaction data
            $('#editTransactionModal').modal('show'); // Show the modal
          });
        });
      }

      // Execute this function when the document is fully loaded
      document.addEventListener('DOMContentLoaded', attachTransactionEditButtons);
    </script>
  </div>
</div>

<script>
  function attachTransactionEditButtons() {
    const editButtons = document.querySelectorAll('.edit-transaction-btn');
    editButtons.forEach(button => {
      button.addEventListener('click', function() {
        const transactionData = JSON.parse(this.dataset.transaction);
        populateEditTransactionForm(transactionData);
        $('#editTransactionModal').modal('show');
      });
    });
  }
</script>

<!------------------ Maintenance function --------------------->

<script>
  let maintenanceCurrentPage = 1;
  let maintenanceRowsPerPage = 5;
  let allMaintenanceRows = [];
  let filteredMaintenanceRows = [];

  document.addEventListener('DOMContentLoaded', () => {
    allMaintenanceRows = Array.from(document.querySelectorAll('#maintenanceTable tbody tr'));
    filteredMaintenanceRows = [...allMaintenanceRows]; // Start with all rows as filtered
    updateMaintenanceTable();
  });

  function changeMaintenanceRowsPerPage() {
    maintenanceRowsPerPage = parseInt(document.getElementById("maintenanceRowsPerPage").value);
    maintenanceCurrentPage = 1;
    updateMaintenanceTable();
  }

  function filterMaintenanceTable() {
    const input = document.getElementById("maintenanceSearchBar").value.toLowerCase();
    filteredMaintenanceRows = allMaintenanceRows.filter(row => row.innerText.toLowerCase().includes(input));

    maintenanceCurrentPage = 1; // Reset to the first page after filtering
    updateMaintenanceTable();

    // Display "No data found" row if necessary
    const noDataRow = document.getElementById("noMaintenanceDataRow");
    if (noDataRow) {
      noDataRow.style.display = filteredMaintenanceRows.length === 0 ? '' : 'none';
    }

    // Update pagination for the filtered rows
    updateMaintenancePaginationNumbers(Math.ceil(filteredMaintenanceRows.length / maintenanceRowsPerPage) || 1);
  }

  function updateMaintenanceTable() {
    const totalRows = filteredMaintenanceRows.length;
    const totalPages = Math.ceil(totalRows / maintenanceRowsPerPage) || 1;
    document.getElementById("totalPagesMaintenance").textContent = totalPages;

    const startIndex = (maintenanceCurrentPage - 1) * maintenanceRowsPerPage;
    const endIndex = startIndex + maintenanceRowsPerPage;

    allMaintenanceRows.forEach(row => row.style.display = 'none'); // Hide all rows initially

    // Display only the rows for the current page
    filteredMaintenanceRows.slice(startIndex, endIndex).forEach(row => {
      row.style.display = '';
    });

    updateMaintenancePaginationNumbers(totalPages);
  }

  function updateMaintenancePaginationNumbers(totalPages) {
    const paginationNumbers = document.getElementById("maintenancePaginationNumbers");
    paginationNumbers.innerHTML = ''; // Clear existing pagination numbers

    const isMobileView = window.innerWidth <= 768; // Check if the current view is mobile (width <= 768px)
    const maxVisiblePages = isMobileView ? 3 : 5; // Show 3 pages in mobile view, 5 pages otherwise

    if (totalPages > 1) {
      // Create the '<<' and '<' buttons
      paginationNumbers.appendChild(createPaginationItem('«', maintenanceCurrentPage === 1, () => {
        maintenanceCurrentPage = 1;
        updateMaintenanceTable();
      }));
      paginationNumbers.appendChild(createPaginationItem('‹', maintenanceCurrentPage === 1, () => {
        if (maintenanceCurrentPage > 1) {
          maintenanceCurrentPage--;
          updateMaintenanceTable();
        }
      }));

      // Display a maximum number of pages at a time based on the view
      let startPage = Math.max(1, maintenanceCurrentPage - Math.floor(maxVisiblePages / 2));
      let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

      // Adjust startPage if the total number of pages is less than maxVisiblePages
      if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
      }

      for (let i = startPage; i <= endPage; i++) {
        const pageItem = document.createElement("li");
        pageItem.classList.add("page-item");
        if (i === maintenanceCurrentPage) {
          pageItem.classList.add("active");
        }

        const pageLink = document.createElement("a");
        pageLink.classList.add("page-link");
        pageLink.textContent = i;
        pageLink.style.cursor = 'pointer';
        pageLink.addEventListener('click', () => {
          maintenanceCurrentPage = i;
          updateMaintenanceTable();
        });

        pageItem.appendChild(pageLink);
        paginationNumbers.appendChild(pageItem);
      }

      // Create the '>' and '>>' buttons
      paginationNumbers.appendChild(createPaginationItem('›', maintenanceCurrentPage === totalPages, () => {
        if (maintenanceCurrentPage < totalPages) {
          maintenanceCurrentPage++;
          updateMaintenanceTable();
        }
      }));
      paginationNumbers.appendChild(createPaginationItem('»', maintenanceCurrentPage === totalPages, () => {
        maintenanceCurrentPage = totalPages;
        updateMaintenanceTable();
      }));
    } else {
      // If there's only one page, show the single page as active and circular
      const singlePageItem = document.createElement("li");
      singlePageItem.classList.add("page-item", "active");
      const singlePageLink = document.createElement("a");
      singlePageLink.classList.add("page-link");
      singlePageLink.textContent = "1";
      singlePageItem.appendChild(singlePageLink);
      paginationNumbers.appendChild(singlePageItem);
    }
  }

  // Add event listener to detect window resize and update pagination accordingly
  window.addEventListener('resize', () => {
    updateMaintenancePaginationNumbers(Math.ceil(filteredMaintenanceRows.length / maintenanceRowsPerPage) || 1);
  });

  function createPaginationItem(label, isDisabled, onClick) {
    const pageItem = document.createElement("li");
    pageItem.classList.add("page-item");
    if (isDisabled) {
      pageItem.classList.add("disabled");
    }

    const pageLink = document.createElement("a");
    pageLink.classList.add("page-link");
    pageLink.textContent = label;
    pageLink.style.cursor = isDisabled ? 'default' : 'pointer'; // Change cursor style

    if (!isDisabled) {
      pageLink.addEventListener('click', onClick);
    }

    pageItem.appendChild(pageLink);
    return pageItem;
  }
</script>
<script>
  // Sorting logic for Maintenance Table
  let maintenanceSortColumn = -1;
  let maintenanceSortAscending = true;

  function sortMaintenanceTable(columnIndex) {
    let table = document.getElementById("maintenanceTable");
    let rows = Array.from(table.getElementsByTagName("tr")).slice(1); // Skip header row
    maintenanceSortAscending = maintenanceSortColumn === columnIndex ? !maintenanceSortAscending : true; // Toggle sorting direction
    maintenanceSortColumn = columnIndex;

    // Determine if the column contains numeric values (e.g., Maintenance ID, Year, Amount)
    let isNumericColumn = (columnIndex === 0 || columnIndex === 1 || columnIndex === 5); // Numeric columns: MaintenanceID, Year, Amount

    let sortedRows = rows.sort((a, b) => {
      let aValue = a.getElementsByTagName("td")[columnIndex].innerText.trim();
      let bValue = b.getElementsByTagName("td")[columnIndex].innerText.trim();

      // Sort numerically if it's a numeric column
      if (isNumericColumn) {
        aValue = parseFloat(aValue) || 0;
        bValue = parseFloat(bValue) || 0;
        return maintenanceSortAscending ? aValue - bValue : bValue - aValue;
      }

      // Otherwise, sort alphabetically
      return maintenanceSortAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
    });

    // Clear and append sorted rows
    let tableBody = document.getElementById("maintenanceTableBody");
    tableBody.innerHTML = "";
    sortedRows.forEach(row => tableBody.appendChild(row));

    // Update the sorting icons
    updateMaintenanceSortIcons(columnIndex, maintenanceSortAscending);
  }

  function updateMaintenanceSortIcons(columnIndex, ascending) {
    const headers = document.querySelectorAll("#maintenanceTable th");
    headers.forEach((header, index) => {
      header.classList.remove('ascending', 'descending'); // Remove previous sorting class
      if (index === columnIndex) {
        header.classList.add(ascending ? 'ascending' : 'descending'); // Add ascending or descending class
      }
    });
  }
</script>


<script>
  document.addEventListener('DOMContentLoaded', function() {
    const maintenanceTableBody = document.getElementById("maintenanceTableBody");
    const maintenanceSearchBar = document.getElementById("maintenanceSearchBar");
    const maintenanceRowsPerPageSelect = document.getElementById("maintenanceRowsPerPage");
    const maintenancePaginationNumbers = document.getElementById("maintenancePaginationNumbers");
    const maintenanceTab = document.querySelector('[href="#maintenance"]');

    let maintenanceCurrentPage = 1;
    let maintenanceRowsPerPage = parseInt(maintenanceRowsPerPageSelect.value);
    let maintenanceTotalRecords = 0;
    let maintenanceDataLoaded = false;

    // Function to load Maintenance data
    function loadMaintenanceData(page = 1, rowsPerPage = 5, search = "") {
      const offset = (page - 1) * rowsPerPage;

      // Show loading indicator
      maintenanceTableBody.innerHTML = "<tr><td colspan='8' class='text-center'>Loading...</td></tr>";

      fetch(`fetch_maintenance.php?limit=${rowsPerPage}&offset=${offset}&search=${encodeURIComponent(search)}`)
        .then(response => response.json())
        .then(({ data, total }) => {
          maintenanceTotalRecords = total;
          maintenanceTableBody.innerHTML = ""; // Clear the table

          if (data.length > 0) {
            data.forEach(row => {
              const tableRow = document.createElement("tr");
              const maintenanceData = JSON.stringify(row).replace(/'/g, "\\'"); // Escape single quotes

              tableRow.innerHTML = `
                <td>${row.MaintenanceID}</td>
                <td>${row.Year}</td>
                <td>${row.Month}</td>
                <td>${row.Category}</td>
                <td>${row.Description}</td>
                <td>${row.Amount}</td>
                <td>${row.LoggedBy}</td>
                <td>
                  <a href="#" class="me-3 text-primary edit-maintenance-btn" data-bs-toggle="modal" data-maintenance='${maintenanceData}'>
                    <i class="fs-4 ti ti-edit"></i>
                  </a>
                </td>
              `;
              maintenanceTableBody.appendChild(tableRow);
            });
          } else {
            maintenanceTableBody.innerHTML = "<tr><td colspan='8' class='text-center'>No maintenance records found</td></tr>";
          }

          updateMaintenancePagination();
        })
        .catch(error => {
          console.error("Error fetching maintenance data:", error);
          maintenanceTableBody.innerHTML = "<tr><td colspan='8' class='text-center text-danger'>Error loading data</td></tr>";
        });
    }

    // Function to update Maintenance pagination
    function updateMaintenancePagination() {
      maintenancePaginationNumbers.innerHTML = ""; // Clear existing pagination

      const totalPages = Math.ceil(maintenanceTotalRecords / maintenanceRowsPerPage);
      const maxVisiblePages = window.innerWidth <= 768 ? 3 : 5; // Adjust for mobile

      document.getElementById("totalPagesMaintenance").textContent = totalPages;

      if (totalPages > 1) {
        // Create the '<<' and '<' buttons
        maintenancePaginationNumbers.appendChild(createPaginationItem('«', maintenanceCurrentPage === 1, () => {
          maintenanceCurrentPage = 1;
          loadMaintenanceData(maintenanceCurrentPage, maintenanceRowsPerPage, maintenanceSearchBar.value);
        }));

        maintenancePaginationNumbers.appendChild(createPaginationItem('‹', maintenanceCurrentPage === 1, () => {
          if (maintenanceCurrentPage > 1) {
            maintenanceCurrentPage--;
            loadMaintenanceData(maintenanceCurrentPage, maintenanceRowsPerPage, maintenanceSearchBar.value);
          }
        }));

        let startPage = Math.max(1, maintenanceCurrentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        if (endPage - startPage < maxVisiblePages - 1) {
          startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
          maintenancePaginationNumbers.appendChild(createPaginationItem(i, false, () => {
            maintenanceCurrentPage = i;
            loadMaintenanceData(maintenanceCurrentPage, maintenanceRowsPerPage, maintenanceSearchBar.value);
          }, i === maintenanceCurrentPage));
        }

        maintenancePaginationNumbers.appendChild(createPaginationItem('›', maintenanceCurrentPage === totalPages, () => {
          if (maintenanceCurrentPage < totalPages) {
            maintenanceCurrentPage++;
            loadMaintenanceData(maintenanceCurrentPage, maintenanceRowsPerPage, maintenanceSearchBar.value);
          }
        }));

        maintenancePaginationNumbers.appendChild(createPaginationItem('»', maintenanceCurrentPage === totalPages, () => {
          maintenanceCurrentPage = totalPages;
          loadMaintenanceData(maintenanceCurrentPage, maintenanceRowsPerPage, maintenanceSearchBar.value);
        }));
      }
    }

    function createPaginationItem(label, isDisabled, onClick, isActive = false) {
      const pageItem = document.createElement("li");
      pageItem.classList.add("page-item");
      if (isDisabled) pageItem.classList.add("disabled");
      if (isActive) pageItem.classList.add("active");

      const pageLink = document.createElement("a");
      pageLink.classList.add("page-link");
      pageLink.textContent = label;
      pageLink.style.cursor = isDisabled ? 'not-allowed' : 'pointer';

      if (!isDisabled && !isActive) {
        pageLink.addEventListener('click', onClick);
      }

      pageItem.appendChild(pageLink);
      return pageItem;
    }

    // Event delegation for edit buttons
    maintenanceTableBody.addEventListener('click', function(event) {
      const target = event.target.closest('.edit-maintenance-btn');
      if (target) {
        event.preventDefault(); // Prevent default link behavior
        const maintenanceData = JSON.parse(target.dataset.maintenance);
        populateEditMaintenanceForm(maintenanceData);
      }
    });

    // Function to populate the Edit Maintenance modal with the selected record data
    function populateEditMaintenanceForm(maintenance) {
      // Map month names to numbers (if necessary)
      const monthMap = {
        'January': '1',
        'February': '2',
        'March': '3',
        'April': '4',
        'May': '5',
        'June': '6',
        'July': '7',
        'August': '8',
        'September': '9',
        'October': '10',
        'November': '11',
        'December': '12',
      };

      let monthValue = maintenance.Month;

      // If the month is a name, map it to its numeric value
      if (isNaN(monthValue)) {
        monthValue = monthMap[monthValue];
      }

      // Populate modal fields with maintenance data
      document.getElementById("maintenanceId").value = maintenance.MaintenanceID;
      document.getElementById("maintenanceYear").value = maintenance.Year;
      document.getElementById("maintenanceMonth").value = monthValue;
      document.getElementById("maintenanceCategory").value = maintenance.Category;
      document.getElementById("maintenanceDescription").value = maintenance.Description;
      document.getElementById("maintenanceAmount").value = maintenance.Amount;
      document.getElementById("maintenanceLoggedBy").value = maintenance.LoggedBy;

      // Show the modal using Bootstrap's Vanilla JS API
      const updateModal = new bootstrap.Modal(document.getElementById('updateMaintenanceRecordModal'));
      updateModal.show();
    }

    // Attach event listeners to filtering and rows per page controls
    maintenanceSearchBar.addEventListener("input", () => {
      maintenanceCurrentPage = 1;
      loadMaintenanceData(maintenanceCurrentPage, maintenanceRowsPerPage, maintenanceSearchBar.value);
    });

    maintenanceRowsPerPageSelect.addEventListener("change", () => {
      maintenanceRowsPerPage = parseInt(maintenanceRowsPerPageSelect.value);
      maintenanceCurrentPage = 1;
      loadMaintenanceData(maintenanceCurrentPage, maintenanceRowsPerPage, maintenanceSearchBar.value);
    });

    // Load Maintenance data only when the Maintenance tab is active
    maintenanceTab.addEventListener('shown.bs.tab', () => {
      if (!maintenanceDataLoaded) {
        loadMaintenanceData(maintenanceCurrentPage, maintenanceRowsPerPage, maintenanceSearchBar.value);
        maintenanceDataLoaded = true; // Ensure data is loaded only once per session
      }
    });

    // Optionally, load data immediately if the Maintenance tab is active on page load
    const maintenanceTabPane = document.getElementById('maintenance');
    if (maintenanceTabPane.classList.contains('active')) {
      loadMaintenanceData(maintenanceCurrentPage, maintenanceRowsPerPage, maintenanceSearchBar.value);
      maintenanceDataLoaded = true;
    }
  });
</script>



<!------------------ Transactions function ----------------->
<script>
  let transactionsCurrentPage = 1;
  let transactionsRowsPerPage = 5;
  let allTransactionsRows = [];
  let filteredTransactionsRows = [];

  document.addEventListener('DOMContentLoaded', () => {
    allTransactionsRows = Array.from(document.querySelectorAll('#transactionsTable tbody tr'));
    filteredTransactionsRows = [...allTransactionsRows]; // Start with all rows as filtered
    updateTransactionsTable();
  });

  function changeTransactionsRowsPerPage() {
    transactionsRowsPerPage = parseInt(document.getElementById("transactionsRowsPerPage").value);
    transactionsCurrentPage = 1;
    updateTransactionsTable();
  }

  function filterTransactionsTable() {
    const input = document.getElementById("transactionsSearchBar").value.toLowerCase();
    filteredTransactionsRows = allTransactionsRows.filter(row => row.innerText.toLowerCase().includes(input));

    transactionsCurrentPage = 1; // Reset to the first page after filtering
    updateTransactionsTable();

    // Display "No data found" row if necessary
    const noDataRow = document.getElementById("noTransactionsDataRow");
    if (noDataRow) {
      noDataRow.style.display = filteredTransactionsRows.length === 0 ? '' : 'none';
    }

    // Update pagination for the filtered rows
    updateTransactionsPaginationNumbers(Math.ceil(filteredTransactionsRows.length / transactionsRowsPerPage) || 1);
  }

  function updateTransactionsTable() {
    const totalRows = filteredTransactionsRows.length;
    const totalPages = Math.ceil(totalRows / transactionsRowsPerPage) || 1;
    document.getElementById("totalPagesTransactions").textContent = totalPages;

    const startIndex = (transactionsCurrentPage - 1) * transactionsRowsPerPage;
    const endIndex = startIndex + transactionsRowsPerPage;

    allTransactionsRows.forEach(row => row.style.display = 'none'); // Hide all rows initially

    // Display only the rows for the current page
    filteredTransactionsRows.slice(startIndex, endIndex).forEach(row => {
      row.style.display = '';
    });

    updateTransactionsPaginationNumbers(totalPages);
  }

  function updateTransactionsPaginationNumbers(totalPages) {
    const paginationNumbers = document.getElementById("transactionsPaginationNumbers");
    paginationNumbers.innerHTML = ''; // Clear existing pagination numbers

    const isMobileView = window.innerWidth <= 768; // Check if the current view is mobile
    const maxVisiblePages = isMobileView ? 3 : 5; // Show 3 pages in mobile view, 5 pages otherwise

    if (totalPages > 1) {
      paginationNumbers.appendChild(createPaginationItem('«', transactionsCurrentPage === 1, () => {
        transactionsCurrentPage = 1;
        updateTransactionsTable();
      }));

      paginationNumbers.appendChild(createPaginationItem('‹', transactionsCurrentPage === 1, () => {
        if (transactionsCurrentPage > 1) {
          transactionsCurrentPage--;
          updateTransactionsTable();
        }
      }));

      let startPage = Math.max(1, transactionsCurrentPage - Math.floor(maxVisiblePages / 2));
      let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

      if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
      }

      for (let i = startPage; i <= endPage; i++) {
        const pageItem = document.createElement("li");
        pageItem.classList.add("page-item");
        if (i === transactionsCurrentPage) {
          pageItem.classList.add("active");
        }

        const pageLink = document.createElement("a");
        pageLink.classList.add("page-link");
        pageLink.textContent = i;
        pageLink.style.cursor = 'pointer';
        pageLink.addEventListener('click', () => {
          transactionsCurrentPage = i;
          updateTransactionsTable();
        });

        pageItem.appendChild(pageLink);
        paginationNumbers.appendChild(pageItem);
      }

      paginationNumbers.appendChild(createPaginationItem('›', transactionsCurrentPage === totalPages, () => {
        if (transactionsCurrentPage < totalPages) {
          transactionsCurrentPage++;
          updateTransactionsTable();
        }
      }));

      paginationNumbers.appendChild(createPaginationItem('»', transactionsCurrentPage === totalPages, () => {
        transactionsCurrentPage = totalPages;
        updateTransactionsTable();
      }));
    } else {
      const singlePageItem = document.createElement("li");
      singlePageItem.classList.add("page-item", "active");
      const singlePageLink = document.createElement("a");
      singlePageLink.classList.add("page-link");
      singlePageLink.textContent = "1";
      singlePageItem.appendChild(singlePageLink);
      paginationNumbers.appendChild(singlePageItem);
    }
  }

  // Add event listener to detect window resize and update pagination accordingly
  window.addEventListener('resize', () => {
    updateTransactionsPaginationNumbers(Math.ceil(filteredTransactionsRows.length / transactionsRowsPerPage) || 1);
  });
</script>
<script>
  // Sorting logic for Transactions Table
  let transactionsSortColumn = -1;
  let transactionsSortAscending = true;

  function sortTransactionsTable(columnIndex) {
    let table = document.getElementById("transactionsTable");
    let rows = Array.from(table.getElementsByTagName("tr")).slice(1); // Skip header row
    transactionsSortAscending = transactionsSortColumn === columnIndex ? !transactionsSortAscending : true; // Toggle sorting direction
    transactionsSortColumn = columnIndex;

    // Determine if the column contains numeric values (e.g., TransactionID, TransactionGroupID, Qty, KGs)
    let isNumericColumn = (columnIndex === 0 || columnIndex === 1 || columnIndex === 5 || columnIndex === 6); // Numeric columns: TransactionID, TransactionGroupID, Qty, KGs

    let sortedRows = rows.sort((a, b) => {
      let aValue = a.getElementsByTagName("td")[columnIndex].innerText.trim();
      let bValue = b.getElementsByTagName("td")[columnIndex].innerText.trim();

      // Sort numerically if it's a numeric column
      if (isNumericColumn) {
        aValue = parseFloat(aValue) || 0;
        bValue = parseFloat(bValue) || 0;
        return transactionsSortAscending ? aValue - bValue : bValue - aValue;
      }

      // Otherwise, sort alphabetically
      return transactionsSortAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
    });

    // Clear and append sorted rows
    let tableBody = document.getElementById("transactionsTableBody");
    tableBody.innerHTML = "";
    sortedRows.forEach(row => tableBody.appendChild(row));

    // Update the sorting icons
    updateTransactionsSortIcons(columnIndex, transactionsSortAscending);
  }

  function updateTransactionsSortIcons(columnIndex, ascending) {
    const headers = document.querySelectorAll("#transactionsTable th");
    headers.forEach((header, index) => {
      header.classList.remove('ascending', 'descending'); // Remove previous sorting class
      if (index === columnIndex) {
        header.classList.add(ascending ? 'ascending' : 'descending'); // Add ascending or descending class
      }
    });
  }
</script>






<!----------- Trucks function ---------------->

<script>
  // Listen for clicks on edit buttons
  document.querySelectorAll('.edit-truck-btn').forEach(button => {
    button.addEventListener('click', function(event) {
      event.preventDefault();

      // Get TruckID from the data-transaction attribute
      const truckId = JSON.parse(this.getAttribute('data-truck')).TruckID;

      // Send an AJAX request to fetch truck data
      fetch(`fetch_truck.php?truckId=${truckId}`)
        .then(response => response.json())
        .then(data => {
          if (!data.error) {
            // Prefill the form with the fetched data
            document.getElementById('truckId').value = data.TruckID;
            document.getElementById('plateNumber').value = data.PlateNo;
            document.getElementById('truckBrand').value = data.TruckBrand;

            // Set the truck status in the dropdown
            const truckStatusDropdown = document.getElementById('truckStatus');
            truckStatusDropdown.value = data.TruckStatus;

            // Open the modal
            const updateModal = new bootstrap.Modal(document.getElementById('updateTrucksRecordModal'));
            updateModal.show();
          } else {
            alert(data.error);
          }
        })
        .catch(error => {
          console.error('Error fetching truck data:', error);
        });
    });
  });
</script>

<script>
  // Sorting logic for Trucks Table
  let trucksSortColumn = -1;
  let trucksSortAscending = true;

  function sortTrucksTable(columnIndex) {
    let table = document.getElementById("trucksTable");
    let rows = Array.from(table.getElementsByTagName("tr")).slice(1); // Skip header row
    trucksSortAscending = trucksSortColumn === columnIndex ? !trucksSortAscending : true; // Toggle sorting direction
    trucksSortColumn = columnIndex;

    // Determine if the column contains numeric values (for sorting numerically)
    let isNumericColumn = columnIndex === 0; // Assume TruckID (columnIndex 0) is numeric

    let sortedRows = rows.sort((a, b) => {
      let aValue = a.getElementsByTagName("td")[columnIndex].innerText.trim();
      let bValue = b.getElementsByTagName("td")[columnIndex].innerText.trim();

      // Sort numerically if it's a numeric column
      if (isNumericColumn) {
        aValue = parseFloat(aValue) || 0;
        bValue = parseFloat(bValue) || 0;
        return trucksSortAscending ? aValue - bValue : bValue - aValue;
      }

      // Otherwise, sort alphabetically
      return trucksSortAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
    });

    // Clear and append sorted rows
    let tableBody = document.getElementById("trucksTableBody");
    tableBody.innerHTML = "";
    sortedRows.forEach(row => tableBody.appendChild(row));

    // Update the sorting icons
    updateTrucksSortIcons(columnIndex, trucksSortAscending);
  }

  function updateTrucksSortIcons(columnIndex, ascending) {
    const headers = document.querySelectorAll("#trucksTable th");
    headers.forEach((header, index) => {
      header.classList.remove('ascending', 'descending'); // Remove previous sorting class
      if (index === columnIndex) {
        header.classList.add(ascending ? 'ascending' : 'descending'); // Add ascending or descending class
      }
    });
  }
</script>
<script>
  function filterTrucksTable() {
    let input = document.getElementById("trucksSearchBar").value.toLowerCase();
    let table = document.getElementById("trucksTable");
    let rows = table.getElementsByTagName("tr");
    let noDataFound = true; // Flag to check if no row matches the search criteria

    for (let i = 1; i < rows.length; i++) { // Skip the header row (i = 1)
      let row = rows[i];
      if (row.id !== "noTrucksDataRow") { // Skip the "No data found" row
        row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";

        if (row.style.display === "") {
          noDataFound = false; // A match was found
        }
      }
    }

    // Toggle the visibility of the "No data found" row
    document.getElementById("noTrucksDataRow").style.display = noDataFound ? "" : "none";
  }
</script>


<!------------- Cluster function --------------->

<script>
  let clustersCurrentPage = 1;
  let clustersRowsPerPage = 5;
  let allClustersRows = [];
  let filteredClustersRows = [];

  document.addEventListener('DOMContentLoaded', () => {
    allClustersRows = Array.from(document.querySelectorAll('#clustersTable tbody tr'));
    filteredClustersRows = [...allClustersRows];
    updateClustersTable();
  });

  function changeClustersRowsPerPage() {
    clustersRowsPerPage = parseInt(document.getElementById("clustersRowsPerPage").value);
    clustersCurrentPage = 1;
    updateClustersTable();
  }

  function filterClustersTable() {
    const input = document.getElementById("clustersSearchBar").value.toLowerCase();
    filteredClustersRows = allClustersRows.filter(row => row.innerText.toLowerCase().includes(input));

    clustersCurrentPage = 1;
    updateClustersTable();

    const noDataRow = document.getElementById("noClustersDataRow");
    if (noDataRow) {
      noDataRow.style.display = filteredClustersRows.length === 0 ? '' : 'none';
    }
    updateClustersPaginationNumbers(Math.ceil(filteredClustersRows.length / clustersRowsPerPage) || 1);
  }

  function updateClustersTable() {
    const totalRows = filteredClustersRows.length;
    const totalPages = Math.ceil(totalRows / clustersRowsPerPage) || 1;
    document.getElementById("totalPagesClusters").textContent = totalPages;

    const startIndex = (clustersCurrentPage - 1) * clustersRowsPerPage;
    const endIndex = startIndex + clustersRowsPerPage;

    allClustersRows.forEach(row => row.style.display = 'none');
    filteredClustersRows.slice(startIndex, endIndex).forEach(row => row.style.display = '');
    updateClustersPaginationNumbers(totalPages);
  }

  function updateClustersPaginationNumbers(totalPages) {
    const paginationNumbers = document.getElementById("clustersPaginationNumbers");
    paginationNumbers.innerHTML = '';

    const maxVisiblePages = window.innerWidth <= 768 ? 3 : 5;
    let startPage = Math.max(1, clustersCurrentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

    if (endPage - startPage < maxVisiblePages - 1) {
      startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    paginationNumbers.appendChild(createPaginationItem('«', clustersCurrentPage === 1, () => {
      clustersCurrentPage = 1;
      updateClustersTable();
    }));

    paginationNumbers.appendChild(createPaginationItem('‹', clustersCurrentPage === 1, () => {
      if (clustersCurrentPage > 1) {
        clustersCurrentPage--;
        updateClustersTable();
      }
    }));

    for (let i = startPage; i <= endPage; i++) {
      const pageItem = document.createElement("li");
      pageItem.classList.add("page-item");
      if (i === clustersCurrentPage) {
        pageItem.classList.add("active");
      }

      const pageLink = document.createElement("a");
      pageLink.classList.add("page-link");
      pageLink.textContent = i;
      pageLink.addEventListener('click', () => {
        clustersCurrentPage = i;
        updateClustersTable();
      });

      pageItem.appendChild(pageLink);
      paginationNumbers.appendChild(pageItem);
    }

    paginationNumbers.appendChild(createPaginationItem('›', clustersCurrentPage === totalPages, () => {
      if (clustersCurrentPage < totalPages) {
        clustersCurrentPage++;
        updateClustersTable();
      }
    }));

    paginationNumbers.appendChild(createPaginationItem('»', clustersCurrentPage === totalPages, () => {
      clustersCurrentPage = totalPages;
      updateClustersTable();
    }));
  }

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
</script>
<script>
  document.querySelectorAll('.edit-cluster-btn').forEach(button => {
    button.addEventListener('click', function(event) {
      event.preventDefault();
      const uniqueClusterId = this.closest('tr').querySelector('td:first-child').textContent; // Assuming UniqueClusterID is in the first cell

      fetch(`fetch_cluster.php?uniqueClusterId=${uniqueClusterId}`)
        .then(response => response.json())
        .then(data => {
          if (!data.error) {
            document.getElementById('uniqueClusterId').value = data.UniqueClusterID;
            document.getElementById('clusterID').value = data.UniqueClusterID;
            document.getElementById('clusterCategory').value = data.ClusterCategory;
            document.getElementById('locationsInCluster').value = data.LocationsInCluster;

            // Set the value for the dropdown
            const tonnerSelect = document.getElementById('tonner');
            const tonnerValue = data.Tonner;
            if (tonnerSelect) {
              tonnerSelect.value = tonnerValue; // Set the dropdown value
              if (tonnerSelect.value !== tonnerValue) {
                console.warn('Tonner value not found in the dropdown. Setting it dynamically:', tonnerValue);
                const newOption = document.createElement('option');
                newOption.value = tonnerValue;
                newOption.textContent = tonnerValue;
                tonnerSelect.appendChild(newOption); // Add the new option to the dropdown
                tonnerSelect.value = tonnerValue; // Set the newly added option as selected
              }
            }

            document.getElementById('kmRadius').value = data.KMRADIUS;
            document.getElementById('fuelPrice').value = data.FuelPrice;
            document.getElementById('rateAmount').value = data.RateAmount;

            const editModal = new bootstrap.Modal(document.getElementById('editClusterModal'));
            editModal.show();
          } else {
            alert(data.error);
          }
        })
        .catch(error => {
          console.error('Error fetching cluster data:', error);
        });
    });
  });
</script>

<script>
  // Handle cluster selection changes
  document.getElementById('clusterSelection').addEventListener('change', function() {
    const selection = this.value;
    document.getElementById('existingClusterFields').style.display = selection === 'existing' ? 'block' : 'none';
    document.getElementById('newClusterFields').style.display = selection === 'new' ? 'block' : 'none';
  });

  // Populate category and locations for existing clusters
  document.getElementById('existingClusterCategory').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    document.getElementById('existingClusterID').value = selectedOption.getAttribute('data-clusterID');
    document.getElementById('existingLocationsInCluster').value = selectedOption.getAttribute('data-locations');
  });

  // Fetch the next available ClusterID for a new cluster
  function fetchNewClusterID() {
    fetch('trucks.php?action=getNextClusterID')
      .then(response => response.json())
      .then(data => {
        console.log("Fetched data:", data); // Debugging: log the response
        if (data.success) {
          document.getElementById('newClusterID').value = data.nextClusterID;
        } else {
          console.error('Failed to fetch the new ClusterID');
        }
      })
      .catch(error => console.error('Error fetching ClusterID:', error));
  }
</script>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    let deleteClusterId = null;

    // Attach event listener to all delete buttons
    document.querySelectorAll(".delete-cluster-btn").forEach(function(button) {
      button.addEventListener("click", function() {
        const row = this.closest("tr");
        deleteClusterId = row.querySelector("td:first-child").textContent.trim(); // Get UniqueClusterID

        // Show SweetAlert2 confirmation dialog
        Swal.fire({
          title: 'Are you sure?',
          text: "Do you want to delete this cluster? This action cannot be undone.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Yes, delete it!',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            // Perform the delete action
            if (deleteClusterId) {
              fetch("delete_cluster.php", {
                  method: "POST",
                  headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                  },
                  body: `UniqueClusterID=${encodeURIComponent(deleteClusterId)}`,
                })
                .then((response) => {
                  if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                  }
                  return response.json(); // Parse JSON response
                })
                .then((data) => {
                  if (data.success) {
                    // Show success SweetAlert2
                    Swal.fire({
                      icon: 'success',
                      title: 'Deleted!',
                      text: data.message || 'The cluster has been deleted successfully.',
                      confirmButtonText: 'OK',
                      timer: 2000, // Automatically close after 2 seconds
                      timerProgressBar: true
                    }).then(() => {
                      window.location.reload(); // Reload the page to reflect changes
                    });
                  } else {
                    // Show error SweetAlert2
                    Swal.fire({
                      icon: 'error',
                      title: 'Error',
                      text: data.message || 'Failed to delete the cluster. Please try again.',
                      confirmButtonText: 'OK'
                    });
                  }
                })
                .catch((error) => {
                  console.error("Error:", error);
                  Swal.fire({
                    icon: 'error',
                    title: 'Request Failed',
                    text: 'An error occurred while processing your request. Please try again later.',
                    confirmButtonText: 'OK'
                  });
                });
            }
          }
        });
      });
    });
  });
</script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Attach the submit event listener to the form
    document.getElementById('addClusterForm').addEventListener('submit', function(e) {
      e.preventDefault(); // Prevent the default form submission

      const formData = new FormData(this);

      // Show loading SweetAlert2
      Swal.fire({
        title: 'Submitting Your Cluster',
        text: 'Please wait while we process your request...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      // Use Fetch API to submit the form via POST request
      fetch('add_cluster.php', {
          method: 'POST',
          body: formData,
        })
        .then((response) => response.json())
        .then((data) => {
          Swal.close(); // Close the loading dialog

          if (data.status === 'error') {
            // Show error SweetAlert2
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'An unexpected error occurred.',
              confirmButtonText: 'OK'
            });
          } else if (data.status === 'success') {
            // Show success SweetAlert2
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: data.message || 'Cluster added successfully.',
              confirmButtonText: 'OK',
              timer: 2000, // Automatically close after 2 seconds
              timerProgressBar: true
            }).then(() => {
              location.reload(); // Reload the page to reflect changes
            });
          }
        })
        .catch((error) => {
          // Handle unexpected errors
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Request Failed',
            text: 'An error occurred while submitting the form. Please try again later.',
            confirmButtonText: 'OK'
          });
        });
    });
  });
</script>



<!----------- Theme function -------------->

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



<!--------- CSS/STYLE --------->

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



<?php
$conn->close();
include '../officer/footer.php';
?>