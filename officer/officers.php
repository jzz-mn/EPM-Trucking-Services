<?php
$allowedRoles = ['SuperAdmin', 'Officer'];

// Include the authentication script
require_once '../includes/auth.php';
include '../officer/header.php';
// Check if the user has the SuperAdmin role
if ($_SESSION['Role'] !== 'SuperAdmin') {
  // Display an access denied message or redirect to an error page
  echo "Access denied. This page is only accessible to SuperAdmin.";
  exit();
}
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
                  <form id="addOfficerForm" method="POST" action="add_officer.php" enctype="multipart/form-data">
                    <div class="row">
                      <!-- Profile Picture Section -->
                      <div class="col-lg-6 d-flex align-items-stretch">
                        <div class="card w-100 border position-relative overflow-hidden">
                          <div class="card-body p-4">
                            <h4 class="card-title">Add Profile Picture</h4>
                            <p class="card-subtitle mb-4">Upload a profile picture here.</p>
                            <div class="text-center">
                              <img id="previewAdd" src="../assets/images/profile/user-1.jpg" alt="profile-img"
                                class="img-fluid rounded-circle my-4" width="140" height="140">
                              <div class="d-flex align-items-center justify-content-center my-4 gap-6">
                                <!-- File input for image upload -->
                                <input type="file" id="addProfilePicture" name="profilePicture"
                                  accept=".jpg,.jpeg,.png,.gif" class="form-control" required>
                              </div>
                              <p class="mb-0">Allowed JPG, GIF, or PNG. Max size of 800KB.</p>
                            </div>
                          </div>
                        </div>
                      </div>
                      <script>
                        // Preview selected profile picture in the Add Officer modal
                        document.getElementById('addProfilePicture').addEventListener('change', function(event) {
                          const [file] = event.target.files;
                          if (file) {
                            document.getElementById('previewAdd').src = URL.createObjectURL(file);
                          }
                        });
                      </script>

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
                              <input type="hidden" id="autoPassword" name="passwordOption" value="auto" checked
                                onclick="togglePasswordFields()">
                              <label for="autoPassword">Automatically Generate Password</label><br>
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
                                placeholder="First Name" required pattern="[A-Za-z\s]+" title="Please enter letters and spaces only" onkeypress="return /[A-Za-z\s]/.test(event.key)">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="middleInitial" class="form-label">Middle Initial</label>
                              <input type="text" class="form-control" id="middleInitial" name="middleInitial"
                                placeholder="Middle Initial" pattern="[A-Za-z\s]?" title="Please enter a single letter or leave blank" onkeypress="return /[A-Za-z\s]/.test(event.key)">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="lastName" class="form-label">Last Name</label>
                              <input type="text" class="form-control" id="lastName" name="lastName"
                                placeholder="Last Name" required pattern="[A-Za-z\s]+" title="Please enter letters and spaces only" onkeypress="return /[A-Za-z\s]/.test(event.key)">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="gender" class="form-label">Gender</label>
                              <select class="form-control" id="gender" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
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
                                placeholder="Year Graduated" pattern="\d*" title="Please enter numbers only" onkeypress="return /[0-9]/.test(event.key)">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="mobile" class="form-label">Mobile Number</label>
                              <input type="tel" class="form-control" id="mobile" name="mobileNo"
                                placeholder="Mobile Number" required pattern="[0-9]*" title="Please enter numbers only" onkeypress="return /[0-9]/.test(event.key)">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="position" class="form-label">Position</label>
                              <input type="text" class="form-control" id="position" name="position"
                                placeholder="Position" required>
                              </input>
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
        document.addEventListener('DOMContentLoaded', function() {
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
                  <form id="editOfficerForm" method="POST" action="edit_officer.php" enctype="multipart/form-data">
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
                              <!-- Image preview -->
                              <img id="previewEdit" src="../assets/images/profile/user-1.jpg" alt="profile-img"
                                class="img-fluid rounded-circle my-4" width="140" height="140">
                              <div class="d-flex align-items-center justify-content-center my-4 gap-6">
                                <!-- File input for image upload -->
                                <input type="file" id="editProfilePicture" name="profilePicture"
                                  accept=".jpg,.jpeg,.png,.gif" class="form-control">
                              </div>
                              <p class="mb-0">Allowed JPG, GIF, or PNG. Max size of 800KB.</p>
                            </div>
                          </div>
                        </div>
                      </div>
                      <script>
                        // Preview selected profile picture in the Edit Officer modal
                        document.getElementById('editProfilePicture').addEventListener('change', function(event) {
                          const [file] = event.target.files;
                          if (file) {
                            document.getElementById('previewEdit').src = URL.createObjectURL(file);
                          }
                        });
                      </script>

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
                                <option value="Activated">Activated</option>
                                <option value="Deactivated">Deactivated</option>
                              </select>
                            </div>
                            <!-- Reset Password Button -->
                            <div class="mb-3">
                              <button type="button" class="btn bg-danger-subtle text-danger"
                                id="resetPasswordButton">Reset Password</button>
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
                                placeholder="First Name" required pattern="[A-Za-z\s]+" title="Please enter letters and spaces only" onkeypress="return /[A-Za-z\s]/.test(event.key)">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="middleInitial" class="form-label">Middle Initial</label>
                              <input type="text" class="form-control" id="middleInitial" name="middleInitial"
                                placeholder="Middle Initial" pattern="[A-Za-z\s]?" title="Please enter a single letter or leave blank" onkeypress="return /[A-Za-z\s]/.test(event.key)">
                            </div>
                          </div>
                          <div class="col-lg-4">
                            <div class="mb-3">
                              <label for="lastName" class="form-label">Last Name</label>
                              <input type="text" class="form-control" id="lastName" name="lastName"
                                placeholder="Last Name" required pattern="[A-Za-z\s]+" title="Please enter letters and spaces only" onkeypress="return /[A-Za-z\s]/.test(event.key)">
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <div class="mb-3">
                              <label for="gender" class="form-label">Gender</label>
                              <select class="form-control" id="gender" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
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
                                placeholder="Year Graduated" required pattern="\d*" title="Please enter numbers only" onkeypress="return /[0-9]/.test(event.key)">
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
                              <input type="text" class="form-control" id="position" name="position"
                                placeholder="Position" required>
                              </input>
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
  o.MobileNo, o.EmailAddress, o.College, o.YearGraduated, ua.ActivationStatus, ua.UserImage
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
              // Fetch the user's profile picture
              $userImageSrc = '../assets/images/profile/user-1.jpg'; // Default placeholder

              if (!empty($row['UserImage'])) {
                // Detect the MIME type
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($row['UserImage']);
                $userImageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($row['UserImage']);
              }


              // Output table rows
              echo "<tr>";
              echo "<td><div class='d-flex align-items-center'>";
              echo "<img src='{$userImageSrc}' class='rounded-circle' width='40' height='40' />";
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

<!-- Include SweetAlert2 CSS and JS via CDN -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  $(document).ready(function() {
    $('.edit-button').on('click', function() {
      var officerID = $(this).data('officerid');

      $.ajax({
        url: 'fetch_officer.php',
        type: 'POST',
        data: {
          officerID: officerID
        },
        dataType: 'json',
        success: function(data) {
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
            $('#editOfficerForm input[name="position"]').val(data.officer.Position); // Corrected to input
            $('#editOfficerForm input[name="address"]').val(data.officer.CityAddress);
            $('#editOfficerForm input[name="emailAddress"]').val(data.officer.EmailAddress);
            $('#editOfficerForm input[name="username"]').val(data.officer.Username);
            $('#editOfficerForm input[name="userEmailAddress"]').val(data.officer.UserEmail);
            $('#editOfficerForm select[name="activationStatus"]').val(data.officer.ActivationStatus); // Removed toLowerCase()
            $('#editOfficerForm input[name="userID"]').val(data.officer.UserID);

            // Load the profile picture
            const profilePicElement = document.getElementById('previewEdit');
            if (data.officer.UserImage) {
              // Detect the MIME type from the server if possible
              profilePicElement.src = `data:image/jpeg;base64,${data.officer.UserImage}`;
            } else {
              profilePicElement.src = '../assets/images/profile/user-1.jpg';
            }
          } else {
            alert('Failed to fetch officer data.');
          }
        },
        error: function() {
          alert('Error in AJAX request.');
        }
      });
    });

    // Handle "Reset Password" button click
    const resetPasswordButton = document.getElementById('resetPasswordButton');

    resetPasswordButton.addEventListener('click', function() {
      // Confirmation dialog
      Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to reset the password for this officer?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, reset it!'
      }).then((result) => {
        if (result.isConfirmed) {
          const officerID = document.getElementById('editOfficerForm').querySelector('input[name="officerID"]').value;

          // Show loading modal
          Swal.fire({
            title: 'Resetting Password...',
            text: 'Please wait while we process your request.',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Prepare form data with officerID
          const formData = new FormData();
          formData.append('officerID', officerID);

          // Send the fetch request
          fetch('../officer/reset_password.php', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              Swal.close(); // Close the loading modal
              if (data.success) {
                // Success notification
                Swal.fire({
                  icon: 'success',
                  title: 'Password Reset Successfully!',
                  text: 'An email has been sent to the officer.',
                  confirmButtonText: 'OK'
                });

                // Update button UI
                resetPasswordButton.classList.remove('bg-danger-subtle', 'text-danger');
                resetPasswordButton.classList.add('btn-success', 'text-white');
                resetPasswordButton.textContent = 'Password Reset';
                resetPasswordButton.disabled = true; // Disable button after reset
              } else {
                // Error notification
                Swal.fire({
                  icon: 'error',
                  title: 'Error!',
                  text: data.message || 'An unexpected error occurred.',
                  confirmButtonText: 'OK'
                });
              }
            })
            .catch(error => {
              Swal.close(); // Close the loading modal
              // Network error notification
              Swal.fire({
                icon: 'error',
                title: 'Request Failed',
                text: 'An error occurred while processing your request. Please try again later.',
                confirmButtonText: 'OK'
              });
              console.error('Error resetting password:', error);
            });
        }
      });
    });
  });
