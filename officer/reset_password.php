<?php
// Start the session and verify user authentication
session_start();

// Check if the user is logged in
if (!isset($_SESSION['UserID'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized.']);
    exit();
}

// Include the database connection file
include('../includes/db_connection.php');

// Include PHPMailer and Dotenv
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $officerID = isset($_POST['officerID']) ? intval($_POST['officerID']) : 0;

if ($officerID <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid officer ID.']);
    exit();
}


    // Begin a transaction to ensure data integrity
    $conn->begin_transaction();

    try {
        // Fetch user details based on officer ID
        $sql_fetch_user = "SELECT e.FirstName, e.LastName, u.EmailAddress, u.Username
                           FROM officers e
                           JOIN useraccounts u ON e.OfficerID = u.OfficerID
                           WHERE e.OfficerID = ?";
        
        $stmt_fetch = $conn->prepare($sql_fetch_user);
        if ($stmt_fetch === false) {
            throw new Exception('Error preparing fetch statement: ' . $conn->error);
        }
        $stmt_fetch->bind_param('i', $officerID);
        $stmt_fetch->execute();
        $stmt_fetch->bind_result($firstName, $lastName, $emailAddress, $username);
        if (!$stmt_fetch->fetch()) {
            throw new Exception('Officer not found.');
        }
        $stmt_fetch->close();

        // Generate a new activation token
        $activationToken = bin2hex(random_bytes(16));

        // Set ActivationStatus to 'deactivated'
        $activationStatus = 'deactivated';

        // Update the useraccounts table with new activation token and status
        $sql_reset = "UPDATE useraccounts SET ActivationToken = ?, ActivationStatus = ? WHERE OfficerID = ?";
        $stmt_reset = $conn->prepare($sql_reset);
        if ($stmt_reset === false) {
            throw new Exception('Error preparing password reset statement: ' . $conn->error);
        }
        $stmt_reset->bind_param('ssi', $activationToken, $activationStatus, $officerID);
        if (!$stmt_reset->execute()) {
            throw new Exception('Error updating user account for password reset: ' . $stmt_reset->error);
        }
        $stmt_reset->close();

        // Send activation email
        $activationLink = "https://www.epm-trucking-services.com/activate_account.php?token=$activationToken";

        // Configure PHPMailer
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
            $mail->addAddress($emailAddress, $firstName . ' ' . $lastName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Password';
            $mail->Body = "Dear $firstName,<br><br>
                              Your password has been reset. Please click the link below to activate your account and set your new password:<br><br>
                              Link: <a href='$activationLink'>Reset Password</a><br><br>
                              If you did not request this, please contact support immediately.<br><br>
                              Best regards,<br>
                              EPM Trucking Services";

            $mail->send();
            // Email sent successfully
        } catch (Exception $e) {
            throw new Exception("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }

        // ---- Insert Activity Log ----
        // Retrieve the logged-in user's UserID from the session
        $currentUserID = $_SESSION['UserID'];

        // Define the action description
        $action = "Reset password for user: " . $username;

        // Get the current timestamp
        $currentTimestamp = date("Y-m-d H:i:s");

        // Prepare the INSERT statement for activitylogs
        $sqlInsertLog = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, NOW())";
        $stmtLog = $conn->prepare($sqlInsertLog);
        if ($stmtLog === false) {
            throw new Exception('Error preparing activity log insertion: ' . $conn->error);
        }
        $stmtLog->bind_param('is', $currentUserID, $action);
        if (!$stmtLog->execute()) {
            error_log("Failed to insert activity log: " . $stmtLog->error);
        }
        $stmtLog->close();

        // Commit the transaction
        $conn->commit();

        // Success message
        echo json_encode(['success' => true, 'message' => 'Password reset successfully!']);
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
