<?php
session_start();
include '../officer/header.php';
?>

<div class="body-wrapper">
  <div class="container-fluid">
    <div class="card card-body py-3">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="d-sm-flex align-items-center justify-space-between">
            <h4 class="mb-4 mb-sm-0 card-title">Records</h4>
            <nav aria-label="breadcrumb" class="ms-auto">
              <ol class="breadcrumb">
                <li class="breadcrumb-item d-flex align-items-center">
                  <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
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
    <h5 class="border-bottom py-2 px-4 mb-4">Employees</h5>
    <div class="widget-content searchable-container list">
      <!-- Add Employee Modal -->
      <div class="modal fade" id="addContactModal" tabindex="-1" role="dialog" aria-labelledby="addContactModalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header d-flex align-items-center bg-primary">
              <h5 class="modal-title text-white fs-4">Add Employee Details</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="add-contact-box">
                <div class="add-contact-content">
                  <form id="addEmployeeForm" method="POST" action="add_employee.php">
                    <div class="row">
                      <div class="col-lg-6 d-flex align-items-stretch">
                        <div class="card w-100 border position-relative overflow-hidden">
                          <div class="card-body p-4">
                            <h4 class="card-title">Add Profile Picture</h4>
                            <p class="card-subtitle mb-4">Upload a profile picture here.</p>
                            <div class="text-center">
                              <img src="../assets/images/profile/user-1.jpg" alt="profile-img"
                                class="img-fluid rounded-circle my-4 " width="140" height="140">
                              <div class="d-flex align-items-center justify-content-center my-4 gap-6">
                                <button class="btn btn-primary">Upload</button>
                                <button class="btn bg-danger-subtle text-danger">Reset</button>
                              </div>
                              <p class="mb-0">Allowed JPG, GIF or PNG. Max size of 800K</p>
                            </div>
                          </div>
                        </div>
                      </div>
                      <!-- Account Creation Section -->
                      <div class="col-lg-6 d-flex align-items-stretch">
                        <div class="card w-100 border position-relative overflow-hidden">
                          <div class="card-body p-4">
                            <h4 class="card-title">Create Account</h4>
                            <p class="card-subtitle mb-4">Please enter the employee's login credentials.</p>
                            <div class="mb-3">
                              <label for="usernameInput" class="form-label">Username</label>
                              <input type="text" class="form-control" id="usernameInput" name="username"
                                placeholder="Enter username" required>
                              <div class="invalid-feedback">
                                This username is already taken.
                              </div>
                            </div>

                            <div class="mb-3">
                              <label for="emailInput" class="form-label">Email address</label>
                              <input type="email" class="form-control" id="emailInput" name="emailAddress"
                                placeholder="Enter email" required>
                              <div class="invalid-feedback">
                                This email address is already taken.
                              </div>
                            </div>


                            <div class="mb-3">
                              <label for="passwordInput" class="form-label">New Password</label>
                              <input type="password" class="form-control" id="passwordInput" name="password"
                                placeholder="Enter password" required>
                            </div>
                            <div>
                              <label for="confirmPasswordInput" class="form-label">Confirm Password</label>
                              <input type="password" class="form-control" id="confirmPasswordInput"
                                name="confirmPassword" placeholder="Confirm password" required>
                            </div>

                          </div>
                        </div>
                      </div>

                    </div>

                    <!-- Personal Details Section -->
                    <div class="col-12">
                      <div class="card w-100 border position-relative overflow-hidden mb-0">
                        <div class="card-body p-4">
                          <h4 class="card-title">Personal Details</h4>
                          <div class="row">
                            <div class="col-lg-4 mb-3">
                              <label for="firstNameInput" class="form-label">First Name</label>
                              <input type="text" class="form-control" id="firstNameInput" name="firstName"
                                placeholder="Enter first name" required>
                            </div>
                            <div class="col-lg-4 mb-3">
                              <label for="middleInitialInput" class="form-label">Middle Initial</label>
                              <input type="text" class="form-control" id="middleInitialInput" name="middleInitial"
                                placeholder="Enter middle initial">
                            </div>
                            <div class="col-lg-4 mb-3">
                              <label for="lastNameInput" class="form-label">Last Name</label>
                              <input type="text" class="form-control" id="lastNameInput" name="lastName"
                                placeholder="Enter last name" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                              <label for="genderInput" class="form-label">Gender</label>
                              <select class="form-control" id="genderInput" name="gender" required>
                                <option value="MALE">MALE</option>
                                <option value="FEMALE">FEMALE</option>
                              </select>
                            </div>
                            <div class="col-lg-6 mb-3">
                              <label for="dobInput" class="form-label">Date of Birth</label>
                              <input type="date" class="form-control" id="dobInput" name="dob"
                                placeholder="Enter date of birth" required>
                            </div>
                            <div class="col-lg-4 mb-3">
                              <label for="mobileInput" class="form-label">Mobile Number</label>
                              <input type="text" class="form-control" id="mobileInput" name="mobileNo"
                                placeholder="Enter mobile number" required>
                            </div>
                            <div class="col-lg-4 mb-3">
                              <label for="employmentDateInput" class="form-label">Employment Date</label>
                              <input type="date" class="form-control" id="employmentDateInput" name="employmentDate"
                                required>
                            </div>
                            <div class="col-lg-4 mb-3">
                              <label for="positionInput" class="form-label">Position</label>
                              <select class="form-control" id="positionInput" name="position"
                                placeholder="Enter position" required>
                                <option value="Driver">Driver</option>
                                <option value="Helper/Crew">Helper/Crew</option>
                              </select>
                            </div>
                            <div class="col-12 mb-3">
                              <label for="addressInput" class="form-label">Address</label>
                              <input type="text" class="form-control" id="addressInput" name="address"
                                placeholder="Enter address" required>
                            </div>
                            <div class="col-12 mb-3">
                              <div class="d-flex gap-6 m-0 justify-content-end">
                                <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Discard</button>
                                <button id="btn-add" class="btn btn-primary" type="submit">Save</button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </form>

                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Edit Employee Modal -->
      <div class="modal fade" id="editContactModal" tabindex="-1" role="dialog" aria-labelledby="editContactModalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header d-flex align-items-center bg-primary">
              <h5 class="modal-title text-white fs-4">Edit Employee Details</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="add-contact-box">
                <div class="add-contact-content">
                  <!-- Edit Employee Form -->
                  <form id="editEmployeeForm" method="POST" action="../officer/edit_employee.php">
                    <input type="hidden" id="editEmployeeID" name="employeeID">
                    <div class="row">
                      <!-- Profile Picture Section -->
                      <div class="col-lg-6 d-flex align-items-stretch">
                        <div class="card w-100 border position-relative overflow-hidden">
                          <div class="card-body p-4">
                            <h4 class="card-title">Edit Profile Picture</h4>
                            <p class="card-subtitle mb-4">Upload a profile picture here.</p>
                            <div class="text-center">
                              <img src="../assets/images/profile/user-1.jpg" alt="profile-img"
                                class="img-fluid rounded-circle my-4" width="140" height="140">
                              <div class="d-flex align-items-center justify-content-center my-4 gap-6">
                                <button class="btn btn-primary">Upload</button>
                                <button class="btn bg-danger-subtle text-danger">Reset</button>
                              </div>
                              <p class="mb-0">Allowed JPG, GIF or PNG. Max size of 800K</p>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="col-lg-6">
                        <div class="card w-100 border">
                          <div class="card-body">
                            <h4 class="card-title">Edit Account</h4>
                            <p class="card-subtitle mb-4">Please enter the employee's login credentials.</p>
                            <div class="mb-3">
                              <label for="editUsernameInput" class="form-label">Username</label>
                              <input type="text" class="form-control" id="editUsernameInput" name="username"
                                placeholder="Enter username" required>
                            </div>
                            <div class="mb-3">
                              <label for="editEmailInput" class="form-label">Email address</label>
                              <input type="email" class="form-control" id="editEmailInput" name="emailAddress"
                                placeholder="Enter email" required>
                            </div>
                            <div class="mb-3">
                              <label for="editCurrentPasswordInput" class="form-label">Current Password</label>
                              <input type="password" class="form-control" id="editCurrentPasswordInput" name="currentPassword"
                                placeholder="Enter current password" required>
                            </div>
                            <div class="mb-3">
                              <label for="editNewPasswordInput" class="form-label">New Password</label>
                              <input type="password" class="form-control" id="editNewPasswordInput" name="newPassword"
                                placeholder="Enter new password">
                            </div>
                            <div>
                              <label for="editConfirmNewPasswordInput" class="form-label">Confirm New Password</label>
                              <input type="password" class="form-control" id="editConfirmNewPasswordInput"
                                name="confirmNewPassword" placeholder="Confirm new password">
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-12">
                      <div class="card w-100 border mb-0">
                        <div class="card-body">
                          <h4 class="card-title">Personal Details</h4>
                          <div class="row">
                            <div class="col-lg-4 mb-3">
                              <label for="editFirstNameInput" class="form-label">First Name</label>
                              <input type="text" class="form-control" id="editFirstNameInput" name="firstName"
                                placeholder="Enter first name" required>
                            </div>
                            <div class="col-lg-4 mb-3">
                              <label for="editMiddleInitialInput" class="form-label">Middle Initial</label>
                              <input type="text" class="form-control" id="editMiddleInitialInput" name="middleInitial"
                                placeholder="Enter middle initial">
                            </div>
                            <div class="col-lg-4 mb-3">
                              <label for="editLastNameInput" class="form-label">Last Name</label>
                              <input type="text" class="form-control" id="editLastNameInput" name="lastName"
                                placeholder="Enter last name" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                              <label for="editGenderInput" class="form-label">Gender</label>
                              <select type="text" class="form-control" id="editGenderInput" name="gender"
                                placeholder="Enter gender" required>
                                <option value="MALE">MALE</option>
                                <option value="FEMALE">FEMALE</option>
                              </select>
                            </div>
                            <div class="col-lg-6 mb-3">
                              <label for="editDobInput" class="form-label">Date of Birth</label>
                              <input type="date" class="form-control" id="editDobInput" name="dateOfBirth" required>
                            </div>
                            <div class="col-lg-4 mb-3">
                              <label for="editMobileInput" class="form-label">Mobile Number</label>
                              <input type="text" class="form-control" id="editMobileInput" name="mobileNo"
                                placeholder="Enter mobile number" required>
                            </div>
                            <div class="col-lg-4 mb-3">
                              <label for="editEmploymentDateInput" class="form-label">Employment Date</label>
                              <input type="date" class="form-control" id="editEmploymentDateInput" name="employmentDate"
                                required>
                            </div>
                            <div class="col-lg-4 mb-3">
                              <label for="editPositionInput" class="form-label">Position</label>
                              <select type="text" class="form-control" id="editPositionInput" name="position"
                                placeholder="Enter position" required>
                                <option value="Driver">Driver</option>
                                <option value="Helper/Crew">Helper/Crew</option>
                              </select>
                            </div>
                            <div class="col-7 mb-3">
                              <label for="editAddressInput" class="form-label">Address</label>
                              <input type="text" class="form-control" id="editAddressInput" name="address"
                                placeholder="Enter address" required>
                            </div>
                            <div class="col-lg-4 mb-3">
                              <label for="editActivationStatus" class="form-label">Activation Status</label>
                              <select class="form-select" id="editActivationStatus" name="activationStatus" required>
                                <option value="Activated">Activated</option>
                                <option value="Deactivated">Deactivated</option>
                              </select>
                            </div>
                            <div class="col-12 mb-3">
                              <div class="d-flex gap-6 justify-content-end">
                                <button class="btn bg-danger-subtle text-danger"
                                  data-bs-dismiss="modal">Discard</button>
                                <button id="btn-edit" class="btn btn-primary" type="submit">Save Changes</button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </form>
                </div>
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
      <div class="card card-body mb-0">
        <div class="row">
          <div class="col-md-4 col-xl-3">
            <form class="position-relative">
              <input type="text" class="form-control product-search" id="input-search" placeholder="Search" />
              <i class="ti position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
            </form>
          </div>

          <div class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
            <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
              data-bs-target="#addContactModal">
              <i class="ti ti-users text-white me-1 fs-5"></i> Add Employee
            </a>
          </div>
        </div>
      </div>
      <div class="table-responsive card p-0 card-body">
        <table id="" class="table table-striped table-bordered text-nowrap align-middle text-center">
          <thead>
            <tr>
              <th class="sortable" data-sort="name">Name</th>
              <th class="sortable" data-sort="position">Position</th>
              <th class="sortable" data-sort="status">Activation Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="employeeTableBody">
            <?php
            $query = "SELECT e.EmployeeID, e.FirstName, e.MiddleInitial, e.LastName, e.Position, e.Address, e.MobileNo, e.EmailAddress, e.EmploymentDate,
                       ua.ActivationStatus
                FROM employees e
                LEFT JOIN useraccounts ua ON e.EmployeeID = ua.employeeID";

            $result = $conn->query($query);

            if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                $fullName = "{$row['FirstName']} {$row['MiddleInitial']} {$row['LastName']}";
                $positionBadge = '';
                if ($row['Position'] === 'Driver') {
                  $positionBadge = "<span class='badge text-bg-primary'>Driver</span>";
                } elseif ($row['Position'] === 'Helper/Crew') {
                  $positionBadge = "<span class='badge text-bg-warning'>Helper/Crew</span>";
                } else {
                  $positionBadge = "<span class='badge text-bg-secondary'>{$row['Position']}</span>";
                }

                // Handle ActivationStatus like in officers.php
                $activationStatus = '';
                if ($row['ActivationStatus'] === 'Activated') {
                  $activationStatus = "<span class='badge text-bg-success'>Activated</span>";
                } elseif ($row['ActivationStatus'] === 'Deactivated') {
                  $activationStatus = "<span class='badge text-bg-danger'>Deactivated</span>";
                } else {
                  $activationStatus = "<span class='badge text-bg-danger'>Deactivated</span>";
                }

                // Output the table row with the data attribute to enable searching
                echo "<tr data-name='{$fullName}' data-position='{$row['Position']}' data-status='{$row['ActivationStatus']}'>";
                echo "<td><div class='d-flex align-items-center'>";
                echo "<img src='../assets/images/profile/user-1.jpg' class='rounded-circle' width='40' height='40' />";
                echo "<div class='ms-3'>";
                echo "<h6 class='fs-4 fw-semibold mb-0'>{$fullName}</h6>";
                echo "</div></div></td>";
                echo "<td>{$positionBadge}</td>";
                echo "<td>{$activationStatus}</td>";
                echo "<td><a data-bs-toggle='modal' data-bs-target='#editContactModal' href='#' class='me-3 text-primary' data-id='{$row['EmployeeID']}'>";
                echo "<i class='fs-4 ti ti-edit'></i></a></td>";
                echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='8' class='text-center'>No employees found</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // When clicking the edit button, load employee details into the modal
    document.querySelectorAll('[data-bs-target="#editContactModal"]').forEach(button => {
      button.addEventListener('click', function() {
        const employeeID = this.getAttribute('data-id');
        fetch(`../officer/fetch_employee.php?id=${employeeID}`)
          .then(response => response.json())
          .then(data => {
            document.getElementById('editEmployeeID').value = data.EmployeeID;
            document.getElementById('editFirstNameInput').value = data.FirstName;
            document.getElementById('editMiddleInitialInput').value = data.MiddleInitial;
            document.getElementById('editLastNameInput').value = data.LastName;
            document.getElementById('editGenderInput').value = data.Gender;

            // Ensure the Date of Birth is in correct format for date input (YYYY-MM-DD)
            document.getElementById('editDobInput').value = data.DateOfBirth;

            document.getElementById('editMobileInput').value = data.MobileNo;
            document.getElementById('editEmploymentDateInput').value = data.EmploymentDate;
            document.getElementById('editPositionInput').value = data.Position;
            document.getElementById('editAddressInput').value = data.Address;

            // Populate the user account details
            document.getElementById('editUsernameInput').value = data.Username;
            document.getElementById('editEmailInput').value = data.accountEmail;
            document.getElementById('editPasswordInput').value = data.Password;


            const activationStatus = data.ActivationStatus === 'Active' ? 'Activated' : 'Deactivated';
            document.getElementById('editActivationStatus').value = activationStatus;
          })
          .catch(error => console.error('Error fetching employee data:', error));
      });
    });

    // Handle form submission to edit employee details
    const editForm = document.getElementById('editEmployeeForm');
    editForm.addEventListener('submit', function(e) {
      e.preventDefault(); // Prevent the form from submitting the traditional way

      const formData = new FormData(editForm);

      fetch('../officer/edit_employee.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          // Log the entire response to check its structure
          console.log('Server response:', data);

          if (data.success) {
            // Success block - Reset the form fields and display the success message
            alert('Employee details updated successfully!');
            editForm.reset(); // Resets the form fields

            // Close the modal after form submission
            const editModal = bootstrap.Modal.getInstance(document.getElementById('editContactModal'));
            if (editModal) {
              editModal.hide();
            }

            // Reload the page to reflect updated data
            location.reload();
          } else {
            // If there is an error message from the server
            alert('Error updating employee: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error updating employee:', error);
        });
    });
  });
