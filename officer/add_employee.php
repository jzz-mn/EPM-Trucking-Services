<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['UserID'])) {
    // Redirect to login page if not logged in
    header("Location: ../login/login.php");
    exit();
}

require '../includes/db_connection.php';
require '../vendor/autoload.php'; // For PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect Employee Data from POST
    $firstName = $_POST['firstName'];
    $middleInitial = $_POST['middleInitial'];
    $lastName = $_POST['lastName'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $address = $_POST['address'];
    $mobileNo = $_POST['mobileNo'];
    $emailAddress = $_POST['emailAddress'];
    $employmentDate = $_POST['employmentDate'];
    $position = $_POST['position'];

    // Collect User Account Data from POST
    $username = ucfirst($_POST['username']); // Automatically set the first letter to uppercase
    $passwordOption = $_POST['passwordOption'];

    // Handle password setting
    if ($passwordOption === 'manual') {
        // Admin sets the password manually
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];

        // Validate passwords match
        if ($password !== $confirmPassword) {
            echo "Passwords do not match.";
            exit();
        }

        // Enforce strong password (you can adjust the pattern as needed)
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
            echo "Password must be at least 8 characters long and include at least one letter and one number.";
            exit();
        }
    } else {
        // Automatically generate a random password
        $password = bin2hex(random_bytes(4)); // Generates an 8-character random password
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Generate Activation Token
    $activationToken = bin2hex(random_bytes(16));

    // Set Activation Status to 'deactivated'
    $activationStatus = 'deactivated';

    // Insert employee data into `employees` table
    $sqlInsertEmployee = "INSERT INTO employees (FirstName, MiddleInitial, LastName, Gender, DateOfBirth, Address, MobileNo, EmailAddress, EmploymentDate, Position)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtEmployee = $conn->prepare($sqlInsertEmployee);
    if (!$stmtEmployee) {
        echo "Error: " . $conn->error;
        exit();
    }
    $stmtEmployee->bind_param('ssssssssss', $firstName, $middleInitial, $lastName, $gender, $dob, $address, $mobileNo, $emailAddress, $employmentDate, $position);

    if ($stmtEmployee->execute()) {
        // Get the last inserted employee ID
        $employeeID = $conn->insert_id;

        // Insert into `useraccounts` table
        $sqlInsertUser = "INSERT INTO useraccounts (Username, Password, Role, EmailAddress, employeeID, officerID, LastLogin, ActivationStatus, ActivationToken)
                          VALUES (?, ?, 'Employee', ?, ?, NULL, NULL, ?, ?)";

        $stmtUser = $conn->prepare($sqlInsertUser);
        if (!$stmtUser) {
            echo "Error: " . $conn->error;
            exit();
        }
        $stmtUser->bind_param('sssiss', $username, $hashedPassword, $emailAddress, $employeeID, $activationStatus, $activationToken);

        if ($stmtUser->execute()) {
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
                $mail->Subject = 'Activate Your Account';
                $mail->Body    = "Dear $firstName,<br><br>
                                  Your account has been created. Please click the link below to activate your account and set your password:<br><br>
                                  <a href='$activationLink'>Activate Account</a><br><br>
                                  If you did not request this, please ignore this email.<br><br>
                                  Best regards,<br>
                                  EPM Trucking Services";

                $mail->send();
                // Email sent successfully
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                exit();
            }

            // ---- Insert Activity Log ----
            // Retrieve the logged-in user's UserID from the session
            $currentUserID = $_SESSION['UserID'];

            // Define the action description
            $action = "Added Employee: " . $username;

            // Get the current timestamp
            $currentTimestamp = date("Y-m-d H:i:s");

            // Prepare the INSERT statement for activitylogs
            $sqlInsertLog = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, ?)";
            $stmtLog = $conn->prepare($sqlInsertLog);
            if ($stmtLog) {
                $stmtLog->bind_param('iss', $currentUserID, $action, $currentTimestamp);
                if (!$stmtLog->execute()) {
                    // Handle insertion error (optional)
                    error_log("Failed to insert activity log: " . $stmtLog->error);
                }
                $stmtLog->close();
            } else {
                // Handle preparation error (optional)
                error_log("Failed to prepare activity log insertion: " . $conn->error);
            }
            // ---- End of Activity Log Insertion ----

            // Redirect to employees.php after successful insertion
            header("Location: employees.php");
            exit(); // Always exit after a header redirect to prevent further script execution
        } else {
            echo "Error: " . $stmtUser->error;
        }
    } else {
        echo "Error: " . $stmtEmployee->error;
    }

    // Close the statements
    if (isset($stmtEmployee)) {
        $stmtEmployee->close();
    }
    if (isset($stmtUser)) {
        $stmtUser->close();
    }

    // Close the database connection
    $conn->close();
}
?>
