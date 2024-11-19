<?php
session_start();
$_SESSION['reset_theme'] = true;

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// If user is already logged in, redirect them
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
      // Optional: Handle unknown roles
      header("Location: ../login.php");
      break;
  }
  exit();
}

include '../includes/db_connection.php';

// Initialize messages
$error_message = "";
$success_message = "Please enter your credentials to continue.";

// Check if form data has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Ensure the keys 'EmailAddress' and 'Password' exist before accessing them
  if (isset($_POST['EmailAddress']) && isset($_POST['Password'])) {
    // Get form data and trim whitespace
    $email = trim($_POST['EmailAddress']);
    $password = trim($_POST['Password']);

    // Validate input
    if (empty($email) || empty($password)) {
      $error_message = "Please enter both email and password.";
    } else {
      // Prepare the SQL statement to prevent SQL injection
      $sql = "SELECT * FROM useraccounts WHERE EmailAddress = ? LIMIT 1";
      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email); // Bind email to the query
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if user was found
        if ($result->num_rows > 0) {
          $row = $result->fetch_assoc();

          // **NEW CODE STARTS HERE**
          // Check if the account is locked
          $lockoutUntil = $row['LockoutUntil'];

          if ($lockoutUntil != null && strtotime($lockoutUntil) > time()) {
            // Account is locked
            $remaining = strtotime($lockoutUntil) - time();
            $minutes = ceil($remaining / 60);
            $error_message = "Your account is locked due to multiple failed login attempts. Please try again in $minutes minute(s).";
          } else {
            // Account is not locked, proceed with login attempt
            // If LockoutUntil has passed, reset FailedLoginAttempts and LockoutUntil
            if ($lockoutUntil != null) {
              // Reset FailedLoginAttempts and LockoutUntil
              $update_sql = "UPDATE useraccounts SET FailedLoginAttempts = 0, LockoutUntil = NULL WHERE UserID = ?";
              if ($update_stmt = $conn->prepare($update_sql)) {
                $update_stmt->bind_param("i", $row['UserID']);
                $update_stmt->execute();
                $update_stmt->close();
              }
            }
            // **NEW CODE ENDS HERE**

            // Check if the account is activated
            if (strtoupper($row['ActivationStatus']) !== 'ACTIVATED') {
              $error_message = "Your account is not activated. Please check your email for the activation link.";
            } else {
              $storedPassword = $row['Password'];

              // Check if the stored password is hashed
              if (password_get_info($storedPassword)['algo'] !== 0) {
                // Password is hashed, use password_verify()
                if (password_verify($password, $storedPassword)) {
                  // **NEW CODE STARTS HERE**
                  // Reset FailedLoginAttempts and LockoutUntil
                  $update_sql = "UPDATE useraccounts SET FailedLoginAttempts = 0, LockoutUntil = NULL WHERE UserID = ?";
                  if ($update_stmt = $conn->prepare($update_sql)) {
                    $update_stmt->bind_param("i", $row['UserID']);
                    $update_stmt->execute();
                    $update_stmt->close();
                  }
                  // **NEW CODE ENDS HERE**
                  // Successful login
                  loginUser($row, $conn);
                } else {
                  // **NEW CODE STARTS HERE**
                  // Handle failed login attempt
                  handleFailedLoginAttempt($row, $error_message, $conn);
                  // **NEW CODE ENDS HERE**
                }
              } else {
                // Password is not hashed, compare plaintext
                if ($password === $storedPassword) {
                  // Successful login

                  // Hash the plaintext password and update the database
                  $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                  $update_sql = "UPDATE useraccounts SET Password = ? WHERE UserID = ?";
                  if ($update_stmt = $conn->prepare($update_sql)) {
                    $update_stmt->bind_param("si", $hashedPassword, $row['UserID']);
                    $update_stmt->execute();
                    $update_stmt->close();
                  }

                  // Update the $row['Password'] to the hashed password for future use
                  $row['Password'] = $hashedPassword;

                  // **NEW CODE STARTS HERE**
                  // Reset FailedLoginAttempts and LockoutUntil
                  $update_sql = "UPDATE useraccounts SET FailedLoginAttempts = 0, LockoutUntil = NULL WHERE UserID = ?";
                  if ($update_stmt = $conn->prepare($update_sql)) {
                    $update_stmt->bind_param("i", $row['UserID']);
                    $update_stmt->execute();
                    $update_stmt->close();
                  }
                  // **NEW CODE ENDS HERE**

                  loginUser($row, $conn);
                } else {
                  // **NEW CODE STARTS HERE**
                  // Handle failed login attempt
                  handleFailedLoginAttempt($row, $error_message, $conn);
                  // **NEW CODE ENDS HERE**
                }
              }
            }
          }
        } else {
          $error_message = "Invalid email or password!";
        }

        $stmt->close();
      } else {
        // Handle preparation error
        $error_message = "An error occurred. Please try again later.";
        error_log("Failed to prepare user selection statement: " . $conn->error);
      }
    }
  } else {
    // If fields are missing, output an error message
    $error_message = "Please fill in both the email and password fields.";
  }
}

