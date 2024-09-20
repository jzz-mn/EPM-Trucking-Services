<?php
session_start();
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



              <div class="table-responsive mt-3">
                <table id="invoice_table" class="table w-100 table-striped table-bordered table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>Invoice ID</th>
                      <th>Billing Invoice No</th>
                      <th>Billing Date</th>
                      <th>Billed To</th>
                      <th>Grand Subtotal</th>
                      <th>Gross Amount</th>
                      <th>VAT 12%</th>
                      <th>EWT 2%</th>
                      <th>Add Toll/Charges</th>
                      <th>Amount Net of Tax</th>
                      <th>Net of Tax</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    // Assuming $conn is your database connection
                    $sql = "SELECT InvoiceID, BillingInvoiceNo, BillingDate, BilledTo,GrandSubtotal, GrossAmount, VAT12, EWT2, AddTollCharges, AmountNetofTax, NetAmount FROM invoices";
                    $result = $conn->query($sql);

                    // Check if query execution was successful
                    if ($result === false) {
                      // Output the error if the query fails
                      echo "Error: " . $conn->error;
                    } else {
                      if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                          echo "<tr>";
                          echo "<td>{$row['InvoiceID']}</td>";
                          echo "<td>{$row['BillingInvoiceNo']}</td>";
                          echo "<td>{$row['BillingDate']}</td>";
                          echo "<td>{$row['BilledTo']}</td>";
                          echo "<td>" . number_format($row['GrandSubtotal'], 2) . "</td>";
                          echo "<td>" . number_format($row['GrossAmount'], 2) . "</td>";
                          echo "<td>" . number_format($row['VAT12'], 2) . "</td>";
                          echo "<td>" . number_format($row['EWT2'], 2) . "</td>";
                          echo "<td>" . number_format($row['AddTollCharges'], 2) . "</td>";
                          echo "<td>" . number_format($row['AmountNetofTax'], 2) . "</td>";
                          echo "<td>" . number_format($row['NetAmount'], 2) . "</td>";

                          echo "<td>";
                          echo "<a href='javascript:void(0)' class='btn btn-primary btn-sm me-2' onclick='openEditModal({$row['InvoiceID']})'><i class='ti ti-edit'></i></a>";
                          echo "<a href='javascript:void(0)' class='btn btn-danger btn-sm' onclick='openDeleteModal({$row['InvoiceID']})'><i class='ti ti-trash'></i></a>";
                          echo "</td>";
                          echo "</tr>";
                        }
                      } else {
                        echo "<tr><td colspan='6' class='text-center'>No invoices found</td></tr>";
                      }
                    }
                    $conn->close();
                    ?>

                  </tbody>
                </table>
              </div>
            </div>
          </div>
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
                          <label for="GrandSubtotalInput" class="form-label">Grand Subtotal</label>
                          <input type="text" class="form-control" id="GrandSubtotalInput" placeholder="Enter Grand Subtotal">
                        </div>
                        <div class="col-lg-4 mb-3">
                          <label for="GrossAmountInput" class="form-label">Gross Amount</label>
                          <input type="text" class="form-control" id="GrossAmountInput" placeholder="Enter Gross Amount">
                        </div>
                        <div class="col-lg-4 mb-3">
                          <label for="VAT12Input" class="form-label">VAT 12%</label>
                          <input type="text" class="form-control" id="VAT12Input" placeholder="VAT 12%" readonly>
                        </div>
                        <div class="col-lg-4 mb-3">
                          <label for="EWT2Input" class="form-label">EWT 2%</label>
                          <input type="text" class="form-control" id="EWT2Input" placeholder="EWT 2%" readonly>
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
                          <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Discard</button>
                          <button id="btn-add-invoice" class="btn btn-primary">Save</button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Invoice Modal -->
        <div class="modal fade" id="editInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="editInvoiceModalTitle" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
              <div class="modal-header d-flex align-items-center bg-primary">
                <h5 class="modal-title text-white fs-4">Edit Invoice Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="editInvoiceForm">
                  <input type="hidden" id="editInvoiceID">
                  <div class="row">
                    <div class="col-lg-4 mb-3">
                      <label for="editBillingInvoiceNoInput" class="form-label">Billing Invoice No</label>
                      <input type="text" class="form-control" id="editBillingInvoiceNoInput">
                    </div>
                    <div class="col-lg-4 mb-3">
                      <label for="editBillingDateInput" class="form-label">Billing Date</label>
                      <input type="date" class="form-control" id="editBillingDateInput">
                    </div>
                    <div class="col-12 mb-3">
                      <label for="editBilledToInput" class="form-label">Billed To</label>
                      <input type="text" class="form-control" id="editBilledToInput">
                    </div>
                    <div class="col-lg-4 mb-3">
                      <label for="editGrandSubtotalInput" class="form-label">Grand Subtotal</label>
                      <input type="text" class="form-control" id="editGrandSubtotalInput" placeholder="Enter Grand Subtotal">
                    </div>
                    <div class="col-lg-4 mb-3">
                      <label for="editGrossAmountInput" class="form-label">Gross Amount</label>
                      <input type="text" class="form-control" id="editGrossAmountInput" placeholder="Enter Gross Amount">
                    </div>
                    <div class="col-lg-4 mb-3">
                      <label for="editVAT12Input" class="form-label">VAT 12%</label>
                      <input type="text" class="form-control" id="editVAT12Input" placeholder="VAT 12%" readonly>
                    </div>
                    <div class="col-lg-4 mb-3">
                      <label for="editEWT2Input" class="form-label">EWT 2%</label>
                      <input type="text" class="form-control" id="editEWT2Input" placeholder="EWT 2%" readonly>
                    </div>
                    <div class="col-lg-4 mb-3">
                      <label for="editAddTollChargesInput" class="form-label">Add Toll Charges</label>
                      <input type="text" class="form-control" id="editAddTollChargesInput">
                    </div>
                    <div class="col-lg-4 mb-3">
                      <label for="editAmountNetofTaxInput" class="form-label">Amount Net of Tax</label>
                      <input type="text" class="form-control" id="editAmountNetofTaxInput">
                    </div>
                    <div class="col-lg-4 mb-3">
                      <label for="editNetAmountInput" class="form-label">Net Amount</label>
                      <input type="text" class="form-control" id="editNetAmountInput">
                    </div>
                    <!-- Add other fields as needed -->
                  </div>
                  <div class="d-flex gap-6 m-0 justify-content-end">
                    <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Cancel</button>
                    <button id="btn-update-invoice" class="btn btn-primary">Update</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="deleteInvoiceModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="deleteInvoiceModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <p>Are you sure you want to delete this invoice? This action cannot be undone.</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
              </div>
            </div>
          </div>
        </div>


        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
          //Add Functions
          document.getElementById('btn-add-invoice').addEventListener('click', function(e) {
            e.preventDefault();

            // Get form values
            let billingInvoiceNo = document.getElementById('BillingInvoiceNoInput').value;
            let billingDate = document.getElementById('BillingDateInput').value;
            let billedTo = document.getElementById('BilledToInput').value;
            let grandSubtotal = document.getElementById('GrandSubtotalInput').value;
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
            formData.append('BilledTo', billedTo);
            formData.append('GrandSubtotal', grandSubtotal);
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
                // Check if the server returned a success message
                if (data.success) {
                  alert(data.message); // Show success message
                  // Optionally, hide the modal and reset the form
                  $('#addInvoiceModal').modal('hide');
                  document.getElementById('addInvoiceForm').reset();
                  // Reload the page to show the new data
                  location.reload();
                } else {
                  alert(data.message); // Show error message from server
                }
              })
              .catch(error => console.error('Error:', error));
          });

          // Function to calculate VAT12 and EWT2
          function calculateVATandEWT() {
            const grossAmount = parseFloat(document.getElementById('GrossAmountInput').value) || 0;
            const vat12 = grossAmount * 0.12;
            const ewt2 = grossAmount * 0.02;

            document.getElementById('VAT12Input').value = vat12.toFixed(2);
            document.getElementById('EWT2Input').value = ewt2.toFixed(2);
          }

          // Add event listener to calculate when GrossAmount is updated
          document.getElementById('GrossAmountInput').addEventListener('input', calculateVATandEWT);

          // Initial calculation when the form loads (if GrossAmount already has a value)
          document.addEventListener('DOMContentLoaded', calculateVATandEWT);
        </script>


        <script>
          // Edit Functions
          function openEditModal(invoiceID) {
            // Fetch the invoice data via AJAX or from your server
            fetch('get_invoice.php?id=' + invoiceID)
              .then(response => response.json())
              .then(data => {
                // Populate the modal fields with the existing invoice data
                document.getElementById('editInvoiceID').value = data.InvoiceID;
                document.getElementById('editBillingInvoiceNoInput').value = data.BillingInvoiceNo;
                document.getElementById('editBillingDateInput').value = data.BillingDate;
                document.getElementById('editGrandSubtotalInput').value = data.GrandSubtotal;
                document.getElementById('editBilledToInput').value = data.BilledTo;
                document.getElementById('editGrossAmountInput').value = data.GrossAmount;

                // Automatically calculate VAT12 and EWT2
                calculateEditVATandEWT();

                document.getElementById('editAddTollChargesInput').value = data.AddTollCharges;
                document.getElementById('editAmountNetofTaxInput').value = data.AmountNetofTax;
                document.getElementById('editNetAmountInput').value = data.NetAmount;

                // Show the modal
                $('#editInvoiceModal').modal('show');
              })
              .catch(error => console.error('Error fetching invoice data:', error));
          }

          // Function to calculate VAT12 and EWT2 for the Edit form
          function calculateEditVATandEWT() {
            const grossAmount = parseFloat(document.getElementById('editGrossAmountInput').value) || 0;
            const vat12 = grossAmount * 0.12;
            const ewt2 = grossAmount * 0.02;

            document.getElementById('editVAT12Input').value = vat12.toFixed(2);
            document.getElementById('editEWT2Input').value = ewt2.toFixed(2);
          }

          // Add event listener to calculate VAT and EWT when GrossAmount is updated in Edit form
          document.getElementById('editGrossAmountInput').addEventListener('input', calculateEditVATandEWT);

          // When updating the invoice
          document.getElementById('btn-update-invoice').addEventListener('click', function(e) {
            e.preventDefault();

            // Get form values
            let invoiceID = document.getElementById('editInvoiceID').value;
            let billingInvoiceNo = document.getElementById('editBillingInvoiceNoInput').value;
            let billingDate = document.getElementById('editBillingDateInput').value;
            let billedTo = document.getElementById('editBilledToInput').value;
            let grandSubtotal = document.getElementById('editGrandSubtotalInput').value;
            let grossAmount = document.getElementById('editGrossAmountInput').value;
            let vat12 = document.getElementById('editVAT12Input').value;
            let ewt2 = document.getElementById('editEWT2Input').value;
            let addTollCharges = document.getElementById('editAddTollChargesInput').value;
            let amountNetofTax = document.getElementById('editAmountNetofTaxInput').value;
            let netAmount = document.getElementById('editNetAmountInput').value;

            // Prepare form data
            let formData = new FormData();
            formData.append('InvoiceID', invoiceID);
            formData.append('BillingInvoiceNo', billingInvoiceNo);
            formData.append('BillingDate', billingDate);
            formData.append('GrandSubtotal', grandSubtotal);
            formData.append('BilledTo', billedTo);
            formData.append('GrossAmount', grossAmount);
            formData.append('VAT12', vat12);
            formData.append('EWT2', ewt2);
            formData.append('AddTollCharges', addTollCharges);
            formData.append('AmountNetofTax', amountNetofTax);
            formData.append('NetAmount', netAmount);

            // Submit the data via AJAX
            fetch('update_invoice.php', {
                method: 'POST',
                body: formData
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  alert('Invoice updated successfully.');
                  $('#editInvoiceModal').modal('hide');
                  location.reload(); // Refresh the table
                } else {
                  alert(data.message);
                }
              })
              .catch(error => console.error('Error:', error));
          });
        </script>

        <script>
          //Delete Functions
          let invoiceIDToDelete = null;

          function openDeleteModal(invoiceID) {
            invoiceIDToDelete = invoiceID; // Store the invoice ID to delete
            $('#deleteInvoiceModal').modal('show'); // Show the modal
          }

          document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (invoiceIDToDelete !== null) {
              // Make AJAX request to delete the invoice
              fetch('delete_invoice.php', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                  },
                  body: `id=${invoiceIDToDelete}` // Pass the invoice ID
                })
                .then(response => response.json())
                .then(data => {
                  if (data.success) {
                    // Hide the modal
                    $('#deleteInvoiceModal').modal('hide');
                    // Optionally, reload the page or remove the deleted invoice row
                    alert('Invoice deleted successfully.');
                    location.reload(); // Reload the page to reflect changes
                  } else {
                    alert('Failed to delete invoice: ' + data.message);
                  }
                })
                .catch(error => console.error('Error deleting invoice:', error));
            }
          });
        </script>

        <!-- DataTables Initialization Script -->
      
        <script>
          $(document).ready(function() {
            $('#invoice_table').DataTable({
              dom: 'Bfrtip', // Include the buttons
              buttons: [{
                  extend: 'copyHtml5',
                  text: 'Copy',
                  className: 'btn btn-primary'
                },
                {
                  extend: 'csvHtml5',
                  text: 'CSV',
                  className: 'btn btn-primary'
                },
                {
                  extend: 'excelHtml5',
                  text: 'Excel',
                  className: 'btn btn-primary'
                },
                {
                  extend: 'pdfHtml5',
                  text: 'PDF',
                  className: 'btn btn-primary'
                },
                {
                  extend: 'print',
                  text: 'Print',
                  className: 'btn btn-primary'
                }
              ]
            });
          });
        </script>

        <script src="../assets/js/datatable/datatable-advanced.init.js"></script>


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
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css" rel="stylesheet">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>

<!-- Buttons extension for DataTables -->
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>

<!-- JSZip and pdfmake for Excel and PDF export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

</body>

</html>