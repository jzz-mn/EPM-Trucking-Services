<?php
session_start();
include '../officer/header.php';
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
                  <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
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
          <table class="table table-striped table-bordered text-nowrap align-middle text-center">
            <thead class="header-item align-self-center text-center">
              <tr>
                <th class="sortable" data-sort="logid">Log ID</th>
                <th class="sortable" data-sort="username">Username</th>
                <th class="sortable" data-sort="role">Role</th>
                <th class="sortable" data-sort="">Action</th>
                <th class="sortable" data-sort="timestamp">Timestamp</th>
              </tr>
            </thead>
            <tbody id="activityLogsbody">
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
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('input-search');
    const tableRows = document.querySelectorAll('#activityLogsbody tr');

    searchInput.addEventListener('input', function () {
      const searchValue = searchInput.value.toLowerCase();

      tableRows.forEach(row => {
        const logid = row.getAttribute('data-name').toLowerCase();
        const position = row.getAttribute('data-position').toLowerCase();
        const status = row.getAttribute('data-status').toLowerCase();

        // Check if search value is part of the name, position, or activation status
        if (name.includes(searchValue) || position.includes(searchValue) || status.includes(searchValue)) {
          row.style.display = ''; // Show the row
        } else {
          row.style.display = 'none'; // Hide the row
        }
      });
    });
  });
</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('input-search');
    const tableRows = document.querySelectorAll('#activityLogsbody tr');
    const table = document.getElementById('activityLogsbody');

    // Sorting
    const headers = document.querySelectorAll('.sortable');
    let currentSortColumn = '';
    let isAscending = true;

    // Function to compare values for sorting
    const compareValues = (a, b, column, ascending) => {
      const valA = a.getAttribute(`data-${column}`).toLowerCase();
      const valB = b.getAttribute(`data-${column}`).toLowerCase();

      if (valA < valB) return ascending ? -1 : 1;
      if (valA > valB) return ascending ? 1 : -1;
      return 0;
    };

    // Function to sort the table rows
    const sortTable = (column) => {
      const rowsArray = Array.from(tableRows);
      rowsArray.sort((a, b) => compareValues(a, b, column, isAscending));
      rowsArray.forEach(row => table.appendChild(row)); // Re-attach sorted rows to the table
    };

    // Add click event listener to each sortable header
    headers.forEach(header => {
      header.addEventListener('click', function () {
        const column = this.getAttribute('data-sort');

        // Toggle sorting order if clicking on the same column
        if (currentSortColumn === column) {
          isAscending = !isAscending;
        } else {
          currentSortColumn = column;
          isAscending = true;
        }

        // Sort the table based on the clicked column
        sortTable(column);

        // Optionally, update the header to show the sorting order
        headers.forEach(h => h.classList.remove('ascending', 'descending'));
        this.classList.add(isAscending ? 'ascending' : 'descending');
      });
    });

    // Search functionality
    searchInput.addEventListener('input', function () {
      const searchValue = searchInput.value.toLowerCase();

      tableRows.forEach(row => {
        const name = row.getAttribute('data-name').toLowerCase();
        const position = row.getAttribute('data-position').toLowerCase();
        const status = row.getAttribute('data-status').toLowerCase();

        // Check if search value is part of the name, position, or activation status
        if (name.includes(searchValue) || position.includes(searchValue) || status.includes(searchValue)) {
          row.style.display = ''; // Show the row
        } else {
          row.style.display = 'none'; // Hide the row
        }
      });
    });
  });
</script>

<style>
  .sortable {
    cursor: pointer;
  }

  .ascending::after {
    content: ' ↑';
  }

  .descending::after {
    content: ' ↓';
  }
</style>
<?php
include '../officer/footer.php';
?>