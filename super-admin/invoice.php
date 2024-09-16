<?php
include '../includes/header.php';
include '../includes/db_connection.php'
?>
<div class="body-wrapper">
  <div class="container-fluid">
    <div class="card card-body py-3">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="d-sm-flex align-items-center justify-space-between">
            <h4 class="mb-4 mb-sm-0 card-title">Invoices</h4>
            <nav aria-label="breadcrumb" class="ms-auto">
              <ol class="breadcrumb">
                <li class="breadcrumb-item d-flex align-items-center">
                  <a class="text-muted text-decoration-none d-flex" href="./">
                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                  </a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                  <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                    Invoice
                  </span>
                </li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <div class="card overflow-hidden invoice-application">

      <div class="d-flex">

        <div class="w-100 w-xs-100 chat-container">
          <div class="invoice-inner-part h-100">
            <div class="invoiceing-box">
              <div class="invoice-header d-flex align-items-center border-bottom p-3">
                <a href="#" class="btn btn-primary d-flex align-items-center ms-auto" data-bs-toggle="modal"
                  data-bs-target="#addInvoiceModal">
                  <i class="ti ti-users text-white me-1 fs-5"></i> Add Invoice
                </a>
              </div>

              <div class="modal fade" id="addInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="addInvoiceModalTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header d-flex align-items-center bg-primary">
                      <h5 class="modal-title text-white fs-4">Add Invoice Details</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="add-contact-box">
                        <div class="add-contact-content">
                          <form id="addInvoiceForm">
                            <div class="row">
                              <div class="col-lg-4 mb-3">
                                <label for="BillingInvoiceNoInput" class="form-label">Billing Invoice No</label>
                                <input type="text" class="form-control" id="BillingInvoiceNoInput" placeholder="Enter Billing Invoice Number">
                              </div>
                              <div class="col-lg-4 mb-3">
                                <label for="BillingDateInput" class="form-label">Billing Date</label>
                                <input type="date" class="form-control" id="BillingDateInput" placeholder="Enter Billing Date">
                              </div>
                              <div class="col-12 mb-3">
                                <label for="BilledToInput" class="form-label">Billed To</label>
                                <input type="text" class="form-control" id="BilledToInput" placeholder="Billing To">
                              </div>
                              <div class="col-lg-4 mb-3">
                                <label for="GrossAmountInput" class="form-label">Gross Amount</label>
                                <input type="text" class="form-control" id="GrossAmountInput" placeholder="Enter Gross Amount">
                              </div>
                              <div class="col-lg-4 mb-3">
                                <label for="VAT12Input" class="form-label">VAT 12%</label>
                                <input type="text" class="form-control" id="VAT12Input" placeholder="Enter VAT 12%">
                              </div>
                              <div class="col-lg-4 mb-3">
                                <label for="EWT2Input" class="form-label">EWT 2%</label>
                                <input type="text" class="form-control" id="EWT2Input" placeholder="Enter EWT 2%">
                              </div>
                              <div class="col-lg-4 mb-3">
                                <label for="AddTollChargesInput" class="form-label">Add Toll/Charges</label>
                                <input type="text" class="form-control" id="AddTollChargesInput" placeholder="Enter Toll/Charges">
                              </div>
                              <div class="col-lg-4 mb-3">
                                <label for="AmountNetofTaxInput" class="form-label">Amount Net of Tax</label>
                                <input type="text" class="form-control" id="AmountNetofTaxInput" placeholder="Enter Amount Net of Tax">
                              </div>
                              <div class="col-lg-4 mb-3">
                                <label for="NetAmountInput" class="form-label">Net Amount</label>
                                <input type="text" class="form-control" id="NetAmountInput" placeholder="Enter Net Amount">
                              </div>
                            </div>
                            <div class="col-12 mb-3">
                              <div class="d-flex gap-6 m-0 justify-content-end">
                                <button id="btn-add-invoice" class="btn btn-success">Save</button>
                                <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Discard</button>
                              </div>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <script>
                document.getElementById('btn-add-invoice').addEventListener('click', function(e) {
                  e.preventDefault();

                  // Get form values
                  let billingInvoiceNo = document.getElementById('BillingInvoiceNoInput').value;
                  let billingDate = document.getElementById('BillingDateInput').value;
                  let billedTo = document.getElementById('BilledToInput').value; // Correct variable name here
                  let grossAmount = document.getElementById('GrossAmountInput').value;
                  let vat12 = document.getElementById('VAT12Input').value;
                  let ewt2 = document.getElementById('EWT2Input').value;
                  let addTollCharges = document.getElementById('AddTollChargesInput').value;
                  let amountNetofTax = document.getElementById('AmountNetofTaxInput').value;
                  let netAmount = document.getElementById('NetAmountInput').value;

                  // Prepare form data for submission
                  let formData = new FormData();
                  formData.append('BillingInvoiceNo', billingInvoiceNo);
                  formData.append('BillingDate', billingDate);
                  formData.append('BilledTo', billedTo); // Ensure you're appending 'BilledTo' correctly
                  formData.append('GrossAmount', grossAmount);
                  formData.append('VAT12', vat12);
                  formData.append('EWT2', ewt2);
                  formData.append('AddTollCharges', addTollCharges);
                  formData.append('AmountNetofTax', amountNetofTax);
                  formData.append('NetAmount', netAmount);

                  // Send the form data using AJAX
                  fetch('add_invoices.php', {
                      method: 'POST',
                      body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                      if (data.success) {
                        alert('Invoice added successfully.');
                        $('#addInvoiceModal').modal('hide');
                        document.getElementById('addInvoiceForm').reset();
                      } else {
                        alert(data.message);
                      }
                    })
                    .catch(error => console.error('Error:', error));
                });
              </script>


              <!-- Responsive Table Container using Bootstrap classes -->
              <div class="table-responsive mt-3">
                <table id="invoice_table" class="table w-100 table-striped table-bordered table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>Invoice ID</th>
                      <th>Billing Invoice No</th>
                      <th>Billing Date</th>
                      <th>Billed To</th>
                      <th>Gross Amount</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    // Assuming $conn is your database connection
                    $sql = "SELECT InvoiceID, BillingInvoiceNo, BillingDate, BilledTo, GrossAmount FROM invoices";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['InvoiceID']}</td>";
                        echo "<td>{$row['BillingInvoiceNo']}</td>";
                        echo "<td>{$row['BillingDate']}</td>";
                        echo "<td>{$row['BilledTo']}</td>";
                        echo "<td>" . number_format($row['GrossAmount'], 2) . "</td>";
                        echo "<td>";
                        echo "<div class='dropdown dropstart'>";
                        echo "<a href='javascript:void(0)' class='text-muted' id='dropdownMenuButton{$row['InvoiceID']}' data-bs-toggle='dropdown' aria-expanded='false'>";
                        echo "<i class='ti ti-dots fs-5'></i></a>";
                        echo "<ul class='dropdown-menu' aria-labelledby='dropdownMenuButton{$row['InvoiceID']}'>";
                        echo "<li><a class='dropdown-item d-flex align-items-center gap-3' href='invoice-edit.php?id={$row['InvoiceID']}'><i class='fs-4 ti ti-edit'></i>Edit</a></li>";
                        echo "<li><a class='dropdown-item d-flex align-items-center gap-3' href='../includes/delete_invoice.php?id={$row['InvoiceID']}'><i class='fs-4 ti ti-trash'></i>Delete</a></li>";
                        echo "</ul></div></td>";
                        echo "</tr>";
                      }
                    } else {
                      echo "<tr><td colspan='6' class='text-center'>No invoices found</td></tr>";
                    }
                    $conn->close();
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- DataTables Initialization Script -->
        <script>
          $(document).ready(function() {
            $('#invoice_table').DataTable(); // Initialize DataTables for the invoices table
          });
        </script>

      </div>
    </div>
    <div class="offcanvas offcanvas-start user-chat-box" tabindex="-1" id="chat-sidebar"
      aria-labelledby="offcanvasExampleLabel">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasExampleLabel">
          Invoice
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="p-3 border-bottom">
        <form class="position-relative">
        </form>
      </div>

    </div>
  </div>
</div>
</div>
</div>
<button class="btn btn-danger p-3 rounded-circle d-flex align-items-center justify-content-center customizer-btn"
  type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExample" aria-controls="offcanvasExample">
  <i class="icon ti ti-settings fs-7"></i>
</button>

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
<script src="../assets/js/apps/invoice.js"></script>
<script src="../assets/js/apps/jquery.PrintArea.js"></script>
</body>

</html>