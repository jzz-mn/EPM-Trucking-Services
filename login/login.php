<?php
session_start();
$_SESSION['reset_theme'] = true;

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (isset($_SESSION['UserID'])) {
  switch ($_SESSION['Role']) {
    case 'SuperAdmin':
    case 'Officer':
      header("Location: ../officer/home.php");
      break;
    case 'Employee':
      header("Location: ../employee/home.php");
      break;
    default:
      header("Location: ../login.php");
      break;
  }
  exit();
}

include '../includes/db_connection.php';

$error_message = "";
$success_message = "Please enter your credentials to continue.";

// Maximum failed attempts and lockout duration
define('MAX_FAILED_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 15 * 60); // 15 minutes

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['EmailAddress']) && isset($_POST['Password'])) {
    $email = trim($_POST['EmailAddress']);
    $password = trim($_POST['Password']);

    if (empty($email) || empty($password)) {
      $error_message = "Please enter both email and password.";
    } else {
      $sql = "SELECT * FROM useraccounts WHERE EmailAddress = ? LIMIT 1";
      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
          $row = $result->fetch_assoc();
          $storedPassword = $row['Password'];
          $failed_attempts = $row['failed_attempts'];
          $lockout_until = $row['lockout_until'];

          // Check if account is locked
          if ($lockout_until && strtotime($lockout_until) > time()) {
            $error_message = "Your account is locked. Please try again after " . date("H:i:s", strtotime($lockout_until)) . ".";
          } else {
            // Reset lockout if applicable
            if ($lockout_until && strtotime($lockout_until) <= time()) {
              $failed_attempts = 0;
              $update_sql = "UPDATE useraccounts SET failed_attempts = 0, lockout_until = NULL WHERE EmailAddress = ?";
              $update_stmt = $conn->prepare($update_sql);
              $update_stmt->bind_param("s", $email);
              $update_stmt->execute();
            }

            // Validate password
            if (password_verify($password, $storedPassword)) {
              loginUser($row, $conn);
            } else {
              // Increment failed attempts
              $failed_attempts++;
              $lockout_until = NULL;

              if ($failed_attempts >= MAX_FAILED_ATTEMPTS) {
                $lockout_until = date("Y-m-d H:i:s", time() + LOCKOUT_DURATION);
                $error_message = "Too many failed login attempts. Your account is locked for 15 minutes.";
              } else {
                $error_message = "Invalid email or password!";
              }

              // Update database
              $update_sql = "UPDATE useraccounts SET failed_attempts = ?, lockout_until = ? WHERE EmailAddress = ?";
              $update_stmt = $conn->prepare($update_sql);
              $update_stmt->bind_param("iss", $failed_attempts, $lockout_until, $email);
              $update_stmt->execute();
            }
          }
        } else {
          $error_message = "Invalid email or password!";
        }

        $stmt->close();
      } else {
        $error_message = "An error occurred. Please try again later.";
        error_log("Failed to prepare user selection statement: " . $conn->error);
      }
    }
  } else {
    $error_message = "Please fill in both the email and password fields.";
  }
}

$conn->close();

function loginUser($userData, $conn)
{
  session_regenerate_id(true);
  $_SESSION['UserID'] = $userData['UserID'];
  $_SESSION['Username'] = $userData['Username'];
  $_SESSION['Role'] = $userData['Role'];
  $_SESSION['EmailAddress'] = $userData['EmailAddress'];

  $current_timestamp = date("Y-m-d H:i:s");
  $update_sql = "UPDATE useraccounts SET LastLogin = ?, failed_attempts = 0, lockout_until = NULL WHERE UserID = ?";
  $update_stmt = $conn->prepare($update_sql);
  $update_stmt->bind_param("si", $current_timestamp, $userData['UserID']);
  $update_stmt->execute();

  $action = "Logged In";
  $insert_sql = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, NOW())";
  $insert_stmt = $conn->prepare($insert_sql);
  $insert_stmt->bind_param("is", $userData['UserID'], $action);
  $insert_stmt->execute();

  if ($userData['Role'] === 'SuperAdmin' || $userData['Role'] === 'Officer') {
    header("Location: ../officer/home.php");
  } elseif ($userData['Role'] === 'Employee') {
    header("Location: ../employee/home.php");
  } else {
    global $error_message;
    $error_message = "User role not recognized!";
  }
  exit();
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
                <a href="" class="text-nowrap logo-img d-block w-100 mb-4">
                  <img src="../assetsEPM/logos/epm-logo.png" class="dark-logo img-fluid w-20" alt="Logo-Dark" />
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
                  <div class="mb-4 position-relative">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control pe-5" id="password" name="Password" required />
                    <span class="position-absolute" id="togglePasswordContainer"
                      style="right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer;">
                      <iconify-icon id="togglePassword" icon="mdi:eye-off" style="font-size: 1.25rem;"></iconify-icon>
                    </span>
                  </div>
                  <style>
                    .form-control {
                      padding-right: 2.5rem;
                      /* Ensures enough space for the icon */
                      border-radius: 0.5rem;
                      /* Ensures border radius stays consistent */
                    }

                    #togglePasswordContainer {
                      color: #6c757d;
                      /* Adjust color to match the style */
                      margin-top: 13px;
                      display: flex;
                      align-items: center;
                      /* Centers icon vertically within the container */
                    }
                  </style>
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
              <img src="../assetsEPM/images/epm-background.png" class="w-100 h-100 object-fit-cover"
                alt="Background Image" />
            </div>

            <!-- Adjusted Text Section -->
            <div
              class="d-flex align-items-center justify-content-start text-start z-index-5 position-relative h-100 ps-5">
              <div class="text-white">
                <h1 class="fw-bold mb-2 text-white" style="font-size: 3rem;">Welcome!</h1>
                <p class="mb-0" style="font-size: 1.25rem;">EPM Trucking Services System is designed to optimize
                  performance and efficiency across the organization.</p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <div class="dark-transparent sidebartoggler"></div>

  <script>
    const togglePassword = document.querySelector("#togglePassword");
    const password = document.querySelector("#password");

    togglePassword.addEventListener("click", function () {
      // Toggle the type attribute
      const type = password.getAttribute("type") === "password" ? "text" : "password";
      password.setAttribute("type", type);

      // Toggle the icon
      this.setAttribute("icon", type === "password" ? "mdi:eye-off" : "mdi:eye");
    });
  </script>


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