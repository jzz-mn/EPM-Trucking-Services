<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
<div class="body-wrapper">
  <div class="container-fluid">
    <?php
    // Including sidebar if it exists
    $sidebar_path = '../officer/sidebar.php';
    if (file_exists($sidebar_path)) {
        include $sidebar_path;
    } else {
        echo "<!-- Sidebar not found at $sidebar_path -->";
    }
    ?>
    <div class="card card-body py-3">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-4 mb-sm-0 card-title">Analytics</h4>
            <nav aria-label="breadcrumb" class="ms-auto">
              <ol class="breadcrumb">
                <li class="breadcrumb-item d-flex align-items-center">
                  <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                  </a>
                </li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>
    <h5 class="border-bottom py-2 px-4 mb-4">Routes</h5>

    <!-- Main content starts here -->
    <div class="body-wrapper">
      <div class="container-fluid p-0">
        <div class="row">
          <div class="col-lg-4">
            <div class="row">
              <!-- New Customers -->
              <div class="col-md-6 col-lg-12">
                <div class="card">
                  <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-6 mb-4 pb-9">
                      <span class="round-48 d-flex align-items-center justify-content-center rounded bg-secondary-subtle">
                        <iconify-icon icon="solar:football-outline" class="fs-7 text-secondary"></iconify-icon>
                      </span>
                      <h6 class="mb-0 fs-4 fw-medium">New Customers</h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-6">
                      <h6 class="mb-0 fw-medium">New goals</h6>
                      <h6 class="mb-0 fw-medium">83%</h6>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Basic example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
                      <div class="progress-bar bg-secondary" style="width: 83%"></div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Total Income -->
              <div class="col-md-6 col-lg-12">
                <div class="card">
                  <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-6 mb-4">
                      <span class="round-48 d-flex align-items-center justify-content-center rounded bg-danger-subtle">
                        <iconify-icon icon="solar:box-linear" class="fs-7 text-danger"></iconify-icon>
                      </span>
                      <h6 class="mb-0 fs-4 fw-medium">Total Income</h6>
                    </div>
                    <div class="row">
                      <div class="col-6">
                        <h4 class="fs-7">$680</h4>
                        <span class="fs-11 text-success fw-semibold">+18%</span>
                      </div>
                      <div class="col-6">
                        <div id="total-income"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Weekly Schedules -->
          <div class="col-lg-8">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Weekly Schedules</h5>
                <div style="height: 300px;">
                  <div id="weekly-schedules" class="rounded-pill-bars"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Main content ends here -->

  </div>
</div>

<script src="../assets/js/vendor.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/libs/simplebar/dist/simplebar.min.js"></script>
<script src="../assets/js/theme/app.init.js"></script>
<script src="../assets/js/theme/theme.js"></script>
<script src="../assets/js/theme/app.min.js"></script>
<script src="../assets/js/theme/sidebarmenu-default.js"></script>
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
<script src="../assets/libs/owl.carousel/dist/owl.carousel.min.js"></script>
<script src="../assets/js/apps/productDetail.js"></script>
</body>
</html>
