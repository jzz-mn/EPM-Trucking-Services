<?php
session_start();
include '../employee/header.php';
?>

<div class="body-wrapper">
  <div class="container-fluid">
    <div class="card card-body py-3">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="d-sm-flex align-items-center justify-space-between">
            <h4 class="mb-4 mb-sm-0 card-title">Maintenance</h4>
            <!-- Removed Sidebar -->
            <nav aria-label="breadcrumb" class="ms-auto">
              <ol class="breadcrumb">
                <li class="breadcrumb-item d-flex align-items-center">
                  <a class="text-muted text-decoration-none d-flex" href="../employee/home.php">
                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                  </a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                  <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                    Trucks
                  </span>
                </li>
              </ol>
            </nav><!-- Sidebar End -->
          </div>
        </div>
      </div>
    </div>

    <div class="widget-content searchable-container list">
      <?php
      // Include your database connection file
      include '../includes/db_connection.php';

      // Query to get the last Maintenance ID
      $query = "SELECT MAX(MaintenanceID) AS lastMaintenanceID FROM truckmaintenance";
      $result = $conn->query($query);

      // Initialize the next Maintenance ID
      $nextMaintenanceID = 1; // Default to 1 if there are no records

      if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nextMaintenanceID = $row['lastMaintenanceID'] + 1;
      }
      ?>

      <!-- Add Maintenance Modal -->
      <div class="modal fade" id="addMaintenanceRecordModal" tabindex="-1" role="dialog" aria-labelledby="addMaintenanceRecordModalTitle" aria-hidden="true">
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
                              <input type="text" class="form-control" id="maintenanceId" name="maintenanceId" value="<?php echo $nextMaintenanceID; ?>" readonly>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="maintenanceDate" class="form-label">Date</label>
                              <input type="date" class="form-control" id="maintenanceDate" name="maintenanceDate" placeholder="Select Date" required>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="maintenanceCategory" class="form-label">Category</label>
                              <select class="form-control" id="maintenanceCategory" name="maintenanceCategory" required>
                                <option value="COOL AIR MAINTENANCE">MAINTENANCE</option>
                                <option value="LEGALIZATION FEE">LEGALIZATION FEE</option>
                                <option value="OFFICE FEE">OFFICE FEE</option>
                              </select>
                            </div>
                          </div>
                          <div class="col-lg-12">
                            <div class="mb-3">
                              <label for="maintenanceDescription" class="form-label">Description</label>
                              <input type="text" class="form-control" id="maintenanceDescription" name="maintenanceDescription" placeholder="Enter Description" required>
                            </div>
                          </div>
                          <div class="col-12">
                            <div class="d-flex align-items-center justify-content-end mt-4 gap-6">
                              <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
                              <!-- Trigger the confirmation modal on click -->
                              <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmationModal" onclick="reviewData()">Save</button>
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
              <p class="mt-3 text-danger">Are you sure you want to submit this record? You will not be allowed to make changes once submitted.</p>
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
          <div class>

            <!-- Tab panes -->
            <div class="tab-content p-4">
              <div class="tab-pane active" id="home" role="tabpanel">
                <div class="row mt-3">
                  <div class="col-md-4 col-xl-3">
                    <form class="position-relative">
                      <input type="text" class="form-control product-search" id="input-search" placeholder="Search" />
                      <i class="ti position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                    </form>
                  </div>
                  <div
                    class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
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
                  $query = "SELECT MaintenanceID, Year, Month, Category, Description FROM truckmaintenance";
                  $result = $conn->query($query);
                  ?>
                  <div class="table-responsive">
                    <table id="" class="table table-striped table-bordered display text-nowrap">
                      <thead>
                        <!-- start row -->
                        <tr>
                          <th>Maintenance ID</th>
                          <th>Year</th>
                          <th>Month</th>
                          <th>Category</th>
                          <th>Description</th>
                        </tr>
                        <!-- end row -->
                      </thead>
                      <tbody>
                        <?php
                        // Check if the query returned any results
                        if ($result->num_rows > 0) {
                          // Output data of each row
                          while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['MaintenanceID'] . "</td>";
                            echo "<td>" . $row['Year'] . "</td>";
                            echo "<td>" . $row['Month'] . "</td>";
                            echo "<td>" . $row['Category'] . "</td>";
                            echo "<td>" . $row['Description'] . "</td>";
                            echo "<td>";

                            echo "</tr>";
                          }
                        } else {
                          echo "<tr><td colspan='5'>No records found</td></tr>";
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
</div>

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


</div>
<div class="dark-transparent sidebartoggler"></div>
<script src="../assets/js/vendor.min.js"></script>
<!-- Import Js Files -->
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/libs/simplebar/dist/simplebar.min.js"></script>
<script src="../assets/js/theme/app.init.js"></script>
<script src="../assets/js/theme/theme.js"></script>
<script src="../assets/js/theme/app.min.js"></script>
<script src="../assets/js/theme/sidebarmenu-default.js"></script>

<!-- solar icons -->
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
<script src="../assets/libs/fullcalendar/index.global.min.js"></script>
<script src="../assets/js/apps/contact.js"></script>

</body>

</html>