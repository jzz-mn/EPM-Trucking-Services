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
                    Officers
                  </span>
                </li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>
    <h5 class="border-bottom py-2 px-4 mb-4">Officers</h5>
    <div class="widget-content searchable-container list">
      <!-- Add Officer Modal -->
      <div class="modal fade" id="addContactModal" tabindex="-1" role="dialog" aria-labelledby="addOfficerModalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header d-flex align-items-center bg-primary">
              <h5 class="modal-title text-white fs-4">Add Officer Details</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="add-contact-box">
                <div class="add-contact-content">
                  <form id="addOfficerForm" method="POST" action="add_officer.php">
                    <div class="row">
                      <!-- Profile Picture Section -->
                      <div class="col-lg-6 d-flex align-items-stretch">
                        <div class="card w-100 border position-relative overflow-hidden">
                          <div class="card-body p-4">
                            <h4 class="card-title">Add Profile Picture</h4>
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
                      <!-- Create Account Section -->
                      <div class="col-lg-6 d-flex align-items-stretch">
                        <div class="card w-100 border position-relative overflow-hidden">
                          <div class="card-body p-4">
                            <h4 class="card-title">Create Account</h4>
                            <p class="card-subtitle mb-4">Please enter the officer's login credentials.</p>
                            <div class="mb-3">
                              <label for="usernameInput" class="form-label">Username</label>
                              <input type="text" class="form-control" id="usernameInput" name="username"
                                placeholder="Enter username" required>
                              <div class="invalid-feedback">Username is already taken.</div>
                            </div>
                            <div class="mb-3">
                              <label for="emailInput" class="form-label">Email Address</label>
                              <input type="email" class="form-control" id="emailInput" name="emailAddress"
                                placeholder="Enter email" required>
                              <div class="invalid-feedback">Email is already taken.</div>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Set Password</label><br>
                              <input type="radio" id="autoPassword" name="passwordOption" value="auto" checked
                                onclick="togglePasswordFields()">
                              <label for="autoPassword">Automatically Generate Password</label><br>
                              <input type="radio" id="manualPassword" name="passwordOption" value="manual"
                                onclick="togglePasswordFields()">
                              <label for="manualPassword">Set Password Manually</label>
                            </div>
                            <div id="passwordFields" style="display: none;">
                              <div class="mb-3">
                                <label for="passwordInput" class="form-label">Temporary Password</label>
                                <input type="password" class="form-control" id="passwordInput" name="password"
                                  placeholder="Enter temporary password">
                              </div>
                              <div class="mb-3">
                                <label for="confirmPasswordInput" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirmPasswordInput"
                                  name="confirmPassword" placeholder="Confirm password">
                                <div class="invalid-feedback">Passwords do not match.</div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- Personal Details Section -->
                    <div class="card w-100 border position-relative overflow-hidden mt-4">
                      <div class="card-body p-4">
                        <h4 class="card-title">Personal Details</h4>
                        <p class="card-subtitle mb-4">Fill in the officer's personal details below.</p>
                        <div class="row">
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="firstName" class="form-label">First Name</label>
                              <input type="text" class="form-control" id="firstName" name="firstName"
                                placeholder="First Name" required>
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="middleInitial" class="form-label">Middle Initial</label>
                              <input type="text" class="form-control" id="middleInitial" name="middleInitial"
                                placeholder="Middle Initial">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="lastName" class="form-label">Last Name</label>
                              <input type="text" class="form-control" id="lastName" name="lastName"
                                placeholder="Last Name" required>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="gender" class="form-label">Gender</label>
                              <select type="text" class="form-control" id="gender" name="gender" placeholder="Gender"
                                required>
                                <option value="MALE">MALE</option>
                                <option value="FEMALE">FEMALE</option>
                              </select>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="college" class="form-label">College</label>
                              <input type="text" class="form-control" id="college" name="college" placeholder="College">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="program" class="form-label">Program</label>
                              <input type="text" class="form-control" id="program" name="program" placeholder="Program">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="yearGraduated" class="form-label">Year Graduated</label>
                              <input type="text" class="form-control" id="yearGraduated" name="yearGraduated"
                                placeholder="Year Graduated">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="mobile" class="form-label">Mobile Number</label>
                              <input type="tel" class="form-control" id="mobile" name="mobileNo"
                                placeholder="Mobile Number" required>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="position" class="form-label">Position</label>
                              <select type="text" class="form-control" id="position" name="position"
                                placeholder="Position" required>
                                <option value="Proprietor">Proprietor</option>
                                <option value="Operation Manager/Proprietor">Operation Manager/Proprietor</option>
                                <option value="Asst. Operation Manager">Asst. Operation Manager</option>
                                <option value="Secretary">Secretary</option>
                              </select>
                            </div>
                          </div>
                          <div class="col-12">
                            <div class="mb-3">
                              <label for="address" class="form-label">Address</label>
                              <input type="text" class="form-control" id="address" name="address" placeholder="Address"
                                required>
                            </div>
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
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Add this script in your officers.php to handle the password option -->
      <script>
        function togglePasswordFields() {
          var passwordOption = document.querySelector('input[name="passwordOption"]:checked').value;
          if (passwordOption === 'manual') {
            document.getElementById('passwordFields').style.display = 'block';
            document.getElementById('passwordInput').required = true;
            document.getElementById('confirmPasswordInput').required = true;
          } else {
            document.getElementById('passwordFields').style.display = 'none';
            document.getElementById('passwordInput').required = false;
            document.getElementById('confirmPasswordInput').required = false;
          }
        }

        // Call the function on page load to set the initial state
        document.addEventListener('DOMContentLoaded', function () {
          togglePasswordFields();
        });
      </script>

      <!-- Edit Officer Modal -->
      <div class="modal fade" id="editContactModal" tabindex="-1" role="dialog" aria-labelledby="editContactModalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header d-flex align-items-center bg-primary">
              <h5 class="modal-title text-white fs-4">Edit Officer Details</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="edit-contact-box">
                <div class="edit-contact-content">
                  <form id="editOfficerForm" method="POST" action="edit_officer.php">
                    <input type="hidden" name="officerID" value="">
                    <!-- Hidden field for UserID -->
                    <input type="hidden" name="userID" value="" id="userID">
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

                      <!-- Edit Account Section -->
                      <div class="col-lg-6 d-flex align-items-stretch">
                        <div class="card w-100 border position-relative overflow-hidden">
                          <div class="card-body p-4">
                            <h4 class="card-title">Edit Account</h4>
                            <p class="card-subtitle mb-4">Update officer's login credentials.</p>
                            <div class="mb-3">
                              <label for="usernameInput" class="form-label">Username</label>
                              <input type="text" class="form-control" id="usernameInput" name="username"
                                placeholder="Enter username" required>
                            </div>
                            <div class="mb-3">
                              <label for="userEmailAddress" class="form-label">Email Address</label>
                              <input type="email" class="form-control" id="userEmailAddress" name="userEmailAddress"
                                placeholder="Enter Email Address" required>
                            </div>
                            <div class="mb-3">
                              <label for="activationStatus" class="form-label">Activation Status</label>
                              <select class="form-control" id="activationStatus" name="activationStatus">
                                <option value="activated">Activated</option>
                                <option value="deactivated">Deactivated</option>
                              </select>
                            </div>
                            <!-- Reset Password Button -->
                            <div class="mb-3">
                              <button type="button" class="btn btn-warning" id="resetPasswordButton">Reset
                                Password</button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Personal Details Section -->
                    <div class="card w-100 border position-relative overflow-hidden mt-4">
                      <div class="card-body p-4">
                        <h4 class="card-title">Personal Details</h4>
                        <p class="card-subtitle mb-4">Update officer's personal details below.</p>
                        <div class="row">
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="firstName" class="form-label">First Name</label>
                              <input type="text" class="form-control" id="firstName" name="firstName"
                                placeholder="First Name" required>
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="middleInitial" class="form-label">Middle Initial</label>
                              <input type="text" class="form-control" id="middleInitial" name="middleInitial"
                                placeholder="Middle Initial">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="lastName" class="form-label">Last Name</label>
                              <input type="text" class="form-control" id="lastName" name="lastName"
                                placeholder="Last Name" required>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="gender" class="form-label">Gender</label>
                              <select type="text" class="form-control" id="gender" name="gender" placeholder="Gender"
                                required>
                                <option value="MALE">MALE</option>
                                <option value="FEMALE">FEMALE</option>
                              </select>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="college" class="form-label">College</label>
                              <input type="text" class="form-control" id="college" name="college" placeholder="College">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="program" class="form-label">Program</label>
                              <input type="text" class="form-control" id="program" name="program" placeholder="Program">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="yearGraduated" class="form-label">Year Graduated</label>
                              <input type="text" class="form-control" id="yearGraduated" name="yearGraduated"
                                placeholder="Year Graduated">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="mobile" class="form-label">Mobile Number</label>
                              <input type="tel" class="form-control" id="mobile" name="mobileNo"
                                placeholder="Mobile Number" required>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="position" class="form-label">Position</label>
                              <select type="text" class="form-control" id="position" name="position"
                                placeholder="Position" required>
                                <option value="Proprietor">Proprietor</option>
                                <option value="Operation Manager/Proprietor">Operation Manager/Proprietor</option>
                                <option value="Asst. Operation Manager">Asst. Operation Manager</option>
                                <option value="Secretary">Secretary</option>
                              </select>
                            </div>
                          </div>
                          <div class="col-12">
                            <div class="mb-3">
                              <label for="address" class="form-label">Address</label>
                              <input type="text" class="form-control" id="address" name="address" placeholder="Address"
                                required>
                            </div>
                          </div>
                          <div class="col-12 mb-3">
                            <div class="d-flex gap-6 m-0 justify-content-end">
                              <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Discard</button>
                              <button id="btn-update" class="btn btn-primary" type="submit">Update</button>
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
    </div>

    <?php
    include '../includes/db_connection.php';
    // Fetch officer data from the database
    $sql = "SELECT OfficerID, FirstName, MiddleInitial, LastName, Position, Gender, CityAddress, MobileNo, EmailAddress, College, Program, YearGraduated FROM officers";
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
            <i class="ti ti-users text-white me-1 fs-5"></i> Add Officer
          </a>

        </div>
      </div>
    </div>
    <div class="table-responsive p-0 card card-body">
      <table id="" class="table table-striped table-bordered text-nowrap align-middle">
        <thead>
          <tr>
            <th>Name</th>
            <th>Position</th>
            <th>Activation Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Your query to join the two tables based on officerID
          $query = "SELECT o.OfficerID, o.FirstName, o.MiddleInitial, o.LastName, o.Position, o.Gender, o.CityAddress, 
          o.MobileNo, o.EmailAddress, o.College, o.YearGraduated, ua.ActivationStatus
          FROM officers o
          JOIN useraccounts ua ON o.OfficerID = ua.officerID";
          $result = $conn->query($query);

          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              // Combine first name, middle initial, and last name
              $fullName = "{$row['FirstName']} {$row['MiddleInitial']} {$row['LastName']}";

              // Assign badges for specific positions
              $positionBadge = '';
              if ($row['Position'] === 'Proprietor') {
                $positionBadge = "<span class='badge text-bg-primary'>Proprietor</span>";
              } elseif ($row['Position'] === 'Operation Manager/Proprietor') {
                $positionBadge = "<span class='badge text-bg-secondary'>Operation Manager/Proprietor</span>";
              } elseif ($row['Position'] === 'Asst. Operation Manager') {
                $positionBadge = "<span class='badge text-bg-danger'>Asst. Operation Manager</span>";
              } else {
                $positionBadge = "<span class='badge text-bg-secondary'>{$row['Position']}</span>";
              }

              // Format the mobile number (assuming a similar format)
              $formattedMobileNo = preg_replace('/(\d{4})(\d{3})(\d{4})/', '$1-$2-$3', $row['MobileNo']);

              // Handle ActivationStatus display
              $activationStatus = '';
              if ($row['ActivationStatus'] === 'Activated' || $row['ActivationStatus'] === 'activated') {
                $activationStatus = "<span class='badge text-bg-success'>Activated</span>";
              } elseif ($row['ActivationStatus'] === 'Deactivated' || $row['ActivationStatus'] === 'deactivated') {
                $activationStatus = "<span class='badge text-bg-danger'>Deactivated</span>";
              }


              // Output table rows
              echo "<tr>";
              echo "<td><div class='d-flex align-items-center'>";
              echo "<img src='../assets/images/profile/user-1.jpg' class='rounded-circle' width='40' height='40' />";
              echo "<div class='ms-3'>";
              echo "<h6 class='fs-4 fw-semibold mb-0'>{$fullName}</h6>";
              echo "</div></div></td>";
              echo "<td>{$positionBadge}</td>";
              echo "<td>{$activationStatus}</td>";
              echo "<td>";
              echo "<a href='#' class='me-3 text-primary edit-button' data-officerid='{$row['OfficerID']}' data-bs-toggle='modal' data-bs-target='#editContactModal'>";
              echo "<i class='fs-4 ti ti-edit'></i></a>";
              echo "</td>";
              echo "</tr>";
            }
          } else {
            echo "<tr><td colspan='10' class='text-center'>No officers found</td></tr>";
          }
          $conn->close();
          ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(document).ready(function () {
    $('.edit-button').on('click', function () {
      var officerID = $(this).data('officerid');

      $.ajax({
        url: 'fetch_officer.php',
        type: 'POST',
        data: {
          officerID: officerID
        },
        dataType: 'json',
        success: function (data) {
          if (data.success) {
            // Populate the modal fields
            $('#editOfficerForm input[name="officerID"]').val(data.officer.OfficerID);
            $('#editOfficerForm input[name="firstName"]').val(data.officer.FirstName);
            $('#editOfficerForm input[name="middleInitial"]').val(data.officer.MiddleInitial);
            $('#editOfficerForm input[name="lastName"]').val(data.officer.LastName);
            $('#editOfficerForm select[name="gender"]').val(data.officer.Gender);
            $('#editOfficerForm input[name="college"]').val(data.officer.College);
            $('#editOfficerForm input[name="program"]').val(data.officer.Program);
            $('#editOfficerForm input[name="yearGraduated"]').val(data.officer.YearGraduated);
            $('#editOfficerForm input[name="mobileNo"]').val(data.officer.MobileNo);
            $('#editOfficerForm select[name="position"]').val(data.officer.Position);
            $('#editOfficerForm input[name="address"]').val(data.officer.CityAddress);
            $('#editOfficerForm input[name="emailAddress"]').val(data.officer.EmailAddress);
            $('#editOfficerForm input[name="username"]').val(data.officer.Username);
            $('#editOfficerForm input[name="userEmailAddress"]').val(data.officer.UserEmail);
            $('#editOfficerForm select[name="activationStatus"]').val(data.officer.ActivationStatus.toLowerCase());
            $('#editOfficerForm input[name="userID"]').val(data.officer.UserID); // Added UserID

          } else {
            alert('Failed to fetch officer data.');
          }
        },
        error: function () {
          alert('Error in AJAX request.');
        }
      });
    });

    // Handle "Reset Password" button click
    $('#resetPasswordButton').on('click', function () {
      var userID = $('#editOfficerForm input[name="userID"]').val();

      if (confirm('Are you sure you want to reset the password for this officer?')) {
        // Send AJAX request to reset password
        $.ajax({
          url: 'reset_password.php',
          type: 'POST',
          dataType: 'json',
          data: {
            userID: userID,
            role: 'Officer' // Indicate that this is an officer account
          },
          success: function (data) {
            if (data.success) {
              alert('Password reset successfully. An email has been sent to the officer.');
            } else {
              alert('Error resetting password: ' + data.message);
            }
          },
          error: function () {
            alert('An error occurred while resetting the password.');
          }
        });
      }
    });
  });
