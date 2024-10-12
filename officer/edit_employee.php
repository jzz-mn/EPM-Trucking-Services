<?php
// Start the session and verify user authentication
session_start();

// Check if the user is logged in
if (!isset($_SESSION['UserID'])) {
    // Redirect to login page if not logged in
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection file
include('../includes/db_connection.php');

// Include PHPMailer and Dotenv
require '../vendor/autoload.php'; // Make sure this path is correct
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $employeeID = isset($_POST['employeeID']) ? intval($_POST['employeeID']) : 0;
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
    $middleInitial = isset($_POST['middleInitial']) ? trim($_POST['middleInitial']) : '';
    $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
    $dob = isset($_POST['dateOfBirth']) ? trim($_POST['dateOfBirth']) : '';
    $mobileNo = isset($_POST['mobileNo']) ? trim($_POST['mobileNo']) : '';
    $employmentDate = isset($_POST['employmentDate']) ? trim($_POST['employmentDate']) : '';
    $position = isset($_POST['position']) ? trim($_POST['position']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';

    // Data for the useraccounts table
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $emailAddress = isset($_POST['emailAddress']) ? trim($_POST['emailAddress']) : '';
    $activationStatus = isset($_POST['activationStatus']) ? trim($_POST['activationStatus']) : '';

    // Check if reset password is requested
    $resetPassword = isset($_POST['resetPassword']) ? $_POST['resetPassword'] === 'true' : false;

    // Begin a transaction to ensure data integrity
    $conn->begin_transaction();

    try {
        // First, update the employees table
        $sql_employee = "UPDATE employees 
                         SET FirstName = ?, MiddleInitial = ?, LastName = ?, Gender = ?, DateOfBirth = ?, MobileNo = ?, EmploymentDate = ?, Position = ?, Address = ? 
                         WHERE EmployeeID = ?";
        $stmt_employee = $conn->prepare($sql_employee);
        if ($stmt_employee === false) {
            throw new Exception('Error preparing employee update statement: ' . $conn->error);
        }
        $stmt_employee->bind_param('sssssssssi', $firstName, $middleInitial, $lastName, $gender, $dob, $mobileNo, $employmentDate, $position, $address, $employeeID);
        if (!$stmt_employee->execute()) {
            throw new Exception('Error updating employee: ' . $stmt_employee->error);
        }
        $stmt_employee->close();

        // Then, update the useraccounts table
        $sql_user = "UPDATE useraccounts SET Username = ?, EmailAddress = ?, ActivationStatus = ? WHERE EmployeeID = ?";
        $stmt_user = $conn->prepare($sql_user);
        if ($stmt_user === false) {
            throw new Exception('Error preparing user account update statement: ' . $conn->error);
        }
        $stmt_user->bind_param('sssi', $username, $emailAddress, $activationStatus, $employeeID);
        if (!$stmt_user->execute()) {
            throw new Exception('Error updating user account: ' . $stmt_user->error);
        }
        $stmt_user->close();

        // Handle password reset if requested
        if ($resetPassword) {
            // Generate a new activation token
            $activationToken = bin2hex(random_bytes(16));

            // Set ActivationStatus to 'deactivated'
            $activationStatus = 'deactivated';

            // Update the useraccounts table with new activation token and status
            $sql_reset = "UPDATE useraccounts SET ActivationToken = ?, ActivationStatus = ? WHERE EmployeeID = ?";
            $stmt_reset = $conn->prepare($sql_reset);
            if ($stmt_reset === false) {
                throw new Exception('Error preparing password reset statement: ' . $conn->error);
            }
            $stmt_reset->bind_param('ssi', $activationToken, $activationStatus, $employeeID);
            if (!$stmt_reset->execute()) {
                throw new Exception('Error updating user account for password reset: ' . $stmt_reset->error);
            }
            $stmt_reset->close();

            // Send activation email
            $activationLink = "http://localhost/EPM-Trucking-Services/activate_account.php?token=$activationToken";

            // Configure PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Server settings
                // $mail->SMTPDebug = 2; // Enable verbose debug output (disable in production)
                $mail->isSMTP();
                $mail->Host       = $_ENV['SMTP_HOST'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['SMTP_USERNAME'];
                $mail->Password   = $_ENV['SMTP_PASSWORD'];
                $mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION'];
                $mail->Port       = $_ENV['SMTP_PORT'];

                // Recipients
                $mail->setFrom($_ENV['FROM_EMAIL'], $_ENV['FROM_NAME']);
                $mail->addAddress($emailAddress, $firstName . ' ' . $lastName);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Reset Your Password';
                $mail->Body    = "Dear $firstName,<br><br>
                                  Your password has been reset. Please click the link below to activate your account and set your new password:<br><br>
                                  <a href='$activationLink'>Reset Password</a><br><br>
                                  If you did not request this, please contact support immediately.<br><br>
                                  Best regards,<br>
                                  EPM Trucking Services";

                $mail->send();
                // Email sent successfully
            } catch (Exception $e) {
                throw new Exception("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            }
        }

        // ---- Insert Activity Log ----
        // Retrieve the logged-in user's UserID from the session
        $currentUserID = $_SESSION['UserID'];

        // Define the action description
        $action = "Updated Employee: " . $firstName . " " . $lastName;
        if ($resetPassword) {
            $action .= " and reset password";
        }

        // Get the current timestamp
        $currentTimestamp = date("Y-m-d H:i:s");

        // Prepare the INSERT statement for activitylogs
        $sqlInsertLog = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, ?)";
        $stmtLog = $conn->prepare($sqlInsertLog);
        if ($stmtLog === false) {
            throw new Exception('Error preparing activity log insertion: ' . $conn->error);
        }
        $stmtLog->bind_param('iss', $currentUserID, $action, $currentTimestamp);
        if (!$stmtLog->execute()) {
            // Log the error without halting the transaction
            error_log("Failed to insert activity log: " . $stmtLog->error);
        }
        $stmtLog->close();
        // ---- End of Activity Log Insertion ----

        // Commit the transaction
        $conn->commit();

        // Success message
        echo json_encode(['success' => true, 'message' => 'Employee details updated successfully!']);
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        // Return error message
        echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }

    // Close the database connection
    $conn->close();
}
?>
