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

    // Handle profile picture upload
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 800 * 1024; // 800KB

    if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] == 0) {
        $fileTmpPath = $_FILES['profilePicture']['tmp_name'];
        $fileType = mime_content_type($fileTmpPath);
        $fileSize = $_FILES['profilePicture']['size'];

        if (in_array($fileType, $allowedTypes)) {
            if ($fileSize <= $maxSize) {
                // Read the file content into a variable
                $profileImage = file_get_contents($fileTmpPath);
            } else {
                // File size exceeds limit
                echo json_encode(['success' => false, 'message' => 'Profile picture size exceeds the maximum allowed size of 800KB.']);
                exit();
            }
        } else {
            // Invalid file type
            echo json_encode(['success' => false, 'message' => 'Invalid file type for profile picture. Allowed types are JPG, PNG, GIF.']);
            exit();
        }
    } else {
        // No file uploaded or an error occurred
        $profileImage = null; // You can set a default image or handle accordingly
    }

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

        if ($profileImage !== null) {
            // Image uploaded
            $sqlInsertUser = "INSERT INTO useraccounts (Username, Password, Role, EmailAddress, employeeID, officerID, LastLogin, ActivationStatus, ActivationToken, UserImage)
                              VALUES (?, ?, 'Employee', ?, ?, NULL, NULL, ?, ?, ?)";
            $stmtUser = $conn->prepare($sqlInsertUser);
            if (!$stmtUser) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
                exit();
            }
            $null = NULL; // Placeholder for blob data
            $stmtUser->bind_param('sssissb', $username, $hashedPassword, $emailAddress, $employeeID, $activationStatus, $activationToken, $null);
            // Send blob data
            $stmtUser->send_long_data(6, $profileImage);
        } else {
            // No image uploaded
            $sqlInsertUser = "INSERT INTO useraccounts (Username, Password, Role, EmailAddress, employeeID, officerID, LastLogin, ActivationStatus, ActivationToken, UserImage)
                              VALUES (?, ?, 'Employee', ?, ?, NULL, NULL, ?, ?, NULL)";
            $stmtUser = $conn->prepare($sqlInsertUser);
            if (!$stmtUser) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
                exit();
            }
            $stmtUser->bind_param('sssisss', $username, $hashedPassword, $emailAddress, $employeeID, $activationStatus, $activationToken);
        }

        // Execute the statement
        if ($stmtUser->execute()) {
            // Send activation email
            $activationLink = "http://localhost/EPM-Trucking-Services/activate_account.php?token=$activationToken";

            // Configure PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = $_ENV['SMTP_HOST'];
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['SMTP_USERNAME'];
                $mail->Password = $_ENV['SMTP_PASSWORD'];
                $mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION'];
                $mail->Port = $_ENV['SMTP_PORT'];

                $mail->setFrom($_ENV['FROM_EMAIL'], $_ENV['FROM_NAME']);
                $mail->addAddress($emailAddress, $firstName . ' ' . $lastName);

                $mail->isHTML(true);
                $mail->Subject = 'Activate Your Account';
                $mail->Body = "Dear $firstName,<br><br>
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