</script>


<script>
  $(document).ready(function () {
    $('#editOfficerForm').submit(function (event) {
      event.preventDefault(); // Prevent the form from submitting via the browser.
      var formData = $(this).serialize(); // Get form data.

      $.ajax({
        type: "POST",
        url: "edit_officer.php",
        data: formData,
        dataType: "json",
        success: function (response) {
          if (response.user_message) {
            $('#editContactModal').modal('hide'); // Hide the modal if success
            window.location.href = "officers.php?message=Officer updated successfully"; // Redirect with success message
          } else {
            alert('Failed to update: ' + response.error);
          }
        },
        error: function () {
          alert('Error updating officer.');
        }
      });
    });
  });
</script>

<!-- JavaScript for Real-Time Validation -->
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

  // Function to validate password matching
  function validatePasswordMatch() {
    const passwordInput = document.getElementById('passwordInput');
    const confirmPasswordInput = document.getElementById('confirmPasswordInput');

    if (passwordInput.value === confirmPasswordInput.value && passwordInput.value !== '') {
      confirmPasswordInput.classList.remove('is-invalid');
      confirmPasswordInput.classList.add('is-valid');
    } else {
      confirmPasswordInput.classList.add('is-invalid');
      confirmPasswordInput.classList.remove('is-valid');
    }
  }

  // Event listeners for username and email input fields
  const usernameInput = document.getElementById('usernameInput');
  const emailInput = document.getElementById('emailInput');
  const passwordInput = document.getElementById('passwordInput');
  const confirmPasswordInput = document.getElementById('confirmPasswordInput');

  // Validate username when user types
  usernameInput.addEventListener('input', () => checkUsernameAvailability(usernameInput.value));

  // Validate email when user types
  emailInput.addEventListener('input', () => checkEmailAvailability(emailInput.value));

  // Validate password match when user types
  passwordInput.addEventListener('input', validatePasswordMatch);
  confirmPasswordInput.addEventListener('input', validatePasswordMatch);
</script>



<?php
include '../officer/footer.php';
?>