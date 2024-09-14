<?php
// Start session
session_start();
include '../db_conn.php';

// Initialize messages
$error_message = "";
$success_message = "Please enter your credentials to continue.";

// Check if form data has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ensure the keys 'EmailAddress' and 'Password' exist before accessing them
    if (isset($_POST['EmailAddress']) && isset($_POST['Password'])) {
        // Get form data
        $email = $_POST['EmailAddress'];
        $password = $_POST['Password'];

        // Validate input
        if (empty($email) || empty($password)) {
            $error_message = "Please enter both email and password.";
        } else {
            // Query to check if user exists in the database
            $sql = "SELECT * FROM useraccounts WHERE EmailAddress = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email); // Bind email to the query
            $stmt->execute();
            $result = $stmt->get_result();

            // Check if user was found
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();

                // Check if the password matches (simple string comparison for now, since passwords are not hashed)
                if ($password === $row['Password']) {
                    // Login successful, set session variables
                    $_SESSION['UserID'] = $row['UserID'];
                    $_SESSION['Username'] = $row['Username'];
                    $_SESSION['Role'] = $row['Role'];
                    $_SESSION['EmailAddress'] = $row['EmailAddress'];

                    // Redirect to a different page (e.g., dashboard)
                    header("Location: ../super-admin/home.php");
                    exit();
                } else {
                    $error_message = "Invalid password!";
                }
            } else {
                $error_message = "No user found with that email!";
            }

            $stmt->close();
        }
    } else {
        // If fields are missing, output an error message
        $error_message = "Please fill in both the email and password fields.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

<head>
  <!-- Required meta tags -->
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Favicon icon -->
  <link rel="shortcut icon" type="image/png" href="../assetsEPM/logos/epm-logo.png" />

  <!-- Core Css -->
  <link rel="stylesheet" href="../assets/css/styles.css" />

  <title>EPM Sign In</title>
</head>

<body>
  <!-- Preloader -->
  <div class="preloader">
    <img src="../assets/images/logos/favicon.png" alt="loader" class="lds-ripple img-fluid" />
  </div>
  <div id="main-wrapper">
    <div class="position-relative overflow-hidden radial-gradient min-vh-100 w-100">
      <div class="position-relative z-index-5">
        <div class="row gx-0">

          <!-- Left side (Form) -->
          <div class="col-lg-6 col-xl-5 col-xxl-4">
            <div class="min-vh-100 bg-body row justify-content-center align-items-center p-5">
              <div class="col-12 auth-card">
                <a href="../main/index.html" class="text-nowrap logo-img d-block w-100 mb-4">
                  <img src="../assetsEPM/logos/epm-logo.png" class="dark-logo img-fluid w-25" alt="Logo-Dark" />
                </a>

                <h2 class="mb-2 mt-4 fs-7 fw-bolder">Sign In</h2>

                <!-- Displaying messages based on success or error -->
                <p class="mb-9 <?php echo (!empty($error_message)) ? 'text-danger' : ''; ?>">
                  <?php echo (!empty($error_message)) ? $error_message : $success_message; ?>
                </p>

                <form action="login.php" method="POST">
                  <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="EmailAddress" required />
                  </div>
                  <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="Password" required />
                  </div>
                  <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <a class="text-danger fw-medium" href="../login/forgot_password.php">Forgot Password?</a>
                  </div>
                  <button type="submit" class="btn btn-muted w-100 py-8 mb-4 rounded-2">Sign In</button>
                </form>
              </div>
            </div>
          </div>

          <div class="col-lg-6 col-xl-7 col-xxl-8 position-relative overflow-hidden bg-dark d-none d-lg-block">
            <div class="position-absolute top-0 start-0 w-100 h-100">
              <img src="../assetsEPM/images/epm-background.png" class="w-100 h-100 object-fit-cover" alt="Background Image" />
            </div>

            <!-- Adjusted Text Section -->
            <div class="d-flex align-items-center justify-content-start text-start z-index-5 position-relative h-100 ps-5">
              <div class="text-white">
                <h1 class="fw-bold mb-2 text-white" style="font-size: 3rem;">Welcome!</h1>
                <p class="mb-0" style="font-size: 1.25rem;">EPM Trucking Services System is designed to optimize performance and efficiency across the organization.</p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <div class="dark-transparent sidebartoggler"></div>

  <!-- Import JS Files -->
  <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/libs/simplebar/dist/simplebar.min.js"></script>
  <script src="../assets/js/theme/app.init.js"></script>
  <script src="../assets/js/theme/theme.js"></script>
  <script src="../assets/js/theme/app.min.js"></script>
  <!-- Icons -->
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
</body>

</html>
