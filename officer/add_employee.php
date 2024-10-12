<?php
session_start();

require '../includes/db_connection.php';
require '../vendor/autoload.php'; // For PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

header('Content-Type: application/json'); // Return JSON response

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
    $username = ucfirst($_POST['username']);
    $passwordOption = $_POST['passwordOption'];

    // Handle password setting
    if ($passwordOption === 'manual') {
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];

        // Validate passwords match
        if ($password !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit();
        }

        // Enforce strong password
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long and include at least one letter and one number.']);
            exit();
        }
    } else {
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
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
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
            echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
            exit();
        }

        // Bind parameters and execute
        $stmtUser->bind_param('sssiss', $username, $hashedPassword, $emailAddress, $employeeID, $activationStatus, $activationToken);

        // Place your if ($stmtUser->execute()) block here
        if ($stmtUser->execute()) {
            // Send activation email
            $activationLink = "http://localhost/EPM-Trucking-Services/activate_account.php?token=$activationToken";

            // Configure PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = $_ENV['SMTP_HOST'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['SMTP_USERNAME'];
                $mail->Password   = $_ENV['SMTP_PASSWORD'];
                $mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION'];
                $mail->Port       = $_ENV['SMTP_PORT'];

                $mail->setFrom($_ENV['FROM_EMAIL'], $_ENV['FROM_NAME']);
                $mail->addAddress($emailAddress, $firstName . ' ' . $lastName);

                $mail->isHTML(true);
                $mail->Subject = 'Activate Your Account';
                $mail->Body    = "Dear $firstName,<br><br>
                                  Your account has been created. Please click the link below to activate your account and set your password:<br><br>
                                  <a href='$activationLink'>Activate Account</a><br><br>
                                  If you did not request this, please ignore this email.<br><br>
                                  Best regards,<br>
                                  EPM Trucking Services";

                $mail->send();
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
                exit();
            }

            // ---- Insert Activity Log ----
            $currentUserID = $_SESSION['UserID'];
            $action = "Added Employee: " . $username;
            $currentTimestamp = date("Y-m-d H:i:s");

            $sqlInsertLog = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, ?)";
            $stmtLog = $conn->prepare($sqlInsertLog);
            if ($stmtLog) {
                $stmtLog->bind_param('iss', $currentUserID, $action, $currentTimestamp);
                if (!$stmtLog->execute()) {
                    error_log("Failed to insert activity log: " . $stmtLog->error);
                }
                $stmtLog->close();
            } else {
                error_log("Failed to prepare activity log insertion: " . $conn->error);
            }

            // Return success message as JSON
            echo json_encode(['success' => true, 'message' => "Employee '$firstName $lastName' added successfully!"]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmtUser->error]);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $stmtEmployee->error]);
        exit();
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
