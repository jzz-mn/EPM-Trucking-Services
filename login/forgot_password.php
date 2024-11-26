<?php
// Start the session
session_start();

// Include necessary files
require '../includes/db_connection.php';
require '../vendor/autoload.php'; // For PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Initialize messages
$error_message = "";
$success_message = "";

// Check if form data has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get the email address from the form
  $email = trim($_POST['EmailAddress']);

  // Validate email
  if (empty($email)) {
    $error_message = "Please enter your email address.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_message = "Please enter a valid email address.";
  } else {
    // Check if the email exists in the database
    $sql = "SELECT * FROM useraccounts WHERE EmailAddress = ? LIMIT 1";
    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows > 0) {
        // Email exists, generate a password reset token
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(16)); // Generate a 32-character token
        $tokenExpiration = date("Y-m-d H:i:s", strtotime('+1 hour')); // Token valid for 1 hour

        // Update the user record with the token and expiration
        $updateSql = "UPDATE useraccounts SET PasswordResetToken = ?, TokenExpiration = ? WHERE UserID = ?";
        if ($updateStmt = $conn->prepare($updateSql)) {
          $updateStmt->bind_param("ssi", $token, $tokenExpiration, $user['UserID']);
          if ($updateStmt->execute()) {
            // Send password reset email
            $resetLink = "https://www.epm-trucking-services.com/login/reset_password.php?token=$token";

            $mail = new PHPMailer(true);
            try {
              // Server settings
              $mail->isSMTP();
              $mail->Host = $_ENV['SMTP_HOST'];
              $mail->SMTPAuth = true;
              $mail->Username = $_ENV['SMTP_USERNAME'];
              $mail->Password = $_ENV['SMTP_PASSWORD'];
              $mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION'];
              $mail->Port = $_ENV['SMTP_PORT'];

              // Recipients
              $mail->setFrom($_ENV['FROM_EMAIL'], $_ENV['FROM_NAME']);
              $mail->addAddress($email, $user['Username']);

              // Content
              $mail->isHTML(true);
              $mail->Subject = 'Password Reset Request';
              $mail->Body = "Dear {$user['Username']},<br><br>
                                              We received a request to reset your password. Please click the link below to reset your password:<br><br>
                                              <a href='$resetLink'>Reset Password</a><br><br>
                                              This link will expire in 1 hour.<br><br>
                                              If you did not request this, please ignore this email.<br><br>
                                              Best regards,<br>
                                              EPM Trucking Services";

              $mail->send();

              $success_message = "A password reset link has been sent to your email address.";
            } catch (Exception $e) {
              $error_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
          } else {
            $error_message = "An error occurred while updating your account.";
          }
          $updateStmt->close();
        } else {
          $error_message = "An error occurred. Please try again later.";
        }
      } else {
        $error_message = "No account found with that email address.";
      }
      $stmt->close();
    } else {
      $error_message = "An error occurred. Please try again later.";
    }
  }
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

  <title>EPM Forgot Password</title>
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
                <a href="../index.php" class="text-nowrap logo-img d-block w-100 mb-4">
                  <img src="../assetsEPM/logos/epm-logo.png" class="dark-logo img-fluid w-20" alt="Logo-Dark" />
                </a>
                <h2 class="mb-2 mt-4 fs-7 fw-bolder">Forgot Password</h2>
                <!-- Displaying messages -->
                <?php if (!empty($error_message)): ?>
                  <p class="mb-4 text-danger text-center text-strong fw-bold my-4"><?php echo $error_message; ?></p>
                <?php elseif (!empty($success_message)): ?>
                  <p class="mb-4 text-success text-center text-strong fw-bold my-4"><?php echo $success_message; ?></p>
                <?php else: ?>
                  <p class="mb-4">Please enter the email address associated with your account, and we will email you a
                    link to reset your password.</p>
                <?php endif; ?>

                <?php if (empty($success_message)): ?>
                  <form method="POST" action="">
                    <div class="mb-3">
                      <label for="EmailAddress" class="form-label">Email Address</label>
                      <input type="email" class="form-control" id="EmailAddress" name="EmailAddress" required>
                    </div>
                    <button type="submit" class="btn btn-muted w-100 py-8 mb-3">Reset Password</button>
                    <a href="../index.php" class="text-primary w-100 text-center d-block">Back to Login</a>
                  </form>
                <?php else: ?>
                  <a href="../index.php" class="btn btn-muted w-100 text-center d-block">Back to Login</a>
                <?php endif; ?>
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