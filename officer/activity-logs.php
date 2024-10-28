<?php
session_start();
include '../officer/header.php';
if ($_SESSION['Role'] !== 'SuperAdmin') {
  echo "Access denied. This page is only accessible to SuperAdmin.";
  exit();
}
include '../includes/db_connection.php';
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
            <select id="rowsPerPage" class="form-select w-auto d-inline">
              <option value="5">5 rows</option>
              <option value="10">10 rows</option>
              <option value="20">20 rows</option>
            </select>
          </div>
        </div>
      </div>

      <div class="card card-body">
        <div class="table-responsive">
          <table class="table table-striped table-bordered text-nowrap align-middle text-center" id="activityLogsTable">
            <thead class="header-item align-self-center text-center">
              <tr>
                <th class="sortable" onclick="sortTable(0, true)" style="width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Log ID</th>
                <th class="sortable" onclick="sortTable(1)" style="width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Username</th>
                <th class="sortable" onclick="sortTable(2)" style="width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Role</th>
                <th class="sortable" onclick="sortTable(3)" style="width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Action</th>
                <th class="sortable" onclick="sortTable(4)" style="width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Timestamp</th>
              </tr>
            </thead>
            <tbody id="activityLogsbody">
              <?php
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
                  echo "<tr>";
                  echo "<td style='width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'>{$row['LogID']}</td>";
                  echo "<td style='width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'>{$row['Username']}</td>";
                  echo "<td style='width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'>{$row['Role']}</td>";
                  echo "<td style='width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'>{$row['Action']}</td>";
                  echo "<td style='width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'>{$row['TimeStamp']}</td>";
                  echo "</tr>";
                }
              } else {
                echo "<tr><td colspan='5' class='text-center'>No activity logs found</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
        <div class="pagination-controls d-flex justify-content-end align-items-center mt-3">
          <button id="prevBtn" class="btn btn-primary me-2" onclick="prevPage()">Previous</button>
          <nav>
            <ul class="pagination mb-0" id="paginationNumbers"></ul>
          </nav>
          <button id="nextBtn" class="btn btn-primary ms-2" onclick="nextPage()">Next</button>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
  let currentSortColumn = -1;
  let isAscending = true;

  function sortTable(columnIndex, isNumeric = false) {
    const table = document.getElementById("activityLogsTable");
    const rows = Array.from(table.getElementsByTagName("tr")).slice(1); // Skip header row

    isAscending = currentSortColumn === columnIndex ? !isAscending : true;
    currentSortColumn = columnIndex;

    const sortedRows = rows.sort((a, b) => {
      let aValue = a.getElementsByTagName("td")[columnIndex].innerText.trim();
      let bValue = b.getElementsByTagName("td")[columnIndex].innerText.trim();

      // Handle numeric sorting
      if (isNumeric) {
        aValue = parseFloat(aValue);
        bValue = parseFloat(bValue);
      }

      return isAscending ? (aValue > bValue ? 1 : -1) : (aValue < bValue ? 1 : -1);
    });

    const tableBody = document.getElementById("activityLogsbody");
    tableBody.innerHTML = "";
    sortedRows.forEach(row => tableBody.appendChild(row));

    updateTable(); // Update table for pagination after sorting
    updateSortIcons(columnIndex);
  }

  // Add sorting arrows next to the column header
  function updateSortIcons(columnIndex) {
    const headers = document.querySelectorAll(".sortable");
    headers.forEach((header, index) => {
      header.classList.remove('ascending', 'descending');
      if (index === columnIndex) {
        header.classList.add(isAscending ? 'ascending' : 'descending');
      }
    });
  }

  // Pagination logic (remains unchanged)
  let currentPage = 1;
  let rowsPerPage = 5;
  let totalRows = 0;
  let totalPages = 0;
  const maxVisiblePages = 5;
  let filteredRows = []; // Store the filtered rows for pagination

  function updateTable() {
    const table = document.getElementById("activityLogsTable");
    const rows = filteredRows.length ? filteredRows : Array.from(table.getElementsByTagName("tr")).slice(1); // Skip header row
    totalRows = rows.length;
    totalPages = Math.ceil(totalRows / rowsPerPage);

    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;

    rows.forEach((row, index) => {
      row.style.display = index >= startIndex && index < endIndex ? "" : "none";
    });

    document.getElementById("prevBtn").disabled = currentPage === 1;
    document.getElementById("nextBtn").disabled = currentPage === totalPages;

    updatePaginationNumbers();
  }

  function nextPage() {
    if (currentPage < totalPages) {
      currentPage++;
      updateTable();
    }
  }

  function prevPage() {
    if (currentPage > 1) {
      currentPage--;
      updateTable();
    }
  }

  function updatePaginationNumbers() {
    const paginationNumbers = document.getElementById("paginationNumbers");
    paginationNumbers.innerHTML = ""; // Clear existing numbers

    const pagesToShow = [];
    const halfVisible = Math.floor(maxVisiblePages / 2);

    let startPage = Math.max(1, currentPage - halfVisible);
    let endPage = Math.min(totalPages, currentPage + halfVisible);

    if (currentPage - halfVisible < 1) {
      endPage = Math.min(totalPages, endPage + (halfVisible - (currentPage - 1)));
    }

    if (currentPage + halfVisible > totalPages) {
      startPage = Math.max(1, startPage - (currentPage + halfVisible - totalPages));
    }

    if (startPage > 1) {
      pagesToShow.push(1);
      if (startPage > 2) pagesToShow.push('...');
    }

    for (let i = startPage; i <= endPage; i++) {
      pagesToShow.push(i);
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) pagesToShow.push('...');
      pagesToShow.push(totalPages);
    }

    pagesToShow.forEach(page => {
      const pageItem = document.createElement("li");
      pageItem.style.display = 'inline'; // Inline display for list item

      if (page === '...') {
        const dots = document.createElement("span");
        dots.textContent = '...';
        pageItem.appendChild(dots);
      } else {
        const pageLink = document.createElement("a");
        pageLink.style.padding = '6px 12px';
        pageLink.style.margin = '0 5px';
        pageLink.style.textDecoration = 'none';
        pageLink.style.cursor = 'pointer';
        pageLink.textContent = page;
        if (page === currentPage) {
          pageLink.style.fontWeight = 'bold';
          pageLink.style.color = '#007bff';
          pageLink.style.border = '1px solid #007bff'; // Circle border
          pageLink.style.borderRadius = '50%'; // Circle shape
          pageLink.style.padding = '5px 10px'; // Padding for circle effect
        }
        pageLink.addEventListener('click', () => {
          currentPage = page;
          updateTable();
        });
        pageItem.appendChild(pageLink);
      }
      paginationNumbers.appendChild(pageItem);
    });
  }

  document.getElementById("rowsPerPage").addEventListener('change', function() {
    rowsPerPage = parseInt(this.value);
    currentPage = 1;
    updateTable();
  });

  document.addEventListener('DOMContentLoaded', updateTable);

  // Search functionality
  document.getElementById('input-search').addEventListener('input', function() {
    const searchValue = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#activityLogsbody tr');

    filteredRows = Array.from(tableRows).filter(row => {
      const rowText = row.innerText.toLowerCase();
      return rowText.includes(searchValue); // Filter rows that include the search term
    });

    currentPage = 1; // Reset to the first page after filtering
    updateTable(); // Update pagination after filtering
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
</style>


<?php
include '../officer/footer.php';
?>