</script>

<script>
  $(document).ready(function() {
    $('#editOfficerForm').submit(function(event) {
      event.preventDefault();
      var formData = new FormData(this);

      $.ajax({
        type: "POST",
        url: "edit_officer.php",
        data: formData,
        dataType: "json",
        processData: false,
        contentType: false,
        success: function(response) {
          if (response.user_message) {
            $('#editContactModal').modal('hide'); // Hide the modal if success
            window.location.href = "officers.php?message=Officer updated successfully"; // Redirect with success message
          } else {
            alert('Failed to update: ' + response.error);
          }
        },
        error: function() {
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

<script>
  document.getElementById('input-search').addEventListener('keyup', function() {
    var searchTerm = this.value.toLowerCase();
    var tableRows = document.querySelectorAll('tbody tr');

    tableRows.forEach(function(row) {
      var nameCell = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
      var positionCell = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
      var statusCell = row.querySelector('td:nth-child(3)').textContent.toLowerCase();

      if (nameCell.includes(searchTerm) || positionCell.includes(searchTerm) || statusCell.includes(searchTerm)) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  });
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Function to get URL parameters
  function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
  }

  // Handle Add Officer Form Submission with SweetAlert confirmation
  const addOfficerForm = document.getElementById('addOfficerForm');

  if (addOfficerForm) {
    addOfficerForm.addEventListener('submit', function(event) {
      event.preventDefault(); // Prevent the default form submission

      // Optionally perform additional client-side validations here

      // Show confirmation dialog
      Swal.fire({
        title: 'Confirm Account Creation',
        text: "Are you sure you want to add this officer?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, add officer!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // Optionally show a loading indicator
          Swal.fire({
            title: 'Adding Officer...',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Submit the form programmatically after confirmation
          addOfficerForm.submit();
        }
      });
    });
  }

  // Display Success Message if 'message' parameter exists in URL
  const successMessage = getQueryParam('message');
  if (successMessage) {
    Swal.fire({
      icon: 'success',
      title: 'Success',
      text: successMessage,
      confirmButtonText: 'OK'
    });
  }

  // Display Error Message if 'error' parameter exists in URL
  const errorMessage = getQueryParam('error');
  if (errorMessage) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: errorMessage,
      confirmButtonText: 'OK'
    });
  }
});
</script>

<?php
include '../officer/footer.php';
?>