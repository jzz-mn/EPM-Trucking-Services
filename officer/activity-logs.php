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
        <div class="d-flex justify-content-between align-items-center">
          <div class="col-md-4 ">
            <form class="position-relative">
              <input type="text" class="form-control" id="input-search" placeholder="Search..." oninput="filterActivityLogs()" />

            </form>
          </div>
          <div class="col-md-4 text-end ">
            <select id="rowsPerPage" class="form-select w-auto d-inline m-1">
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
        <div class="pagination-controls d-flex justify-content-between align-items-center mt-3 flex-column flex-md-row">
          <div class="order-2 order-md-1 mt-3 mt-md-0">
            Number of pages: <span id="totalPages"></span>
          </div>
          <nav aria-label="Page navigation" class="order-1 order-md-2 w-100">
            <ul class="pagination justify-content-center justify-content-md-end mb-0" id="paginationNumbers">
              <!-- Pagination buttons will be dynamically generated here -->
            </ul>
          </nav>
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
        aValue = parseFloat(aValue.replace(/[^0-9.-]/g, '')) || 0; // Remove non-numeric characters and convert to float
        bValue = parseFloat(bValue.replace(/[^0-9.-]/g, '')) || 0;
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

  // Pagination logic
  let currentPage = 1;
  let rowsPerPage = 5;
  let totalRows = 0;
  let totalPages = 0;
  let filteredRows = [];

  function updateTable() {
    const tableBody = document.getElementById("activityLogsbody");
    const rows = filteredRows.length ? filteredRows : Array.from(tableBody.children);
    totalRows = rows.length;
    totalPages = Math.ceil(totalRows / rowsPerPage);

    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;

    rows.forEach((row, index) => {
      row.style.display = index >= startIndex && index < endIndex ? "" : "none";
    });

    document.getElementById("totalPages").textContent = totalPages;
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

    const isMobile = window.innerWidth <= 768; // Check if it's mobile view
    const maxVisiblePages = isMobile ? 3 : 5; // Show 3 pages on mobile, 5 on desktop
    const halfVisible = Math.floor(maxVisiblePages / 2);
    let startPage, endPage;

    // Ensure currentPage is within bounds
    if (currentPage > totalPages) {
      currentPage = totalPages;
    }
    if (currentPage < 1) {
      currentPage = 1;
    }

    if (totalPages <= maxVisiblePages) {
      startPage = 1;
      endPage = totalPages;
    } else if (currentPage <= halfVisible) {
      startPage = 1;
      endPage = maxVisiblePages;
    } else if (currentPage + halfVisible >= totalPages) {
      startPage = totalPages - maxVisiblePages + 1;
      endPage = totalPages;
    } else {
      startPage = currentPage - halfVisible;
      endPage = currentPage + halfVisible;
    }

    // Create first page button (<<)
    paginationNumbers.appendChild(createPaginationItem('«', currentPage === 1, () => {
      currentPage = 1;
      updateTable();
    }));

    // Create previous button (<)
    paginationNumbers.appendChild(createPaginationItem('‹', currentPage === 1, prevPage));

    for (let i = startPage; i <= endPage; i++) {
      const pageItem = document.createElement("li");
      pageItem.classList.add('page-item');

      const pageLink = document.createElement("button");
      pageLink.classList.add('page-link', 'border-0');
      pageLink.textContent = i;
      pageLink.style.minWidth = '35px';
      pageLink.style.height = '35px';
      pageLink.style.display = 'flex';
      pageLink.style.justifyContent = 'center';
      pageLink.style.alignItems = 'center';

      if (i === currentPage) {
        pageLink.classList.add('active', 'rounded-circle', 'text-white');
        pageLink.style.backgroundColor = '#0d6efd';
        pageLink.classList.remove('border-0'); // Remove border-0 for the active button
      }

      pageLink.onclick = () => {
        currentPage = i;
        updateTable();
      };

      pageItem.appendChild(pageLink);
      paginationNumbers.appendChild(pageItem);
    }

    // Create next button (>)
    paginationNumbers.appendChild(createPaginationItem('›', currentPage === totalPages, nextPage));

    // Create last page button (>>)
    paginationNumbers.appendChild(createPaginationItem('»', currentPage === totalPages, () => {
      currentPage = totalPages;
      updateTable();
    }));
  }

  function createPaginationItem(label, isDisabled = false, onClick = null) {
    const pageItem = document.createElement("li");
    pageItem.classList.add('page-item');
    if (isDisabled) {
      pageItem.classList.add('disabled');
    }

    const pageLink = document.createElement("button");
    pageLink.classList.add('page-link', 'border-0');
    pageLink.textContent = label;
    pageLink.style.minWidth = '35px';
    pageLink.style.height = '35px';
    pageLink.style.display = 'flex';
    pageLink.style.justifyContent = 'center';
    pageLink.style.alignItems = 'center';

    if (onClick) {
      pageLink.onclick = onClick;
    }

    pageItem.appendChild(pageLink);
    return pageItem;
  }

  window.addEventListener('resize', updatePaginationNumbers); // Adjust on resize
  document.addEventListener('DOMContentLoaded', () => {
    updateTable();
    updatePaginationNumbers();
  });

  document.getElementById("rowsPerPage").addEventListener('change', function() {
    rowsPerPage = parseInt(this.value);
    currentPage = 1;
    updateTable();
  });

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

<script>
  // Function to filter activity logs based on search input
  function filterActivityLogs() {
    const searchValue = document.getElementById("input-search").value.toLowerCase();
    const tableRows = document.querySelectorAll("#activityLogsbody tr");

    tableRows.forEach((row) => {
      const rowText = row.innerText.toLowerCase();
      row.style.display = rowText.includes(searchValue) ? "" : "none";
    });

    // Update pagination after filtering
    filteredRows = Array.from(tableRows).filter(row => row.style.display === "");
    currentPage = 1;
    updateTable();
  }
</script>

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


<style>
  /* Dark mode pagination styles */
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
include '../officer/footer.php';
?>