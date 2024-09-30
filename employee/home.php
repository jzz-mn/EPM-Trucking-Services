<?php
session_start();
include '../employee/header.php';
include '../includes/db_connection.php';
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
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
                      <span class="round-48 d-flex align-items-center justify-content-center rounded bg-secondary-subtle">
                        <iconify-icon icon="solar:football-outline" class="fs-7 text-secondary"> </iconify-icon>
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
                      <span class="round-48 d-flex align-items-center justify-content-center rounded bg-secondary-subtle">
                        <iconify-icon icon="solar:football-outline" class="fs-7 text-secondary"> </iconify-icon>
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

        <div class="d-flex flex-row flex-wrap gap-3 customizer-box color-pallete" role="group">
          <input type="radio" class="btn-check" name="color-theme-layout" id="Blue_Theme" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
            onclick="handleColorTheme('Blue_Theme')" for="Blue_Theme" data-bs-toggle="tooltip" data-bs-placement="top"
            data-bs-title="BLUE_THEME">
            <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-1">
              <i class="ti ti-check text-white d-flex icon fs-5"></i>
            </div>
          </label>

          <input type="radio" class="btn-check" name="color-theme-layout" id="Aqua_Theme" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
            onclick="handleColorTheme('Aqua_Theme')" for="Aqua_Theme" data-bs-toggle="tooltip" data-bs-placement="top"
            data-bs-title="AQUA_THEME">
            <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-2">
              <i class="ti ti-check text-white d-flex icon fs-5"></i>
            </div>
          </label>

          <input type="radio" class="btn-check" name="color-theme-layout" id="Purple_Theme" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
            onclick="handleColorTheme('Purple_Theme')" for="Purple_Theme" data-bs-toggle="tooltip" data-bs-placement="top"
            data-bs-title="PURPLE_THEME">
            <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-3">
              <i class="ti ti-check text-white d-flex icon fs-5"></i>
            </div>
          </label>

          <input type="radio" class="btn-check" name="color-theme-layout" id="green-theme-layout" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
            onclick="handleColorTheme('Green_Theme')" for="green-theme-layout" data-bs-toggle="tooltip"
            data-bs-placement="top" data-bs-title="GREEN_THEME">
            <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-4">
              <i class="ti ti-check text-white d-flex icon fs-5"></i>
            </div>
          </label>

          <input type="radio" class="btn-check" name="color-theme-layout" id="cyan-theme-layout" autocomplete="off" />
          <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center"
            onclick="handleColorTheme('Cyan_Theme')" for="cyan-theme-layout" data-bs-toggle="tooltip"
            data-bs-placement="top" data-bs-title="CYAN_THEME">
            <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-5">
              <i class="ti ti-check text-white d-flex icon fs-5"></i>
            </div>
          </label>

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
<script src="../assets/libs/owl.carousel/dist/owl.carousel.min.js"></script>
<script src="../assets/js/apps/productDetail.js"></script>
</body>

</html>