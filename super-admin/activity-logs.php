<?php
session_start();
include '../super-admin/header.php';
include '../includes/db_connection.php'
  ?>
<div class="body-wrapper">
  <div class="container-fluid">
    <div class="card card-body py-3">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="d-sm-flex align-items-center justify-space-between">
            <h4 class="mb-4 mb-sm-0 card-title">Activity Logs</h4>
            <nav aria-label="breadcrumb" class="ms-auto">
              <ol class="breadcrumb">
                <li class="breadcrumb-item d-flex align-items-center">
                  <a class="text-muted text-decoration-none d-flex" href="../super-admin/home.php">
                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                  </a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                  <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                    Activity Logs
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
              <input type="text" class="form-control product-search ps-5" id="input-search" placeholder="Search" />
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
          </div>
        </div>
      </div>

      <div class="card card-body">
        <div class="table-responsive">
          <table class="table search-table align-middle text-nowrap">
            <thead class="header-item align-self-center text-center">
              <tr>
                <th>Log ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Action</th>
                <th>Timestamp</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Original SQL query with Role
              $sql = "SELECT activitylogs.LogID, useraccounts.Username, useraccounts.Role, activitylogs.Action, activitylogs.TimeStamp 
            FROM activitylogs 
            INNER JOIN useraccounts ON activitylogs.UserID = useraccounts.UserID
            ORDER BY LogID DESC";

              $result = $conn->query($sql);

              if (!$result) {
                die("Query failed: " . $conn->error);
              }

              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  echo "<tr class='align-self-center text-center'>";
                  echo "<td>{$row['LogID']}</td>";
                  echo "<td>{$row['Username']}</td>";
                  echo "<td>{$row['Role']}</td>";
                  echo "<td>{$row['Action']}</td>";
                  echo "<td>{$row['TimeStamp']}</td>";
                  echo "</tr>";
                }
              } else {
                // Updated colspan to 5 to match the number of columns
                echo "<tr><td colspan='5' class='text-center'>No activity logs found</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <script>
        $(document).ready(function () {
          $('.search-table').DataTable(); // Initialize DataTables
        });
      </script>

    </div>
  </div>
</div>
<?php
include '../super-admin/footer.php';
?>