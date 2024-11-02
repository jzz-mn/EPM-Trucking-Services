<?php
session_start();

include '../officer/header.php';
include '../includes/db_connection.php';

if (!isset($_SESSION['UserID'])) {
    // Redirect to login page if no session exists
    header('location: ../login/login.php');
    exit();
}

// Fetch current logged-in user's ID
$userID = $_SESSION['UserID'];

// Initialize the user image source with a default placeholder
$userImageSrc = '../assets/images/profile/user-1.jpg';

// Get the user's image from the database
if ($stmt = $conn->prepare("SELECT UserImage FROM useraccounts WHERE UserID = ?")) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($userImageData);
    if ($stmt->fetch() && !empty($userImageData)) {
        // Get the MIME type of the image
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($userImageData);

        // Build the data URI for the image
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

// Query officers table to get personal details
$queryOfficer = "SELECT FirstName, MiddleInitial, LastName, Position, Gender, CityAddress, MobileNo, College, Program, YearGraduated FROM officers WHERE OfficerID = (SELECT OfficerID FROM useraccounts WHERE UserID = ?)";
$stmtOfficer = mysqli_prepare($conn, $queryOfficer);
mysqli_stmt_bind_param($stmtOfficer, "i", $userID);
mysqli_stmt_execute($stmtOfficer);
mysqli_stmt_bind_result($stmtOfficer, $dbFirstName, $dbMiddleInitial, $dbLastName, $dbPosition, $dbGender, $dbCityAddress, $dbMobileNo, $dbCollege, $dbProgram, $dbYearGraduated);
mysqli_stmt_fetch($stmtOfficer);
mysqli_stmt_close($stmtOfficer);

// Query employees table to get employee details (if applicable)
$queryEmployees = "SELECT DateOfBirth, Position, EmploymentDate FROM employees WHERE EmployeeID = (SELECT EmployeeID FROM useraccounts WHERE UserID = ?)";
$stmtEmployees = mysqli_prepare($conn, $queryEmployees);
mysqli_stmt_bind_param($stmtEmployees, "i", $userID);
mysqli_stmt_execute($stmtEmployees);
mysqli_stmt_bind_result($stmtEmployees, $dbDateOfBirth, $dbPosition, $dbEmploymentDate);
mysqli_stmt_fetch($stmtEmployees);
mysqli_stmt_close($stmtEmployees);

// Close database connection
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
                                    <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
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
                                                <label for="MobileNo" class="form-label">Mobile Number</label>
                                                <input type="text" class="form-control" id="MobileNo"
                                                    value="<?php echo htmlspecialchars($dbMobileNo); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="College" class="form-label">College</label>
                                                <input type="text" class="form-control" id="College"
                                                    value="<?php echo htmlspecialchars($dbCollege); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="Program" class="form-label">Program</label>
                                                <input type="text" class="form-control" id="Program"
                                                    value="<?php echo htmlspecialchars($dbProgram); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="YearGraduated" class="form-label">Year Graduated</label>
                                                <input type="text" class="form-control" id="YearGraduated"
                                                    value="<?php echo htmlspecialchars($dbYearGraduated); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="Position" class="form-label">Position</label>
                                                <input type="text" class="form-control" id="Position"
                                                    value="<?php echo htmlspecialchars($dbPosition); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="mb-3">
                                                <label for="CityAddress" class="form-label">Address</label>
                                                <input type="text" class="form-control" id="CityAddress"
                                                    value="<?php echo htmlspecialchars($dbCityAddress); ?>" readonly>
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
            }

            @media (max-width: 576px) {
                .profile-img {
                    max-width: 100%;
                }
            }
        </style>

        <!-- JavaScript for toggling password visibility -->
        <script>
            const togglePassword = document.querySelector('#togglePassword');
            const passwordField = document.querySelector('#Password');

            togglePassword.addEventListener('click', function () {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });
        </script>

    </div>
    <?php
    include '../officer/footer.php';
    ?>