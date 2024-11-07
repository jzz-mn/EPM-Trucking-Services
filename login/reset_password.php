<?php
// Start the session
session_start();

// Include necessary files
require '../includes/db_connection.php';
require '../vendor/autoload.php'; // For PHPMailer
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Initialize messages
$error_message = "";
$success_message = "";

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if token is valid
    $sql = "SELECT * FROM useraccounts WHERE PasswordResetToken = ? LIMIT 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Check if token has expired
            if (strtotime($user['TokenExpiration']) >= time()) {
                // Token is valid and not expired
                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    // Get new password from form
                    $password = $_POST['Password'];
                    $confirmPassword = $_POST['ConfirmPassword'];

                    // Validate passwords
                    if (empty($password) || empty($confirmPassword)) {
                        $error_message = "Please enter and confirm your new password.";
                    } elseif ($password !== $confirmPassword) {
                        $error_message = "Passwords do not match.";
                    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
                        $error_message = "Password must be at least 8 characters long and include at least one letter and one number.";
                    } else {
                        // Hash the new password
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                        // Update the user's password and clear the token
                        $updateSql = "UPDATE useraccounts SET Password = ?, PasswordResetToken = NULL, TokenExpiration = NULL WHERE UserID = ?";
                        if ($updateStmt = $conn->prepare($updateSql)) {
                            $updateStmt->bind_param("si", $hashedPassword, $user['UserID']);
                            if ($updateStmt->execute()) {
                                $success_message = "Your password has been reset successfully.";
                            } else {
                                $error_message = "An error occurred while updating your password.";
                            }
                            $updateStmt->close();
                        } else {
                            $error_message = "An error occurred. Please try again later.";
                        }
                    }
                }
            } else {
                $error_message = "This password reset link has expired.";
            }
        } else {
            $error_message = "Invalid password reset link.";
        }
        $stmt->close();
    } else {
        $error_message = "An error occurred. Please try again later.";
    }
} else {
    $error_message = "No password reset token provided.";
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/png" href="../assetsEPM/logos/epm-logo.png" />

    <!-- Core Css -->
    <link rel="stylesheet" href="../assets/css/styles.css" />

    <title>EPM Reset Password</title>
</head>

<body>
    <!-- Preloader -->
    <div class="preloader">
        <img src="../assetsEPM/logos/epm-logo.png" alt="loader" class="lds-ripple img-fluid" />
    </div>
    <div id="main-wrapper">
        <div class="position-relative overflow-hidden radial-gradient min-vh-100 w-100">
            <div class="position-relative z-index-5">
                <div class="row gx-0">

                    <div class="col-lg-6 col-xl-5 col-xxl-4">
                        <div class="min-vh-100 bg-body row justify-content-center align-items-center p-5">
                            <div class="col-12 auth-card">
                                <a href="../main/index.html" class="text-nowrap logo-img d-block w-100 mb-4">
                                    <img src="../assetsEPM/logos/epm-logo.png" class="dark-logo img-fluid w-25"
                                        alt="Logo-Dark" />
                                </a>
                                <h2 class="mb-2 mt-4 fs-7 fw-bolder">Reset Password</h2>
                                <!-- Displaying messages -->
                                <?php if (!empty($error_message)): ?>
                                    <p class="mb-4 text-danger"><?php echo $error_message; ?></p>
                                    <a href="../login/forgot_password.php"
                                        class="text-primary w-100 text-center d-block">Request a new reset link</a>
                                <?php elseif (!empty($success_message)): ?>
                                    <p class="mb-4 text-success"><?php echo $success_message; ?></p>
                                    <a href="../login/login.php" class="text-primary w-100 text-center d-block">Back to
                                        Login</a>
                                <?php else: ?>
                                    <p class="mb-4">Please enter your new password below.</p>

                                    <form method="POST" action="">
                                        <div class="mb-3 position-relative">
                                            <label for="Password" class="form-label">New Password</label>
                                            <input type="password" class="form-control pe-5" id="Password" name="Password" required>
                                            <span class="position-absolute top-50 end-0 translate-middle-y me-3" style="cursor: pointer;">
                                                <iconify-icon id="toggleNewPassword" icon="mdi:eye-off" style="font-size: 1.25rem;"></iconify-icon>
                                            </span>
                                        </div>
                                        <div class="mb-3 position-relative">
                                            <label for="ConfirmPassword" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control pe-5" id="ConfirmPassword" name="ConfirmPassword" required>
                                            <span class="position-absolute top-50 end-0 translate-middle-y me-3" style="cursor: pointer;">
                                                <iconify-icon id="toggleConfirmPassword" icon="mdi:eye-off" style="font-size: 1.25rem;"></iconify-icon>
                                            </span>
                                        </div>
                                        <button type="submit" class="btn btn-muted w-100 py-8 mb-3">Reset Password</button>
                                    </form>
                                    <style>
                                        .form-control {
                                            padding-right: 2.5rem;
                                        }

                                        #toggleNewPassword, #toggleConfirmPassword {
                                            color: #6c757d;
                                            /* Adjust color to match the style */
                                            margin-top: 30px;
                                            display: flex;
                                            align-items: center;
                                        }
                                    </style>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div
                        class="col-lg-6 col-xl-7 col-xxl-8 position-relative overflow-hidden bg-dark d-none d-lg-block">
                        <div class="position-absolute top-0 start-0 w-100 h-100">
                            <img src="../assetsEPM/images/epm-background.png" class="w-100 h-100 object-fit-cover"
                                alt="Background Image" />
                        </div>

                        <!-- Adjusted Text Section -->
                        <div
                            class="d-flex align-items-center justify-content-start text-start z-index-5 position-relative h-100 ps-5">
                            <div class="text-white">
                                <h1 class="fw-bold mb-2 text-white" style="font-size: 3rem;">Welcome!</h1>
                                <p class="mb-0" style="font-size: 1.25rem;">EPM Trucking Services System is designed to
                                    optimize performance and efficiency across the organization.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const toggleNewPassword = document.querySelector("#toggleNewPassword");
        const newPassword = document.querySelector("#Password");

        const toggleConfirmPassword = document.querySelector("#toggleConfirmPassword");
        const confirmPassword = document.querySelector("#ConfirmPassword");

        toggleNewPassword.addEventListener("click", function() {
            // Toggle new password visibility
            const type = newPassword.getAttribute("type") === "password" ? "text" : "password";
            newPassword.setAttribute("type", type);

            // Change icon
            this.setAttribute("icon", type === "password" ? "mdi:eye-off" : "mdi:eye");
        });

        toggleConfirmPassword.addEventListener("click", function() {
            // Toggle confirm password visibility
            const type = confirmPassword.getAttribute("type") === "password" ? "text" : "password";
            confirmPassword.setAttribute("type", type);

            // Change icon
            this.setAttribute("icon", type === "password" ? "mdi:eye-off" : "mdi:eye");
        });
    </script>


    <!-- Import Js Files -->
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/libs/simplebar/dist/simplebar.min.js"></script>
    <script src="../assets/js/theme/app.init.js"></script>
    <script src="../assets/js/theme/theme.js"></script>
    <script src="../assets/js/theme/app.min.js"></script>
    <!-- Icons -->
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
</body>

</html>