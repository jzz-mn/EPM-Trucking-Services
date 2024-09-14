<?php
include '../includes/header.php';
?>

<div class="body-wrapper">
  <div class="container-fluid">
    <div class="card card-body py-3">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="d-sm-flex align-items-center justify-space-between">
            <h4 class="mb-4 mb-sm-0 card-title">Employees</h4>
            <nav aria-label="breadcrumb" class="ms-auto">
              <ol class="breadcrumb">
                <li class="breadcrumb-item d-flex align-items-center">
                  <a class="text-muted text-decoration-none d-flex" href="./">
                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                  </a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                  <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                    Employees
                  </span>
                </li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <div class="widget-content searchable-container list">
      <div class="card card-body">
        <div class="row">
          <div class="col-md-4 col-xl-3">
            <form class="position-relative">
              <input type="text" class="form-control product-search ps-5" id="input-search"
                placeholder="Search Contacts..." />
              <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
            </form>
          </div>
          <div class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
            <div class="action-btn show-btn">
              <a href="javascript:void(0)"
                class="delete-multiple bg-danger-subtle btn me-2 text-danger d-flex align-items-center ">
                <i class="ti ti-trash me-1 fs-5"></i> Delete All Row
              </a>
            </div>
            <a href="javascript:void(0)" id="btn-add-contact" class="btn btn-primary d-flex align-items-center">
              <i class="ti ti-users text-white me-1 fs-5"></i> Add Contact
            </a>
          </div>
        </div>
      </div>
      <!-- Modal -->
      <div class="modal fade" id="addContactModal" tabindex="-1" role="dialog" aria-labelledby="addContactModalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header d-flex align-items-center">
              <h5 class="modal-title">Contact</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="add-contact-box">
                <div class="add-contact-content">
                  <form id="addContactModalTitle">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="mb-3 contact-name">
                          <input type="text" id="c-name" class="form-control" placeholder="Name" />
                          <span class="validation-text text-danger"></span>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="mb-3 contact-email">
                          <input type="text" id="c-email" class="form-control" placeholder="Email" />
                          <span class="validation-text text-danger"></span>
                        </div>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="mb-3 contact-occupation">
                          <input type="text" id="c-occupation" class="form-control" placeholder="Occupation" />
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="mb-3 contact-phone">
                          <input type="text" id="c-phone" class="form-control" placeholder="Phone" />
                          <span class="validation-text text-danger"></span>
                        </div>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-12">
                        <div class="mb-3 contact-location">
                          <input type="text" id="c-location" class="form-control" placeholder="Location" />
                        </div>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <div class="d-flex gap-6 m-0">
                <button id="btn-add" class="btn btn-success">Add</button>
                <button id="btn-edit" class="btn btn-success">Save</button>
                <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal"> Discard
                </button>
              </div>

            </div>
          </div>
        </div>
      </div>
      <?php
      include '../includes/db_connection.php';

      // Fetch employee data from database
      $sql = "SELECT EmployeeID, FirstName, MiddleInitial, LastName, Gender, Position, DateOfBirth, Address, MobileNo, EmailAddress, EmploymentDate FROM employees";
      $result = $conn->query($sql);
      ?>

      <div class="table-responsive">
        <table id="file_export" class="table w-100 table-striped table-bordered display text-nowrap">
          <thead>
            <tr>
              <th>Name</th>
              <th>Position</th>
              <th>Address</th>
              <th>Mobile No</th>
              <th>Email Address</th>
              <th>Employment Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                $fullName = "{$row['FirstName']} {$row['MiddleInitial']} {$row['LastName']}";
                $positionBadge = '';
                if ($row['Position'] === 'Driver') {
                  $positionBadge = "<span class='badge text-bg-primary'>Driver</span>";
                } elseif ($row['Position'] === 'Helper/Crew') {
                  $positionBadge = "<span class='badge text-bg-danger'>Helper/Crew</span>";
                } else {
                  $positionBadge = "<span class='badge text-bg-secondary'>{$row['Position']}</span>";
                }
                $formattedMobileNo = preg_replace('/(\d{4})(\d{3})(\d{4})/', '$1-$2-$3', $row['MobileNo']);
                echo "<tr>";
                echo "<td><div class='d-flex align-items-center'>";
                echo "<img src='../assets/images/profile/user-1.jpg' class='rounded-circle' width='40' height='40' />";
                echo "<div class='ms-3'>";
                echo "<h6 class='fs-4 fw-semibold mb-0'>{$fullName}</h6>";
                echo "</div></div></td>";
                echo "<td>{$positionBadge}</td>";
                echo "<td><p class='mb-0 fw-normal'>{$row['Address']}</p></td>";
                echo "<td><p class='mb-0 fw-normal'>{$formattedMobileNo}</p></td>";
                echo "<td><p class='mb-0 fw-normal'>{$row['EmailAddress']}</p></td>";
                echo "<td><p class='mb-0 fw-normal'>{$row['EmploymentDate']}</p></td>";
                echo "<td>";
                echo "<div class='dropdown dropstart'>";
                echo "<a href='javascript:void(0)' class='text-muted' id='dropdownMenuButton{$row['EmployeeID']}' data-bs-toggle='dropdown' aria-expanded='false'>";
                echo "<i class='ti ti-dots fs-5'></i></a>";
                echo "<ul class='dropdown-menu' aria-labelledby='dropdownMenuButton{$row['EmployeeID']}'>";
                echo "<li><a class='dropdown-item d-flex align-items-center gap-3' href='acc-setting.php?id={$row['EmployeeID']}'><i class='fs-4 ti ti-edit'></i>Edit</a></li>";
                echo "<li><a class='dropdown-item d-flex align-items-center gap-3' href='../includes/delete_employee.php?id={$row['EmployeeID']}'><i class='fs-4 ti ti-trash'></i>Delete</a></li>";
                echo "</ul></div></td>";
                echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='7' class='text-center'>No employees found</td></tr>";
            }
            $conn->close();
            ?>
          </tbody>
        </table>
      </div>

      <script>
        $(document).ready(function () {
          $('#file_export').DataTable(); // Initialize DataTables
        });
      </script>

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
<!-- Import Js Files -->
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/libs/simplebar/dist/simplebar.min.js"></script>
<script src="../assets/js/theme/app.init.js"></script>
<script src="../assets/js/theme/theme.js"></script>
<script src="../assets/js/theme/app.min.js"></script>
<script src="../assets/js/theme/sidebarmenu-default.js"></script>

<!-- solar icons -->
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
</body>

</html>