</script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const currentPasswordInput = document.getElementById('editCurrentPasswordInput');
    const newPasswordInput = document.getElementById('editNewPasswordInput');
    const confirmNewPasswordInput = document.getElementById('editConfirmNewPasswordInput');

    // Validate if the new password and confirm new password match
    function validateNewPasswordMatch() {
      if (newPasswordInput.value === confirmNewPasswordInput.value && newPasswordInput.value !== '') {
        confirmNewPasswordInput.classList.add('is-valid');
        confirmNewPasswordInput.classList.remove('is-invalid');
      } else {
        confirmNewPasswordInput.classList.add('is-invalid');
        confirmNewPasswordInput.classList.remove('is-valid');
      }
    }

    newPasswordInput.addEventListener('input', validateNewPasswordMatch);
    confirmNewPasswordInput.addEventListener('input', validateNewPasswordMatch);
  });
</script>

<!-- Place this script before the closing </body> tag -->
<script>
  // Get references to the password input fields
  const passwordInput = document.getElementById('passwordInput');
  const confirmPasswordInput = document.getElementById('confirmPasswordInput');

  // Function to validate passwords
  function validatePassword() {
    if (confirmPasswordInput.value === passwordInput.value && confirmPasswordInput.value !== '') {
      // Passwords match and are not empty
      confirmPasswordInput.classList.remove('is-invalid');
      confirmPasswordInput.classList.add('is-valid');
    } else {
      // Passwords do not match
      confirmPasswordInput.classList.remove('is-valid');
      confirmPasswordInput.classList.add('is-invalid');
    }
  }

  // Add event listeners to the password fields
  passwordInput.addEventListener('input', validatePassword);
  confirmPasswordInput.addEventListener('input', validatePassword);
