<?php
// Start the session if it's not already started in header.php
// session_start(); // Removed as it's handled in header.php

// Include the header (which handles session and database connection)
include '../employee/header.php';

// Check if the user is logged in (redundant if already handled in header.php)
if (!isset($_SESSION['UserID'])) {
    header('Location: ../login/login.php');
    exit();
}

// Fetch current logged-in user's ID
$userID = $_SESSION['UserID'];

// Initialize the user image source with a default placeholder
$userImageSrc = '../assets/images/profile/user-1.jpg';

// Fetch the user's image from the database
if ($stmt = $conn->prepare("SELECT UserImage FROM useraccounts WHERE UserID = ?")) {
    $stmt->bind_param("i", $userID); // Assuming UserID is an integer
    $stmt->execute();
    $stmt->bind_result($userImageData);
    if ($stmt->fetch() && !empty($userImageData)) {
        // Determine the MIME type of the image
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($userImageData);

        // Create a data URI for the image
        $userImageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($userImageData);
    }
    $stmt->close();
}

// Query useraccounts table to get account information
$queryAccount = "SELECT Username, EmailAddress, Role FROM useraccounts WHERE UserID = ?";
$stmtAccount = mysqli_prepare($conn, $queryAccount);
mysqli_stmt_bind_param($stmtAccount, "i", $userID);
mysqli_stmt_execute($stmtAccount);
mysqli_stmt_bind_result($stmtAccount, $dbUsername, $dbEmail, $dbRole);
mysqli_stmt_fetch($stmtAccount);
mysqli_stmt_close($stmtAccount);

// Query employees table to get employee details
$queryEmployees = "
    SELECT FirstName, MiddleInitial, LastName, Gender, Position, DateOfBirth, Address, MobileNo, EmailAddress, EmploymentDate 
    FROM employees 
    WHERE EmployeeID = (SELECT EmployeeID FROM useraccounts WHERE UserID = ?)
";

// Prepare the query
$stmtEmployees = mysqli_prepare($conn, $queryEmployees);

// Bind the UserID parameter
mysqli_stmt_bind_param($stmtEmployees, "i", $userID);

// Execute the query
mysqli_stmt_execute($stmtEmployees);

// Bind the result variables
mysqli_stmt_bind_result(
    $stmtEmployees,
    $dbFirstName,
    $dbMiddleInitial,
    $dbLastName,
    $dbGender,
    $dbPosition,
    $dbDateOfBirth,
    $dbAddress,
    $dbMobileNo,
    $dbEmailAddress,
    $dbEmploymentDate
);

// Fetch the result
mysqli_stmt_fetch($stmtEmployees);

// Close the statement
mysqli_stmt_close($stmtEmployees);

// Close database connection (optional if header.php keeps it open)
mysqli_close($conn);
?>

<div class="body-wrapper">
    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-space-between">
                        <h4 class="mb-4 mb-sm-0 card-title">My Profile</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="../employee/home.php">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="breadcrumb-item" aria-current="page">
                                    <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                        My Profile
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Frontend: Displaying User Information in the Form -->
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <!-- Profile Picture Section -->
                    <div class="col-lg-6 d-flex align-items-stretch">
                        <div class="card w-100 border position-relative overflow-hidden">
                            <div class="card-body p-4">
                                <h4 class="card-title mb-4">Profile Picture</h4>
                                <div class="text-center">
                                    <!-- User's profile picture -->
                                    <img src="<?php echo $userImageSrc; ?>" alt="Profile Image"
                                        class="img-fluid rounded-circle mb-1 profile-img">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information Section -->
                    <div class="col-lg-6 d-flex align-items-stretch">
                        <div class="card w-100 border position-relative overflow-hidden">
                            <div class="card-body p-4">
                                <h4 class="card-title mb-2">Account Information</h4>
                                <form>
                                    <div class="mb-2">
                                        <label for="Username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="Username"
                                            value="<?php echo htmlspecialchars($dbUsername); ?>" readonly>
                                    </div>
                                    <div class="mb-2">
                                        <label for="EmailAddress" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="EmailAddress"
                                            value="<?php echo htmlspecialchars($dbEmail); ?>" readonly>
                                    </div>
                                    <div class="mb-2">
                                        <label for="Role" class="form-label">Role</label>
                                        <input type="text" class="form-control" id="Role"
                                            value="<?php echo htmlspecialchars($dbRole); ?>" readonly>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Details Section -->
                    <div class="col-12">
                        <div class="card w-100 border position-relative overflow-hidden mb-0">
                            <div class="card-body p-4">
                                <h4 class="card-title">Personal Details</h4>
                                <form>
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="FirstName" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="FirstName"
                                                    value="<?php echo htmlspecialchars($dbFirstName); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="MiddleInitial" class="form-label">Middle Initial</label>
                                                <input type="text" class="form-control" id="MiddleInitial"
                                                    value="<?php echo htmlspecialchars($dbMiddleInitial); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="LastName" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="LastName"
                                                    value="<?php echo htmlspecialchars($dbLastName); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="Gender" class="form-label">Gender</label>
                                                <input type="text" class="form-control" id="Gender"
                                                    value="<?php echo htmlspecialchars($dbGender); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="Position" class="form-label">Position</label>
                                                <input type="text" class="form-control" id="Position"
                                                    value="<?php echo htmlspecialchars($dbPosition); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="DateOfBirth" class="form-label">Date of Birth</label>
                                                <input type="text" class="form-control" id="DateOfBirth"
                                                    value="<?php echo htmlspecialchars($dbDateOfBirth); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="MobileNo" class="form-label">Mobile Number</label>
                                                <input type="text" class="form-control" id="MobileNo"
                                                    value="<?php echo htmlspecialchars($dbMobileNo); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="EmploymentDate" class="form-label">Employment Date</label>
                                                <input type="text" class="form-control" id="EmploymentDate"
                                                    value="<?php echo htmlspecialchars($dbEmploymentDate); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="CityAddress" class="form-label">Address</label>
                                                <input type="text" class="form-control" id="CityAddress"
                                                    value="<?php echo htmlspecialchars($dbAddress); ?>" readonly>
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

        <!-- Optional: Custom CSS to ensure the image is responsive -->
        <style>
            .profile-img {
                width: 100%;
                max-width: 300px;
                height: auto;
                /* Optional: Add a border to the profile image */
            }

            @media (max-width: 576px) {
                .profile-img {
                    max-width: 100%;
                }
            }
        </style>

        <!-- JavaScript for toggling password visibility (if applicable) -->
        <script>
            const togglePassword = document.querySelector('#togglePassword');
            const passwordField = document.querySelector('#Password');

            if (togglePassword && passwordField) {
                togglePassword.addEventListener('click', function () {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    this.classList.toggle('bi-eye');
                    this.classList.toggle('bi-eye-slash');
                });
            }
        </script>
    </div>
    <?php
    include '../employee/footer.php';
    ?>