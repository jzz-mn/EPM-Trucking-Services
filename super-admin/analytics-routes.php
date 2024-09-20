<?php
session_start();
include '../includes/header.php';
include '../includes/db_connection.php';
?>
    
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
<div class="body-wrapper">
  <div class="container-fluid">
    <div class="card card-body py-3">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="d-sm-flex align-items-center justify-space-between">
            <h4 class="mb-4 mb-sm-0 card-title">Analytics</h4>
            <nav aria-label="breadcrumb" class="ms-auto">
              <ol class="breadcrumb">
                <li class="breadcrumb-item d-flex align-items-center">
                  <a class="text-muted text-decoration-none d-flex" href="./">
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

    <div class="body-wrapper">
      <div class="container-fluid p-0">
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
            <div class="card card-body">
              <form class="position-relative">
                <input type="text" class="form-control product-search ps-5 w-25" id="input-search" placeholder="Search" />
                <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
              </form>

              <div class="table-responsive">
                <table class="table search-table align-middle text-nowrap">
                  <thead class="header-item">
                    <tr>
                      <th>
                        <div class="n-chk align-self-center text-center">
                          <div class="form-check">
                            <span class="new-control-indicator"></span>
                          </div>
                        </div>
                      </th>
                      <th>Transaction ID</th>
                      <th>Date</th>
                      <th>Plate Number</th>
                      <th>DR Number</th>
                      <th>Quantity (Qty)</th>
                      <th>Weight (Kgs)</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php

                    $items_per_page = isset($_GET['items_per_page']) ? (int)$_GET['items_per_page'] : 5;
                    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $offset = ($current_page - 1) * $items_per_page;

                    // Get total number of rows in the transaction table
                    $total_rows_query = "SELECT COUNT(*) as total FROM transactions";
                    $total_rows_result = $conn->query($total_rows_query);
                    $total_rows = $total_rows_result->fetch_assoc()['total'];

                    // Calculate total pages
                    $total_pages = ceil($total_rows / $items_per_page);

                    // Fetch data with pagination
                    $sql = "SELECT TransactionID, Date, PlateNumber, DRNumber, Qty, Kgs FROM transactions LIMIT $offset, $items_per_page";
                    $result = $conn->query($sql);
                    ?>

                    
                          <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                              while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td><input type='checkbox' class='form-check-input' id='checkbox{$row['TransactionID']}' /></td>";
                                echo "<td>{$row['TransactionID']}</td>";
                                echo "<td>{$row['Date']}</td>";
                                echo "<td>{$row['PlateNumber']}</td>";
                                echo "<td>{$row['DRNumber']}</td>";
                                echo "<td>{$row['Qty']}</td>";
                                echo "<td>{$row['Kgs']}</td>";
                                echo "<td>
                        <div class='d-flex justify-content-center'>
                            <a href='edit.php?id={$row['TransactionID']}' class='btn btn-outline-primary btn-sm me-2'><i class='bi bi-pencil'></i></a>
                            <a href='delete.php?id={$row['TransactionID']}' class='btn btn-outline-danger btn-sm'><i class='bi bi-trash'></i></a>
                        </div>
                        </td>";
                                echo "</tr>";
                              }
                            } else {
                              echo "<tr><td colspan='8' class='text-center'>No transactions found</td></tr>";
                            }
                            ?>
                          </tbody>
                        </table>
                      </div>

                      <!-- Pagination -->
                      <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                          <label for="items-per-page" class="me-2">Items per page:</label>
                          <select class="form-select w-auto d-inline" id="items-per-page" onchange="location = this.value;">
                            <option value="?items_per_page=5&page=1" <?php if ($items_per_page == 5) echo 'selected'; ?>>5</option>
                            <option value="?items_per_page=10&page=1" <?php if ($items_per_page == 10) echo 'selected'; ?>>10</option>
                            <option value="?items_per_page=15&page=1" <?php if ($items_per_page == 15) echo 'selected'; ?>>15</option>
                          </select>
                        </div>

                        <nav>
                          <ul class="pagination mb-0">
                            <li class="page-item <?php if ($current_page <= 1) echo 'disabled'; ?>">
                              <a class="page-link" href="?items_per_page=<?php echo $items_per_page; ?>&page=<?php echo max(1, $current_page - 1); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                              <li class="page-item <?php if ($i == $current_page) echo 'active'; ?>">
                                <a class="page-link" href="?items_per_page=<?php echo $items_per_page; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                              </li>
                            <?php endfor; ?>
                            <li class="page-item <?php if ($current_page >= $total_pages) echo 'disabled'; ?>">
                              <a class="page-link" href="?items_per_page=<?php echo $items_per_page; ?>&page=<?php echo min($total_pages, $current_page + 1); ?>">Next</a>
                            </li>
                          </ul>
                        </nav>
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