</script>

<!-- Place this script before the closing </body> tag -->
<script>
  // Get references to the form and the discard button
  const addEmployeeForm = document.getElementById('addEmployeeForm');
  const discardButton = document.querySelector('[data-bs-dismiss="modal"]');

  // Function to clear the form when discard is clicked
  function clearForm() {
    addEmployeeForm.reset(); // This will reset the form fields
    // Remove validation classes
    const formControls = addEmployeeForm.querySelectorAll('.form-control');
    formControls.forEach(function(control) {
      control.classList.remove('is-valid', 'is-invalid');
    });
  }

  // Add event listener to the discard button
  discardButton.addEventListener('click', clearForm);
</script>

<script>
  // Function to validate the username
  function checkUsernameAvailability(username) {
    const formData = new FormData();
    formData.append('username', username);

    // AJAX request to check username
    fetch('check_user.php', {
        method: 'POST',
        body: formData,
      })
      .then(response => response.text())
      .then(data => {
        const usernameInput = document.getElementById('usernameInput');
        if (data === 'taken') {
          usernameInput.classList.add('is-invalid'); // Red border
          usernameInput.classList.remove('is-valid'); // Remove green border
        } else {
          usernameInput.classList.remove('is-invalid');
          usernameInput.classList.add('is-valid'); // Green border
        }
      })
      .catch(error => console.error('Error:', error));
  }

  // Function to validate the email address
  function checkEmailAvailability(email) {
    const formData = new FormData();
    formData.append('emailAddress', email);

    // AJAX request to check email
    fetch('check_user.php', {
        method: 'POST',
        body: formData,
      })
      .then(response => response.text())
      .then(data => {
        const emailInput = document.getElementById('emailInput');
        if (data === 'taken') {
          emailInput.classList.add('is-invalid'); // Red border
          emailInput.classList.remove('is-valid'); // Remove green border
        } else {
          emailInput.classList.remove('is-invalid');
          emailInput.classList.add('is-valid'); // Green border
        }
      })
      .catch(error => console.error('Error:', error));
  }

  // Event listeners for username and email input fields
  const usernameInput = document.getElementById('usernameInput');
  const emailInput = document.getElementById('emailInput');

  // Validate username when user types
  usernameInput.addEventListener('input', () => checkUsernameAvailability(usernameInput.value));

  // Validate email when user types
  emailInput.addEventListener('input', () => checkEmailAvailability(emailInput.value));
</script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('input-search');
    const tableRows = document.querySelectorAll('#employeeTableBody tr');

    searchInput.addEventListener('input', function() {
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

<script>
document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('input-search');
  const tableRows = document.querySelectorAll('#employeeTableBody tr');
  const table = document.getElementById('employeeTableBody');

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