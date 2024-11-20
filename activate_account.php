<?php
require 'includes/db_connection.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Validate token
    $sql = "SELECT UserID FROM useraccounts WHERE ActivationToken = ? AND ActivationStatus = 'deactivated'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Token is valid
        $row = $result->fetch_assoc();
        $userID = $row['UserID'];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Handle password reset
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirmPassword'];

            if ($password !== $confirmPassword) {
                $error = "Passwords do not match.";
            } else {
                // Enforce strong password policy
                if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
                    $error = "Password must be at least 8 characters long and include at least one letter and one number.";
                } else {
                    // Hash the new password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    // Update the user's password and activation status
                    $updateSql = "UPDATE useraccounts SET Password = ?, ActivationStatus = 'Activated', ActivationToken = NULL WHERE UserID = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param('si', $hashedPassword, $userID);

                    if ($updateStmt->execute()) {
                        $success = "Your account has been activated. You can now log in.";
                    } else {
                        $error = "Failed to activate account.";
                    }
                }
            }
        }
    } else {
        $error = "Invalid or expired activation link.";
    }
} else {
    $error = "No activation token provided.";
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Favicon icon -->
    <link rel="shortcut icon" type="image/png" href="assetsEPM/logos/epm-logo.png" />

    <!-- Core Css -->
    <link rel="stylesheet" href="assets/css/styles.css" />

    <title>Activate Account</title>
</head>

<body>
    <!-- Preloader -->
    <div class="preloader">
        <img src="assetsEPM/logos/epm-logo.png" alt="loader" class="lds-ripple img-fluid" />
    </div>
    <div id="main-wrapper">
        <div class="position-relative overflow-hidden radial-gradient min-vh-100 w-100">
            <div class="position-relative z-index-5">
                <div class="row gx-0">

                    <!-- Left side (Image and Text) -->
                    <div
                        class="col-lg-6 col-xl-7 col-xxl-8 position-relative overflow-hidden bg-dark d-none d-lg-block">
                        <div class="position-absolute top-0 start-0 w-100 h-100">
                            <img src="assetsEPM/images/epm-background.png" class="w-100 h-100 object-fit-cover"
                                alt="Background Image" />
                        </div>

                        <!-- Adjusted Text Section -->
                        <div
                            class="d-flex align-items-center justify-content-start text-start z-index-5 position-relative h-100 ps-5">
                            <div class="text-white">
                                <h1 class="fw-bold mb-2 text-white" style="font-size: 3rem;">Welcome!</h1>
                                <p class="mb-0" style="font-size: 1.25rem;">EPM Trucking Services System is designed to
                                    optimize
                                    performance and efficiency across the organization.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right side (Form) -->
                    <div class="col-lg-6 col-xl-5 col-xxl-4">
                        <div class="min-vh-100 bg-body row justify-content-center align-items-center p-5">
                            <div class="col-12 auth-card">
                                <a href="main/index.html" class="text-nowrap logo-img d-block w-100 mb-4">
                                    <img src="assetsEPM/logos/epm-logo.png" class="dark-logo img-fluid w-25"
                                        alt="Logo-Dark" />
                                </a>

                                <h2 class="mb-2 mt-4 fs-7 fw-bolder">Activate Account</h2>

                                <!-- Displaying messages based on success or error -->
                                <?php if (isset($error)): ?>
                                    <p class="text-danger"><?php echo $error; ?></p>
                                <?php elseif (isset($success)): ?>
                                    <p class="text-success"><?php echo $success; ?></p>
                                <?php endif; ?>

                                <?php if (!isset($success)): ?>
                                    <form method="POST">
                                        <div class="mb-3 position-relative">
                                            <label for="password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="password" id="password" required />
                                            <span class="position-absolute top-50 end-0 translate-middle-y me-3" style="cursor: pointer;">
                                                <iconify-icon id="toggleNewPassword" icon="mdi:eye-off" style="font-size: 1.25rem;"></iconify-icon>
                                            </span>
                                        </div>
                                        <div class="mb-4 position-relative">
                                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" name="confirmPassword" id="confirmPassword" required />
                                            <span class="position-absolute top-50 end-0 translate-middle-y me-3" style="cursor: pointer;">
                                                <iconify-icon id="toggleConfirmPassword" icon="mdi:eye-off" style="font-size: 1.25rem;"></iconify-icon>
                                            </span>
                                        </div>
                                        <button type="submit" class="btn btn-muted w-100 py-8 mb-4 rounded-2">Activate
                                            Account</button>
                                    </form>
                                    <style>
                                        .form-control {
                                            padding-right: 2.5rem;
                                            margin-bottom: 0px;
                                        }

                                        #toggleNewPassword,
                                        #toggleConfirmPassword {
                                            color: #6c757d;
                                            margin-top: 30px;
                                            display: flex;
                                            align-items: center;
                                        }
                                    </style>
                                <?php else: ?>
                                    <a href="index.php" class="btn btn-muted w-100 py-8 mb-4 rounded-2">Go to
                                        Login</a>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="dark-transparent sidebartoggler"></div>

    <script>
        const toggleNewPassword = document.querySelector("#toggleNewPassword");
        const newPassword = document.querySelector("#password");

        const toggleConfirmPassword = document.querySelector("#toggleConfirmPassword");
        const confirmPassword = document.querySelector("#confirmPassword");

        toggleNewPassword.addEventListener("click", function() {
            const type = newPassword.getAttribute("type") === "password" ? "text" : "password";
            newPassword.setAttribute("type", type);
            this.setAttribute("icon", type === "password" ? "mdi:eye-off" : "mdi:eye");
        });

        toggleConfirmPassword.addEventListener("click", function() {
            const type = confirmPassword.getAttribute("type") === "password" ? "text" : "password";
            confirmPassword.setAttribute("type", type);
            this.setAttribute("icon", type === "password" ? "mdi:eye-off" : "mdi:eye");
        });
    </script>

    <!-- Import JS Files -->
    <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/simplebar/dist/simplebar.min.js"></script>
    <script src="assets/js/theme/app.init.js"></script>
    <script src="assets/js/theme/theme.js"></script>
    <script src="assets/js/theme/app.min.js"></script>
    <!-- Icons -->
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
</body>

</html>