$conn->close();

// Function to handle successful login
function loginUser($userData, $conn)
{
  // Regenerate session ID to prevent session fixation
  session_regenerate_id(true);

  // Set session variables
  $_SESSION['UserID'] = $userData['UserID'];
  $_SESSION['Username'] = $userData['Username'];
  $_SESSION['Role'] = $userData['Role'];
  $_SESSION['EmailAddress'] = $userData['EmailAddress'];

  // **NEW CODE STARTS HERE**
  // Reset FailedLoginAttempts and LockoutUntil
  $update_sql = "UPDATE useraccounts SET FailedLoginAttempts = 0, LockoutUntil = NULL WHERE UserID = ?";
  if ($update_stmt = $conn->prepare($update_sql)) {
    $update_stmt->bind_param("i", $userData['UserID']);
    $update_stmt->execute();
    $update_stmt->close();
  }
  // **NEW CODE ENDS HERE**

  // **EXISTING CODE**
  // Store last_seen_logid in session
  $_SESSION['last_seen_logid'] = isset($userData['last_seen_logid']) ? intval($userData['last_seen_logid']) : 0;

  // Update LastLogin timestamp
  $current_timestamp = date("Y-m-d H:i:s");
  $update_sql = "UPDATE useraccounts SET LastLogin = ? WHERE UserID = ?";
  if ($update_stmt = $conn->prepare($update_sql)) {
    $update_stmt->bind_param("si", $current_timestamp, $userData['UserID']);
    $update_stmt->execute();
    $update_stmt->close();
  }

  // Insert activity log
  $action = "Logged In";

  // Prepare the INSERT statement
  $insert_sql = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, NOW())";
  if ($insert_stmt = $conn->prepare($insert_sql)) {
    $insert_stmt->bind_param("is", $userData['UserID'], $action);
    if (!$insert_stmt->execute()) {
      // Handle insertion error (optional)
      error_log("Failed to insert activity log: " . $insert_stmt->error);
    }
    $insert_stmt->close();
  } else {
    // Handle preparation error (optional)
    error_log("Failed to prepare activity log insertion: " . $conn->error);
  }

  // Redirect to the appropriate home page based on role
  if ($userData['Role'] === 'SuperAdmin' || $userData['Role'] === 'Officer') {
    header("Location: ../officer/home.php");
  } elseif ($userData['Role'] === 'Employee') {
    header("Location: ../employee/home.php");
  } else {
    // Handle unknown role
    global $error_message;
    $error_message = "User role not recognized!";
  }
  exit();
}

// **NEW FUNCTION STARTS HERE**
// Function to handle failed login attempts
function handleFailedLoginAttempt($userData, &$error_message, $conn)
{
  $failedAttempts = $userData['FailedLoginAttempts'] + 1;
  $lockoutThreshold = 5; // Number of allowed attempts
  $lockoutDuration = 15; // Lockout duration in minutes

  if ($failedAttempts >= $lockoutThreshold) {
    // Lock the account
    $lockoutUntil = date("Y-m-d H:i:s", strtotime("+$lockoutDuration minutes"));
    $update_sql = "UPDATE useraccounts SET FailedLoginAttempts = ?, LockoutUntil = ? WHERE UserID = ?";
    if ($update_stmt = $conn->prepare($update_sql)) {
      $update_stmt->bind_param("isi", $failedAttempts, $lockoutUntil, $userData['UserID']);
      $update_stmt->execute();
      $update_stmt->close();
    }
    $error_message = "Your account is locked due to multiple failed login attempts. Please try again after $lockoutDuration minutes.";
  } else {
    // Update FailedLoginAttempts
    $update_sql = "UPDATE useraccounts SET FailedLoginAttempts = ? WHERE UserID = ?";
    if ($update_stmt = $conn->prepare($update_sql)) {
      $update_stmt->bind_param("ii", $failedAttempts, $userData['UserID']);
      $update_stmt->execute();
      $update_stmt->close();
    }
    $remainingAttempts = $lockoutThreshold - $failedAttempts;
    $error_message = "Invalid email or password! You have $remainingAttempts remaining attempt(s) before your account is locked.";
  }
}
// **NEW FUNCTION ENDS HERE**
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