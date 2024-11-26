<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['UserID'])) {
    // Redirect to login page if not logged in
    header("Location: ../index.php");
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
    // Collect Officer Data from POST
    $firstName = $_POST['firstName'];
    $middleInitial = isset($_POST['middleInitial']) ? $_POST['middleInitial'] : ''; // Optional
    $lastName = $_POST['lastName'];
    $gender = $_POST['gender'];
    $address = isset($_POST['address']) ? $_POST['address'] : ''; // Optional
    $mobileNo = $_POST['mobileNo'];
    $emailAddress = $_POST['emailAddress']; // Corrected field name
    $position = $_POST['position'];
    $college = isset($_POST['college']) ? $_POST['college'] : ''; // Optional
    $program = isset($_POST['program']) ? $_POST['program'] : ''; // Optional
    $yearGraduated = isset($_POST['yearGraduated']) ? $_POST['yearGraduated'] : ''; // Optional

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

        // Enforce strong password
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
            echo "Password must be at least 8 characters long and include at least one letter and one number.";
            exit();
        }
    } else {
        // Automatically generate a random password
        $password = bin2hex(random_bytes(4)); // Generates an 8-character random password
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);

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
                echo "Profile picture size exceeds the maximum allowed size of 800KB.";
                exit();
            }
        } else {
            // Invalid file type
            echo "Invalid file type for profile picture. Allowed types are JPG, PNG, GIF.";
            exit();
        }
    } else {
        // No file uploaded or an error occurred
        $profileImage = null; // You can set a default image or handle accordingly
    }

    // Step 1: Check if the username or email already exists
    $sqlCheck = "SELECT COUNT(*) AS count FROM useraccounts WHERE Username = ? OR EmailAddress = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    if ($stmtCheck === false) {
        echo "Error preparing check statement: " . $conn->error;
        exit();
    }
    $stmtCheck->bind_param('ss', $username, $emailAddress);
    $stmtCheck->execute();
    $stmtCheck->bind_result($count);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($count > 0) {
        echo "Error: Username or email already exists!";
        exit();
    }

    // Step 2: Insert officer data into `officers` table
    $sqlInsertOfficer = "INSERT INTO officers (FirstName, MiddleInitial, LastName, Position, Gender, CityAddress, MobileNo, EmailAddress, College, Program, YearGraduated)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtOfficer = $conn->prepare($sqlInsertOfficer);
    if ($stmtOfficer === false) {
        echo "Error preparing officer insert statement: " . $conn->error;
        exit();
    }
    $stmtOfficer->bind_param('sssssssssss', $firstName, $middleInitial, $lastName, $position, $gender, $address, $mobileNo, $emailAddress, $college, $program, $yearGraduated);

    if ($stmtOfficer->execute()) {
        // Step 3: Get the last inserted officer ID
        $officerID = $conn->insert_id;

        // Step 4: Insert into `useraccounts` table
        if ($profileImage !== null) {
            // Image uploaded
            $sqlInsertUser = "INSERT INTO useraccounts (Username, Password, Role, EmailAddress, employeeID, officerID, LastLogin, ActivationStatus, ActivationToken, UserImage)
                              VALUES (?, ?, 'Officer', ?, NULL, ?, NULL, ?, ?, ?)";
            $stmtUser = $conn->prepare($sqlInsertUser);
            if ($stmtUser === false) {
                echo "Error preparing user account insert statement: " . $conn->error;
                exit();
            }
            $null = NULL; // Placeholder for blob data
            $stmtUser->bind_param('sssissb', $username, $hashedPassword, $emailAddress, $officerID, $activationStatus, $activationToken, $null);
            $stmtUser->send_long_data(6, $profileImage);
        } else {
            // No image uploaded
            $sqlInsertUser = "INSERT INTO useraccounts (Username, Password, Role, EmailAddress, employeeID, officerID, LastLogin, ActivationStatus, ActivationToken)
                              VALUES (?, ?, 'Officer', ?, NULL, ?, NULL, ?, ?)";
            $stmtUser = $conn->prepare($sqlInsertUser);
            if ($stmtUser === false) {
                echo "Error preparing user account insert statement: " . $conn->error;
                exit();
            }
            $stmtUser->bind_param('sssiss', $username, $hashedPassword, $emailAddress, $officerID, $activationStatus, $activationToken);
        }

        if ($stmtUser->execute()) {
            // Send activation email
            $activationLink = "https://www.epm-trucking-services.com/activate_account.php?token=$activationToken";

            // Configure PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Server settings
                // $mail->SMTPDebug = 2; // Enable verbose debug output (disable in production)
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
                $mail->Subject = 'Activate Your Account';
                $mail->Body = "Dear $firstName,<br><br>
                                  Your officer account has been created. Please click the link below to activate your account and set your password:<br><br>
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
            $action = "Added Officer: " . $username;

            // Get the current timestamp
            $currentTimestamp = date("Y-m-d H:i:s");

            // Prepare the INSERT statement for activitylogs
            $sqlInsertLog = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, NOW())";
            $stmtLog = $conn->prepare($sqlInsertLog);
            if ($stmtLog) {
                $stmtLog->bind_param('is', $currentUserID, $action);
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

            // Redirect to officers.php after successful insertion
            header("Location: officers.php");
            exit();
        } else {
            echo "Error: " . $stmtUser->error;
        }

        // Close the user account statement
        $stmtUser->close();
    } else {
        echo "Error: " . $stmtOfficer->error;
    }

    // Close the officer statement
    $stmtOfficer->close();

    // Close the database connection
    $conn->close();
}
?>