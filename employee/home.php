<?php
session_start();
include '../employee/header.php';
include '../includes/db_connection.php';

// Check if the user is logged in (redundant if already handled in header.php)
if (!isset($_SESSION['UserID'])) {
  header('Location: ../index.php');
  exit();
}

?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css"
  rel="stylesheet">
<div class="body-wrapper m-0 p-0">
  <div class="container-fluid">


    <div class="body-wrapper">
      <div class="container-fluid p-0 m-0">
        <div class="row">
          <!-- First Column: Welcome Back, Customers, Projects -->
          <div class="col-lg-6">
            <!-- Welcome Back Card -->
            <div class="card text-white bg-primary-gt overflow-hidden">
              <div class="card-body position-relative z-1">
                <span class="badge badge-custom-dark d-inline-flex align-items-center gap-2 fs-3">
                  <iconify-icon icon="solar:check-circle-outline" class="fs-5"></iconify-icon>
                  <span class="fw-normal">This month <span class="fw-semibold">+15%
                      Profit</span>
                  </span>
                </span>
                <h4 class="text-white fw-normal mt-5 pt-7 mb-1">Hey, <span class="fw-bolder">David
                    McMichael</span>
                </h4>
                <h6 class="opacity-75 fw-normal text-white mb-0">Aenean vel libero id metus
                  sollicitudin</span>
              </div>
            </div>

            <!-- Customers and Projects -->
            <div class="row">
              <!-- Customers Card -->
              <div class="col-lg-6 mt-0 mb-4">
                <div class="card">
                  <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-6 mb-4 pb-9">
                      <span
                        class="round-48 d-flex align-items-center justify-content-center rounded bg-secondary-subtle">
                        <iconify-icon icon="solar:football-outline" class="fs-7 text-secondary"> </iconify-icon>
                      </span>
                      <h6 class="mb-0 fs-4 fw-medium">New Customers</h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-6">
                      <h6 class="mb-0 fw-medium">New goals</h6>
                      <h6 class="mb-0 fw-medium">83%</h6>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Basic example" aria-valuenow="25"
                      aria-valuemin="0" aria-valuemax="100">
                      <div class="progress-bar bg-secondary" style="width: 83%"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Projects Card -->
              <div class="col-lg-6 mb-4">
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

          <!-- Second Column: Revenue Forecast -->
          <div class="col-lg-6">
            <div class="card">
              <div class="card-body">
                <div class="d-md-flex align-items-center justify-content-between">
                  <div>
                    <h5 class="card-title">Revenue Forecast</h5>
                    <p class="card-subtitle mb-0">Overview of Profit</p>
                  </div>

                  <div class="hstack gap-3">
                    <div class="d-flex align-items-center gap-2">
                      <span class="rounded-circle bg-primary" style="width: 12px; height: 12px;"></span>
                      <span class="text-muted">2024</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <span class="rounded-circle bg-danger" style="width: 12px; height: 12px;"></span>
                      <span class="text-muted">2023</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <span class="rounded-circle bg-success" style="width: 12px; height: 12px;"></span>
                      <span class="text-muted">2022</span>
                    </div>
                  </div>
                </div>


                <!-- Summary -->
                <div class="row mt-4">
                  <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3">
                      <span class="bg-light rounded-circle p-3">
                        <i class="bi bi-pie-chart text-dark"></i>
                      </span>
                      <div>
                        <p class="mb-0">Total</p>
                        <h5 class="fw-medium">$96,640</h5>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3">
                      <span class="bg-primary-subtle rounded-circle p-3">
                        <i class="bi bi-currency-dollar text-primary"></i>
                      </span>
                      <div>
                        <p class="mb-0">Profit</p>
                        <h5 class="fw-medium">$48,820</h5>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3">
                      <span class="bg-danger-subtle rounded-circle p-3">
                        <i class="bi bi-wallet2 text-danger"></i>
                      </span>
                      <div>
                        <p class="mb-0">Earnings</p>
                        <h5 class="fw-medium">$48,820</h5>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="body-wrapper p-0 m-0">
      <div class="container-fluid p-0 m-0">
        <div class="row">
          <div class="col-lg-4">
            <div class="row">
              <!-- ----------------------------------------- -->
              <!-- New Customers -->
              <!-- ----------------------------------------- -->
              <div class="col-md-6 col-lg-12">
                <div class="card">
                  <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-6 mb-4 pb-9">
                      <span
                        class="round-48 d-flex align-items-center justify-content-center rounded bg-secondary-subtle">
                        <iconify-icon icon="solar:football-outline" class="fs-7 text-secondary"> </iconify-icon>
                      </span>
                      <h6 class="mb-0 fs-4 fw-medium">New Customers</h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-6">
                      <h6 class="mb-0 fw-medium">New goals</h6>
                      <h6 class="mb-0 fw-medium">83%</h6>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Basic example" aria-valuenow="25"
                      aria-valuemin="0" aria-valuemax="100">
                      <div class="progress-bar bg-secondary" style="width: 83%"></div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- ----------------------------------------- -->
              <!-- Total Income -->
              <!-- ----------------------------------------- -->
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
          <!-- ----------------------------------------- -->
          <!-- Weekly Schedules -->
          <!-- ----------------------------------------- -->
          <div class="col-lg-8">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Weekly Scheduels</h5>
                <div style="height: 300px;">
                  <div id="weekly-schedules" class="rounded-pill-bars"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
    include '../employee/footer.php';
    ?>