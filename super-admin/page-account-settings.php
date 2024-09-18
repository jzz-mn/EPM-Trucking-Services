<?php
// Start the session at the very beginning
session_start();

// Include the header and database connection
include '../includes/header.php';
include '../includes/db_connection.php';

// Check if the user is not logged in
if (!isset($_SESSION['UserID'])) {
    // Redirect to login page if no session exists
    header('location: ../login/login.php');
    exit();
}

// Fetch current logged-in user's ID
$userID = $_SESSION['UserID'];

// Query useraccounts table to get account information
$queryAccount = "SELECT Username, EmailAddress, Role, Password FROM useraccounts WHERE UserID = ?";
$stmtAccount = mysqli_prepare($conn, $queryAccount);
mysqli_stmt_bind_param($stmtAccount, "s", $userID);
mysqli_stmt_execute($stmtAccount);
mysqli_stmt_bind_result($stmtAccount, $dbUsername, $dbEmail, $dbRole, $dbPassword);
mysqli_stmt_fetch($stmtAccount);
mysqli_stmt_close($stmtAccount);

// Query officers table to get personal details
$queryOfficer = "SELECT FirstName, MiddleInitial, LastName, Position, Gender, CityAddress, MobileNo, College, Program, YearGraduated FROM officers WHERE OfficerID = (SELECT OfficerID FROM useraccounts WHERE UserID = ?)";
$stmtOfficer = mysqli_prepare($conn, $queryOfficer);
mysqli_stmt_bind_param($stmtOfficer, "s", $userID);
mysqli_stmt_execute($stmtOfficer);
mysqli_stmt_bind_result($stmtOfficer, $dbFirstName, $dbMiddleInitial, $dbLastName, $dbPosition, $dbGender, $dbCityAddress, $dbMobileNo, $dbCollege, $dbProgram, $dbYearGraduated);
mysqli_stmt_fetch($stmtOfficer);
mysqli_stmt_close($stmtOfficer);

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
                                    <a class="text-muted text-decoration-none d-flex" href="../main/index.html">
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
                    <div class="col-lg-6">
                        <div class="card w-100 border position-relative overflow-hidden">
                            <div class="card-body p-4">
                                <h4 class="card-title">Profile Picture</h4>
                                <div class="text-center">
                                    <img src="../assets/images/profile/user-1.jpg" alt="Profile Image" class="img-fluid rounded-circle" width="280" height="280">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information Section -->
                    <div class="col-lg-6">
                        <div class="card w-100 border position-relative overflow-hidden">
                            <div class="card-body p-4">
                                <h4 class="card-title mb-2">Account Information</h4>
                                <form>
                                    <div class="mb-2">
                                        <label for="Username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="Username" value="<?php echo htmlspecialchars($dbUsername); ?>" readonly>
                                    </div>
                                    <div class="mb-2">
                                        <label for="EmailAddress" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="EmailAddress" value="<?php echo htmlspecialchars($dbEmail); ?>" readonly>
                                    </div>
                                    <div class="mb-2">
                                        <label for="Password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="Password" value="<?php echo htmlspecialchars($dbPassword); ?>" readonly>
                                    </div>
                                    <div class="mb-2">
                                        <label for="Role" class="form-label">Role</label>
                                        <input type="role" class="form-control" id="Role" value="<?php echo htmlspecialchars($dbRole); ?>" readonly>
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
                                                <input type="text" class="form-control" id="FirstName" value="<?php echo htmlspecialchars($dbFirstName); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="MiddleInitial" class="form-label">Middle Initial</label>
                                                <input type="text" class="form-control" id="MiddleInitial" value="<?php echo htmlspecialchars($dbMiddleInitial); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="LastName" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="LastName" value="<?php echo htmlspecialchars($dbLastName); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="Gender" class="form-label">Gender</label>
                                                <input type="text" class="form-control" id="Gender" value="<?php echo htmlspecialchars($dbGender); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="CityAddress" class="form-label">Address</label>
                                                <input type="text" class="form-control" id="CityAddress" value="<?php echo htmlspecialchars($dbCityAddress); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="MobileNo" class="form-label">Mobile Number</label>
                                                <input type="text" class="form-control" id="MobileNo" value="<?php echo htmlspecialchars($dbMobileNo); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="College" class="form-label">College</label>
                                                <input type="text" class="form-control" id="College" value="<?php echo htmlspecialchars($dbCollege); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="Program" class="form-label">Program</label>
                                                <input type="text" class="form-control" id="Program" value="<?php echo htmlspecialchars($dbProgram); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="YearGraduated" class="form-label">Year Graduated</label>
                                                <input type="text" class="form-control" id="YearGraduated" value="<?php echo htmlspecialchars($dbYearGraduated); ?>" readonly>
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

        <!-- JavaScript for toggling password visibility -->
        <script>
            const togglePassword = document.querySelector('#togglePassword');
            const passwordField = document.querySelector('#Password');

            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });
        </script>


        <button class="btn btn-danger p-3 rounded-circle d-flex align-items-center justify-content-center customizer-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExample" aria-controls="offcanvasExample">
            <i class="icon ti ti-settings fs-7"></i>
        </button>

        <div class="offcanvas customizer offcanvas-end" tabindex="-1" id="offcanvasExample" aria-labelledby="offcanvasExampleLabel">
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
                    <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center" onclick="handleColorTheme('Blue_Theme')" for="Blue_Theme" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="BLUE_THEME">
                        <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-1">
                            <i class="ti ti-check text-white d-flex icon fs-5"></i>
                        </div>
                    </label>

                    <input type="radio" class="btn-check" name="color-theme-layout" id="Aqua_Theme" autocomplete="off" />
                    <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center" onclick="handleColorTheme('Aqua_Theme')" for="Aqua_Theme" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="AQUA_THEME">
                        <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-2">
                            <i class="ti ti-check text-white d-flex icon fs-5"></i>
                        </div>
                    </label>

                    <input type="radio" class="btn-check" name="color-theme-layout" id="Purple_Theme" autocomplete="off" />
                    <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center" onclick="handleColorTheme('Purple_Theme')" for="Purple_Theme" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="PURPLE_THEME">
                        <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-3">
                            <i class="ti ti-check text-white d-flex icon fs-5"></i>
                        </div>
                    </label>

                    <input type="radio" class="btn-check" name="color-theme-layout" id="green-theme-layout" autocomplete="off" />
                    <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center" onclick="handleColorTheme('Green_Theme')" for="green-theme-layout" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="GREEN_THEME">
                        <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-4">
                            <i class="ti ti-check text-white d-flex icon fs-5"></i>
                        </div>
                    </label>

                    <input type="radio" class="btn-check" name="color-theme-layout" id="cyan-theme-layout" autocomplete="off" />
                    <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center" onclick="handleColorTheme('Cyan_Theme')" for="cyan-theme-layout" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="CYAN_THEME">
                        <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-5">
                            <i class="ti ti-check text-white d-flex icon fs-5"></i>
                        </div>
                    </label>

                    <input type="radio" class="btn-check" name="color-theme-layout" id="orange-theme-layout" autocomplete="off" />
                    <label class="btn p-9 btn-outline-primary rounded-2 d-flex align-items-center justify-content-center" onclick="handleColorTheme('Orange_Theme')" for="orange-theme-layout" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="ORANGE_THEME">
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

    <!--  Search Bar -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <input type="search" class="form-control" placeholder="Search here" id="search" />
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="lh-1">
                        <i class="ti ti-x fs-5 ms-3"></i>
                    </a>
                </div>
                <div class="modal-body message-body" data-simplebar="">
                    <h5 class="mb-0 fs-5 p-1">Quick Page Links</h5>
                    <ul class="list mb-0 py-2">
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Analytics</span>
                                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard1</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">eCommerce</span>
                                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard2</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">CRM</span>
                                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard3</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Contacts</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/contacts</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Posts</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/blog/posts</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Detail</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/blog/detail/streaming-video-way-before-it-was-cool-go-dark-tomorrow</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Shop</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/ecommerce/shop</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Modern</span>
                                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard1</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Dashboard</span>
                                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard2</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Contacts</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/contacts</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Posts</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/blog/posts</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Detail</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/blog/detail/streaming-video-way-before-it-was-cool-go-dark-tomorrow</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Shop</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/ecommerce/shop</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>


</div>
<div class="dark-transparent sidebartoggler"></div>
<!-- Import Js Files -->
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/libs/simplebar/dist/simplebar.min.js"></script>
<script src="../assets/js/theme/app.init.js"></script>
<script src="../assets/js/theme/theme.js"></script>
<script src="../assets/js/theme/app.min.js"></script>
<script src="../assets/js/theme/sidebarmenu.js"></script>

<!-- solar icons -->
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
</body>

</html>