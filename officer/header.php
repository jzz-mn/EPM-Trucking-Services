<?php
// header.php

// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection
require_once('../includes/db_connection.php');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure the user is logged in
    if (!isset($_SESSION['Username'])) {
        echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
        exit();
    }

    // Sanitize the action
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'mark_seen') {
        if (isset($_POST['last_seen_logid'])) {
            $last_seen_logid = intval($_POST['last_seen_logid']);
            $userID = intval($_SESSION['UserID']);

            // Update the database
            if ($stmt = $conn->prepare("UPDATE useraccounts SET last_seen_logid = ? WHERE UserID = ?")) {
                $stmt->bind_param("ii", $last_seen_logid, $userID);
                if ($stmt->execute()) {
                    // Also update the session
                    $_SESSION['last_seen_logid'] = $last_seen_logid;
                    echo json_encode(['status' => 'success']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
                }
                $stmt->close();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement']);
            }
            exit();
        }
    }
}

// Ensure the user is logged in
if (!isset($_SESSION['Username'])) {
    // Redirect to login page if not logged in
    header("Location: ../login/login.php");
    exit();
}

// Retrieve and sanitize user data from session
$username = htmlspecialchars($_SESSION['Username'], ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['Role'], ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($_SESSION['EmailAddress'], ENT_QUOTES, 'UTF-8');
$userID = intval($_SESSION['UserID']); // Ensure UserID is an integer

// Determine user role for access control
$isSuperAdmin = ($role === 'SuperAdmin');
$isOfficer = ($role === 'Officer');

// Initialize the user image source with a default placeholder
$userImageSrc = '../assets/images/profile/user-1.jpg';

// Get the user's image from the database
if ($stmt = $conn->prepare("SELECT UserImage FROM useraccounts WHERE Username = ?")) {
    $stmt->bind_param("s", $_SESSION['Username']);
    $stmt->execute();
    $stmt->bind_result($userImageData);
    if ($stmt->fetch() && !empty($userImageData)) {
        // Get the MIME type of the image
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($userImageData);

        // Build the data URI for the image
        $userImageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($userImageData);
    }
    $stmt->close();
}

// Fetch last_seen_logid from the session
$last_seen_logid = isset($_SESSION['last_seen_logid']) ? intval($_SESSION['last_seen_logid']) : 0;

// Function to build notification SQL based on user role
function buildNotificationQuery($isSuperAdmin, $isOfficer)
{
    if ($isSuperAdmin) {
        // SuperAdmin can view all activity logs
        $sql_new = "
            SELECT al.LogID, al.Action, al.TimeStamp, ua.Username 
            FROM activitylogs al
            JOIN useraccounts ua ON al.UserID = ua.UserID
            WHERE al.UserID != ? 
              AND al.Action NOT IN ('Logged In', 'Logged Out') 
              AND al.LogID > ? 
              AND al.TimeStamp >= DATE_SUB(NOW(), INTERVAL 10 DAY)
            ORDER BY al.TimeStamp DESC
        ";

        $sql_all = "
            SELECT al.LogID, al.Action, al.TimeStamp, ua.Username 
            FROM activitylogs al
            JOIN useraccounts ua ON al.UserID = ua.UserID
            WHERE al.UserID != ? 
              AND al.Action NOT IN ('Logged In', 'Logged Out') 
              AND al.TimeStamp >= DATE_SUB(NOW(), INTERVAL 10 DAY)
            ORDER BY al.TimeStamp DESC
        ";
    } elseif ($isOfficer) {
        // Officer can view activity logs where the action is performed by Employees
        $sql_new = "
            SELECT al.LogID, al.Action, al.TimeStamp, ua.Username 
            FROM activitylogs al
            JOIN useraccounts ua ON al.UserID = ua.UserID
            JOIN useraccounts u2 ON al.UserID = u2.UserID
            WHERE al.UserID != ? 
              AND al.Action NOT IN ('Logged In', 'Logged Out') 
              AND al.LogID > ? 
              AND al.TimeStamp >= DATE_SUB(NOW(), INTERVAL 10 DAY)
              AND u2.Role = 'Employee'
            ORDER BY al.TimeStamp DESC
        ";

        $sql_all = "
            SELECT al.LogID, al.Action, al.TimeStamp, ua.Username 
            FROM activitylogs al
            JOIN useraccounts ua ON al.UserID = ua.UserID
            JOIN useraccounts u2 ON al.UserID = u2.UserID
            WHERE al.UserID != ? 
              AND al.Action NOT IN ('Logged In', 'Logged Out') 
              AND al.TimeStamp >= DATE_SUB(NOW(), INTERVAL 10 DAY)
              AND u2.Role = 'Employee'
            ORDER BY al.TimeStamp DESC
        ";
    } else {
        // For other roles, no access to notifications
        $sql_new = "";
        $sql_all = "";
    }

    return [$sql_new, $sql_all];
}

// Build the notification queries based on role
list($sql_new_notifications, $sql_all_notifications) = buildNotificationQuery($isSuperAdmin, $isOfficer);

// Initialize notification variables
$notifications = [];
$new_notification_count = 0;
$max_logid = 0;
$all_notifications = [];
$all_notifications_count = 0;

// Fetch new notifications if the user has access
if (!empty($sql_new_notifications)) {
    $stmt = $conn->prepare($sql_new_notifications);
    $stmt->bind_param("ii", $userID, $last_seen_logid);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $new_notification_count = count($notifications);
    if ($new_notification_count > 0) {
        $max_logid = max(array_column($notifications, 'LogID'));
    }
    $stmt->close();
}

// Fetch all notifications for display in dropdown (last 10 days), excluding user's own actions
if (!empty($sql_all_notifications)) {
    $stmt = $conn->prepare($sql_all_notifications);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $all_notifications = $result->fetch_all(MYSQLI_ASSOC);
    $all_notifications_count = count($all_notifications);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Aqua_Theme" data-layout="vertical">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Icon-->
    <link rel="shortcut icon" type="image/png" href="../assetsEPM/logos/epm-logo.png" />

    <!-- Core Css -->
    <link rel="stylesheet" href="../assets/css/styles.css" />

    <title>EPM Trucking Services</title>
</head>

<body class="link-sidebar">

    <!-- Preloader -->

    <div id="main-wrapper">
        <!-- Sidebar Start -->
        <aside class="left-sidebar with-vertical">
            <div>
                <div class="brand-logo d-flex align-items-center">
                    <a href="../officer/home.php" class="text-nowrap logo-img">
                        <img src="../assetsEPM/logos/epm-logo-no-bg-light.png" alt="Logo" class="img-fluid"
                            style="max-width: 146px; height: auto;">
                    </a>
                </div>

                <!-- ---------------------------------- -->
                <!-- Dashboard -->
                <!-- ---------------------------------- -->
                <nav class="sidebar-nav scroll-sidebar" data-simplebar>
                    <ul class="sidebar-menu" id="sidebarnav">
                        <!-- ---------------------------------- -->
                        <!-- Home -->
                        <!-- ---------------------------------- -->
                        <li class="nav-small-cap">
                            <iconify-icon icon="solar:menu-dots-linear" class="mini-icon"></iconify-icon>
                            <span class="hide-menu">Menu</span>
                        </li>
                        <!-- ---------------------------------- -->
                        <!-- Dashboard -->
                        <!-- ---------------------------------- -->
                        <li class="sidebar-item">
                            <a id="get-url" aria-expanded="false">
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="../officer/home.php" aria-expanded="false">
                                <iconify-icon icon="mdi:home-outline"></iconify-icon>
                                <span class="hide-menu">Home</span>
                            </a>
                        </li>


                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="#" aria-expanded="false">
                                <iconify-icon icon="mdi:folder-outline"></iconify-icon>
                                <span class="hide-menu">Records</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="../officer/employees.php">
                                        <iconify-icon icon="mdi:account-group-outline"></iconify-icon>
                                        <span class="hide-menu">Employees</span>
                                    </a>
                                </li>
                                <?php
                                // Function to check if the user is logged in and has a specific role
                                function isUserRole($role)
                                {
                                    return isset($_SESSION['Role']) && $_SESSION['Role'] === $role;
                                }
                                ?>
                                <?php if (isUserRole('SuperAdmin')): // Updated condition to include 'Officer' ?>
                                    <li class="sidebar-item">
                                        <a class="sidebar-link" href="../officer/officers.php">
                                            <iconify-icon icon="mdi:badge-account-horizontal-outline"></iconify-icon>
                                            <span class="hide-menu">Officers</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="../officer/finance.php">
                                        <iconify-icon icon="mdi:finance"></iconify-icon>
                                        <span class="hide-menu">Finance</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="../officer/trucks.php">
                                        <iconify-icon icon="mdi:truck-outline"></iconify-icon>
                                        <span class="hide-menu">Trucks</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="#" aria-expanded="false">
                                <iconify-icon icon="mdi:chart-box-outline"></iconify-icon>
                                <span class="hide-menu">Analytics</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="../officer/analytics-finance.php">
                                        <iconify-icon icon="mdi:chart-line"></iconify-icon>
                                        <span class="hide-menu">Finance</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="../officer/analytics-routes.php">
                                        <iconify-icon icon="mdi:map-marker-path"></iconify-icon>
                                        <span class="hide-menu">Routes</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="../officer/analytics-maintenance.php">
                                        <iconify-icon icon="mdi:tools"></iconify-icon>
                                        <span class="hide-menu">Maintenance</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="../officer/invoice.php">
                                <iconify-icon icon="mdi:receipt"></iconify-icon>
                                <span class="hide-menu">Invoice</span>
                            </a>
                        </li>
                        <?php if (isUserRole('SuperAdmin') || isUserRole('Officer')): // Updated condition to include 'Officer' ?>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="../officer/activity-logs.php">
                                    <iconify-icon icon="mdi:history"></iconify-icon>
                                    <span class="hide-menu">Activity Logs</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </aside>
        <!--  Sidebar End -->

        <style>
            /* Override visited and active link styles */
            .sidebar-link iconify-icon {
                color: inherit;
                /* Retain the original color */
            }

            .sidebar-link:visited iconify-icon,
            .sidebar-link:active iconify-icon {
                color: inherit !important;
                /* Prevent color change after click */
            }

            /* Handle text overflow in notification cards */
            .notification-action {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* Adjust timestamp font size */
            .notification-time {
                font-size: 0.8rem;
                /* Small font size */
            }

            /* Indicator for viewed notifications */
            .notification-viewed {
                color: #6c757d;
                /* Bootstrap secondary color */
            }

            /* Indicator for new notifications */
            .notification-new {
                color: #000000;
                /* Bootstrap primary color */
                font-weight: bold;
            }

            /* Additional styles for notification indicators */
            .notification-icon {
                position: relative;
                /* Ensure the badge is positioned relative to this container */
            }

            .notification-badge {
                position: absolute;
                top: 0;
                right: 0;
                transform: translate(50%, -50%);
                /* Use Bootstrap's badge styling */
                padding: 0.25em 0.5em;
                font-size: 0.75rem;
            }

            /* Allow only badge and indicator spans within notification-icon to be displayed */
            .notification-icon span {
                display: none;
                /* Hide all spans by default */
            }

            .notification-icon .badge,
            .notification-icon {
                display: inline-block;
                /* Show the badge and indicator */
                z-index: 1;
                /* Ensure they appear above the icon */
            }

            /* Style the notification badge */
            .notification-icon .badge.notification-badge {
                position: absolute;
                top: 0;
                right: 0;
                transform: translate(50%, -50%);
                /* Ensure the badge is positioned correctly */
                padding: 0.25em 0.5em;
                font-size: 0.75rem;
            }


            /* Dark mode styles for notifications */
            .dark-mode .badge.bg-primary {
                background-color: #0d6efd;
            }
        </style>

        <div class="page-wrapper">
            <!--  Header Start -->
            <header class="topbar">
                <div class="with-vertical">
                    <!-- Start Vertical Layout Header -->
                    <nav class="navbar navbar-expand-lg p-0">
                        <ul class="navbar-nav">
                            <li class="nav-item nav-icon-hover-bg rounded-circle d-flex">
                                <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)">
                                    <iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                        </ul>

                        <div class="d-block d-lg-none py-9 py-xl-0">
                            <a href="../officer/home.php" class="text-nowrap logo-img">
                                <img src="../assetsEPM/logos/epm-logo-no-bg.png" alt="Logo" class="img-fluid"
                                    style="max-width: 146px; height: auto;">
                            </a>
                        </div>
                        <a class="navbar-toggler p-0 border-0 nav-icon-hover-bg rounded-circle"
                            href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                            <iconify-icon icon="solar:menu-dots-bold-duotone" class="fs-6"></iconify-icon>
                        </a>
                        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                            <div class="d-flex align-items-center justify-content-between">
                                <ul
                                    class="navbar-nav flex-row mx-auto ms-lg-auto align-items-center justify-content-center">
                                    <li class="nav-item">
                                        <a class="btn btn-primary" href="add_data.php">Add Data</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link moon dark-layout nav-icon-hover-bg rounded-circle"
                                            href="javascript:void(0)">
                                            <iconify-icon icon="solar:moon-line-duotone"
                                                class="moon fs-6"></iconify-icon>
                                        </a>
                                        <a class="nav-link sun light-layout nav-icon-hover-bg rounded-circle"
                                            href="javascript:void(0)" style="display: none">
                                            <iconify-icon icon="solar:sun-2-line-duotone"
                                                class="sun fs-6"></iconify-icon>
                                        </a>
                                    </li>

                                    <!-- ------------------------------- -->
                                    <!-- start notification Dropdown -->
                                    <!-- ------------------------------- -->
                                    <?php if ($isSuperAdmin || $isOfficer): ?>
                                        <li class="nav-item dropdown nav-icon-hover-bg rounded-circle">
                                            <a class="nav-link position-relative notification-icon"
                                                href="javascript:void(0)" id="notificationDropdown" aria-expanded="false">
                                                <iconify-icon icon="solar:bell-bing-line-duotone"
                                                    class="fs-6"></iconify-icon>
                                                <?php if ($new_notification_count > 0): ?>
                                                    <!-- Notification Count Badge -->
                                                    <span
                                                        class="badge bg-primary rounded-pill position-absolute top-0 start-100 translate-middle">
                                                        <?php echo $new_notification_count; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </a>
                                            <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up"
                                                aria-labelledby="notificationDropdown">
                                                <div class="d-flex align-items-center justify-content-between py-3 px-7">
                                                    <h5 class="mb-0 fs-5 fw-semibold">Notifications</h5>
                                                    <?php if ($new_notification_count > 0): ?>
                                                        <span class="badge text-bg-primary rounded-4 px-3 py-1 lh-sm">
                                                            <?php echo $new_notification_count; ?> new
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="message-body" data-simplebar style="max-height: 300px;">
                                                    <?php if ($all_notifications_count > 0): ?>
                                                        <?php foreach ($all_notifications as $notification): ?>
                                                            <a href="../officer/activity-logs.php"
                                                                class="notification-item d-flex align-items-center px-7 py-3 border-bottom">
                                                                <span
                                                                    class="flex-shrink-0 bg-primary-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-primary">
                                                                    <iconify-icon
                                                                        icon="solar:bell-bing-line-duotone"></iconify-icon>
                                                                </span>
                                                                <div class="w-75 d-inline-block ms-3">
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <?php
                                                                        // Determine if the notification is viewed
                                                                        $is_viewed = ($notification['LogID'] <= $last_seen_logid);
                                                                        $notification_class = $is_viewed ? 'notification-viewed' : 'notification-new';
                                                                        ?>
                                                                        <h6
                                                                            class="mb-1 fw-semibold <?php echo $notification_class; ?>">
                                                                            <?php echo htmlspecialchars($notification['Username'], ENT_QUOTES, 'UTF-8'); ?>
                                                                        </h6>
                                                                        <span
                                                                            class="d-block fs-2 notification-time"><?php echo date("h:i A", strtotime($notification['TimeStamp'])); ?></span>
                                                                    </div>
                                                                    <span
                                                                        class="d-block text-truncate notification-action fs-11 <?php echo $notification_class; ?>">
                                                                        <?php echo htmlspecialchars($notification['Action'], ENT_QUOTES, 'UTF-8'); ?>
                                                                    </span>
                                                                </div>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="py-6 px-7 d-flex align-items-center justify-content-center">
                                                            <span class="text-muted">No notifications</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($all_notifications_count > 0): ?>
                                                    <div class="py-3 px-7 mb-1">
                                                        <a href="../officer/activity-logs.php" class="btn btn-primary w-100">See
                                                            All Notifications</a>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="py-6 px-7 mb-1">
                                                        <a href="../officer/activity-logs.php" class="btn btn-primary w-100">See
                                                            All Notifications</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endif; ?>

                                    <!-- ------------------------------- -->
                                    <!-- end notification Dropdown -->
                                    <!-- ------------------------------- -->

                                    <!-- Mini Profile -->
                                    <li class="nav-item dropdown">
                                        <a class="nav-link" href="javascript:void(0)" id="drop1" aria-expanded="false">
                                            <div class="d-flex align-items-center gap-2 lh-base">
                                                <!-- User's profile picture -->
                                                <img src="<?php echo $userImageSrc; ?>" class="rounded-circle"
                                                    width="35" height="35" alt="user-img" />
                                                <iconify-icon icon="solar:alt-arrow-down-bold"
                                                    class="fs-2"></iconify-icon>
                                            </div>
                                        </a>
                                        <div class="dropdown-menu profile-dropdown dropdown-menu-end dropdown-menu-animate-up"
                                            aria-labelledby="drop1">
                                            <div class="position-relative px-4 pt-3 pb-2">
                                                <div class="d-flex align-items-center mb-3 pb-3 border-bottom gap-6">
                                                    <!-- User's profile picture -->
                                                    <img src="<?php echo $userImageSrc; ?>" class="rounded-circle"
                                                        width="56" height="56" alt="user-img" />
                                                    <div>
                                                        <h5 class="mb-0 fs-12">
                                                            <?php echo $username; ?>
                                                            <span class="text-success fs-11"><?php echo $role; ?></span>
                                                        </h5>
                                                        <p class="mb-0 text-dark"
                                                            style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;">
                                                            <?php echo $email; ?>
                                                        </p>

                                                    </div>
                                                </div>
                                                <div class="message-body">
                                                    <a href="../officer/page-account-settings.php"
                                                        class="p-2 dropdown-item h6 rounded-1">
                                                        My Profile
                                                    </a>
                                                    <a href="../login/logout.php"
                                                        class="p-2 dropdown-item h6 rounded-1">
                                                        Sign Out
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </nav>
                    <!-- End Vertical Layout Header -->

                </div>
                <div class="app-header with-horizontal">
                    <nav class="navbar navbar-expand-xl container-fluid p-0">
                        <ul class="navbar-nav align-items-center">
                            <li class="nav-item d-flex d-xl-none">
                                <a class="nav-link sidebartoggler nav-icon-hover-bg rounded-circle" id="sidebarCollapse"
                                    href="javascript:void(0)">
                                    <iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-7"></iconify-icon>
                                </a>
                            </li>
                            <li class="nav-item d-none d-xl-flex align-items-center">
                                <a href="../horizontal/home.php" class="text-nowrap nav-link">
                                    <img src="../assets/images/logos/logo.svg" alt="matdash-img" />
                                </a>
                            </li>
                            <li class="nav-item d-none d-xl-flex align-items-center nav-icon-hover-bg rounded-circle">
                                <a class="nav-link" href="javascript:void(0)" data-bs-toggle="modal"
                                    data-bs-target="#exampleModal">
                                    <iconify-icon icon="solar:magnifer-linear" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li
                                class="nav-item d-none d-lg-flex align-items-center dropdown nav-icon-hover-bg rounded-circle">
                                <div class="hover-dd">
                                    <a class="nav-link" id="drop2" href="javascript:void(0)" aria-haspopup="true"
                                        aria-expanded="false">
                                        <iconify-icon icon="solar:widget-3-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </div>
                            </li>
                        </ul>
                        <div class="d-block d-xl-none">
                            <a href="../main/home.php" class="text-nowrap nav-link">
                                <img src="../assets/images/logos/logo.svg" alt="matdash-img" />
                            </a>
                        </div>
                        <a class="navbar-toggler nav-icon-hover p-0 border-0 nav-icon-hover-bg rounded-circle"
                            href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="p-2">
                                <i class="ti ti-dots fs-7"></i>
                            </span>
                        </a>
                        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                            <div class="d-flex align-items-center justify-content-between px-0 px-xl-8">
                                <ul
                                    class="navbar-nav flex-row mx-auto ms-lg-auto align-items-center justify-content-center">
                                    <li class="nav-item dropdown">
                                        <a href="javascript:void(0)"
                                            class="nav-link nav-icon-hover-bg rounded-circle d-flex d-lg-none align-items-center justify-content-center"
                                            type="button" data-bs-toggle="offcanvas" data-bs-target="#mobilenavbar"
                                            aria-controls="offcanvasWithBothOptions">
                                            <iconify-icon icon="solar:sort-line-duotone" class="fs-6"></iconify-icon>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link nav-icon-hover-bg rounded-circle moon dark-layout"
                                            href="javascript:void(0)">
                                            <iconify-icon icon="solar:moon-line-duotone"
                                                class="moon fs-6"></iconify-icon>
                                        </a>
                                        <a class="nav-link nav-icon-hover-bg rounded-circle sun light-layout"
                                            href="javascript:void(0)" style="display: none">
                                            <iconify-icon icon="solar:sun-2-line-duotone"
                                                class="sun fs-6"></iconify-icon>
                                        </a>
                                    </li>
                                    <li class="nav-item d-block d-xl-none">
                                        <a class="nav-link nav-icon-hover-bg rounded-circle" href="javascript:void(0)"
                                            data-bs-toggle="modal" data-bs-target="#exampleModal">
                                            <iconify-icon icon="solar:magnifer-line-duotone"
                                                class="fs-6"></iconify-icon>
                                        </a>
                                    </li>

                                    <!-- ------------------------------- -->
                                    <!-- start notification Dropdown (Horizontal Layout) -->
                                    <!-- ------------------------------- -->
                                    <?php if ($isSuperAdmin || $isOfficer): ?>
                                        <li class="nav-item dropdown nav-icon-hover-bg rounded-circle">
                                            <a class="nav-link position-relative notification-icon"
                                                href="javascript:void(0)" id="notificationDropdownHorizontal"
                                                aria-expanded="false">
                                                <iconify-icon icon="solar:bell-bing-line-duotone"
                                                    class="fs-6"></iconify-icon>
                                                <?php if ($new_notification_count > 0): ?>
                                                    <!-- Notification Count Badge -->
                                                    <span
                                                        class="badge bg-primary rounded-pill position-absolute top-0 start-100 translate-middle">
                                                        <?php echo $new_notification_count; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </a>
                                            <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up"
                                                aria-labelledby="notificationDropdownHorizontal">
                                                <div class="d-flex align-items-center justify-content-between py-3 px-7">
                                                    <h5 class="mb-0 fs-5 fw-semibold">Notifications</h5>
                                                    <?php if ($new_notification_count > 0): ?>
                                                        <span class="badge text-bg-primary rounded-4 px-3 py-1 lh-sm">
                                                            <?php echo $new_notification_count; ?> new
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="message-body" data-simplebar style="max-height: 300px;">
                                                    <?php if ($all_notifications_count > 0): ?>
                                                        <?php foreach ($all_notifications as $notification): ?>
                                                            <a href="../officer/activity-logs.php"
                                                                class="notification-item d-flex align-items-center px-7 py-3 border-bottom">
                                                                <span
                                                                    class="flex-shrink-0 bg-primary-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-primary">
                                                                    <iconify-icon
                                                                        icon="solar:bell-bing-line-duotone"></iconify-icon>
                                                                </span>
                                                                <div class="w-75 d-inline-block ms-3">
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <?php
                                                                        // Determine if the notification is viewed
                                                                        $is_viewed = ($notification['LogID'] <= $last_seen_logid);
                                                                        $notification_class = $is_viewed ? 'notification-viewed' : 'notification-new';
                                                                        ?>
                                                                        <h6
                                                                            class="mb-1 fw-semibold <?php echo $notification_class; ?>">
                                                                            <?php echo htmlspecialchars($notification['Username'], ENT_QUOTES, 'UTF-8'); ?>
                                                                        </h6>
                                                                        <span
                                                                            class="d-block fs-2 notification-time"><?php echo date("h:i A", strtotime($notification['TimeStamp'])); ?></span>
                                                                    </div>
                                                                    <span
                                                                        class="d-block text-truncate notification-action fs-11 <?php echo $notification_class; ?>">
                                                                        <?php echo htmlspecialchars($notification['Action'], ENT_QUOTES, 'UTF-8'); ?>
                                                                    </span>
                                                                </div>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="py-6 px-7 d-flex align-items-center justify-content-center">
                                                            <span class="text-muted">No notifications</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($all_notifications_count > 0): ?>
                                                    <div class="py-3 px-7 mb-1">
                                                        <a href="../officer/activity-logs.php" class="btn btn-primary w-100">See
                                                            All Notifications</a>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="py-6 px-7 mb-1">
                                                        <a href="../officer/activity-logs.php" class="btn btn-primary w-100">See
                                                            All Notifications</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endif; ?>

                                    <!-- ------------------------------- -->
                                    <!-- end notification Dropdown (Horizontal Layout) -->
                                    <!-- ------------------------------- -->

                                    <!-- start profile Dropdown -->
                                    <li class="nav-item dropdown">
                                        <a class="nav-link" href="javascript:void(0)" id="drop1" aria-expanded="false">
                                            <div class="d-flex align-items-center gap-2 lh-base">
                                                <!-- Placeholder image; update dynamically if image data becomes available -->
                                                <img src="<?php echo $userImageSrc; ?>" class="rounded-circle"
                                                    width="35" height="35" alt="matdash-img" />
                                                <iconify-icon icon="solar:alt-arrow-down-bold"
                                                    class="fs-2"></iconify-icon>
                                            </div>
                                        </a>
                                        <div class="dropdown-menu profile-dropdown dropdown-menu-end dropdown-menu-animate-up"
                                            aria-labelledby="drop1">
                                            <div class="position-relative px-4 pt-3 pb-2">
                                                <div class="d-flex align-items-center mb-3 pb-3 border-bottom gap-6">
                                                    <!-- User's profile picture -->
                                                    <img src="<?php echo $userImageSrc; ?>" class="rounded-circle"
                                                        width="56" height="56" alt="user-img" />
                                                    <div>
                                                        <h5 class="mb-0 fs-12">
                                                            <?php echo $username; ?>
                                                            <span class="text-success fs-11"><?php echo $role; ?></span>
                                                        </h5>
                                                        <p class="mb-0 text-dark"
                                                            style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;">
                                                            <?php echo $email; ?>
                                                        </p>

                                                    </div>
                                                </div>
                                                <div class="message-body">
                                                    <a href="../officer/page-account-settings.php"
                                                        class="p-2 dropdown-item h6 rounded-1">
                                                        My Profile
                                                    </a>
                                                    <a href="../login/logout.php"
                                                        class="p-2 dropdown-item h6 rounded-1">
                                                        Sign Out
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </nav>
                    <!-- End Vertical Layout Header -->
                </div>
                <div class="app-header with-horizontal">
                    <nav class="navbar navbar-expand-xl container-fluid p-0">
                        <ul class="navbar-nav align-items-center">
                            <li class="nav-item d-flex d-xl-none">
                                <a class="nav-link sidebartoggler nav-icon-hover-bg rounded-circle" id="sidebarCollapse"
                                    href="javascript:void(0)">
                                    <iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-7"></iconify-icon>
                                </a>
                            </li>
                            <li class="nav-item d-none d-xl-flex align-items-center">
                                <a href="../horizontal/home.php" class="text-nowrap nav-link">
                                    <img src="../assets/images/logos/logo.svg" alt="matdash-img" />
                                </a>
                            </li>
                            <li class="nav-item d-none d-xl-flex align-items-center nav-icon-hover-bg rounded-circle">
                                <a class="nav-link" href="javascript:void(0)" data-bs-toggle="modal"
                                    data-bs-target="#exampleModal">
                                    <iconify-icon icon="solar:magnifer-linear" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li
                                class="nav-item d-none d-lg-flex align-items-center dropdown nav-icon-hover-bg rounded-circle">
                                <div class="hover-dd">
                                    <a class="nav-link" id="drop2" href="javascript:void(0)" aria-haspopup="true"
                                        aria-expanded="false">
                                        <iconify-icon icon="solar:widget-3-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </div>
                            </li>
                        </ul>
                        <div class="d-block d-xl-none">
                            <a href="../main/home.php" class="text-nowrap nav-link">
                                <img src="../assets/images/logos/logo.svg" alt="matdash-img" />
                            </a>
                        </div>
                        <a class="navbar-toggler nav-icon-hover p-0 border-0 nav-icon-hover-bg rounded-circle"
                            href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="p-2">
                                <i class="ti ti-dots fs-7"></i>
                            </span>
                        </a>
                        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                            <div class="d-flex align-items-center justify-content-between px-0 px-xl-8">
                                <ul
                                    class="navbar-nav flex-row mx-auto ms-lg-auto align-items-center justify-content-center">
                                    <li class="nav-item dropdown">
                                        <a href="javascript:void(0)"
                                            class="nav-link nav-icon-hover-bg rounded-circle d-flex d-lg-none align-items-center justify-content-center"
                                            type="button" data-bs-toggle="offcanvas" data-bs-target="#mobilenavbar"
                                            aria-controls="offcanvasWithBothOptions">
                                            <iconify-icon icon="solar:sort-line-duotone" class="fs-6"></iconify-icon>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link nav-icon-hover-bg rounded-circle moon dark-layout"
                                            href="javascript:void(0)">
                                            <iconify-icon icon="solar:moon-line-duotone"
                                                class="moon fs-6"></iconify-icon>
                                        </a>
                                        <a class="nav-link nav-icon-hover-bg rounded-circle sun light-layout"
                                            href="javascript:void(0)" style="display: none">
                                            <iconify-icon icon="solar:sun-2-line-duotone"
                                                class="sun fs-6"></iconify-icon>
                                        </a>
                                    </li>
                                    <li class="nav-item d-block d-xl-none">
                                        <a class="nav-link nav-icon-hover-bg rounded-circle" href="javascript:void(0)"
                                            data-bs-toggle="modal" data-bs-target="#exampleModal">
                                            <iconify-icon icon="solar:magnifer-line-duotone"
                                                class="fs-6"></iconify-icon>
                                        </a>
                                    </li>

                                    <!-- ------------------------------- -->
                                    <!-- start notification Dropdown (Horizontal Layout) -->
                                    <!-- ------------------------------- -->
                                    <?php if ($isSuperAdmin || $isOfficer): // Only show notifications if user has access ?>
                                        <li class="nav-item dropdown nav-icon-hover-bg rounded-circle">
                                            <a class="nav-link position-relative notification-icon"
                                                href="javascript:void(0)" id="notificationDropdownHorizontal"
                                                aria-expanded="false">
                                                <iconify-icon icon="solar:bell-bing-line-duotone"
                                                    class="fs-6"></iconify-icon>
                                                <?php if ($new_notification_count > 0): ?>
                                                    <!-- Notification Count Badge -->
                                                    <span
                                                        class="badge bg-primary rounded-pill notification-badge"><?php echo $new_notification_count; ?></span>
                                                <?php endif; ?>
                                            </a>
                                            <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up"
                                                aria-labelledby="notificationDropdownHorizontal">
                                                <div class="d-flex align-items-center justify-content-between py-3 px-7">
                                                    <h5 class="mb-0 fs-5 fw-semibold">Notifications</h5>
                                                    <?php if ($new_notification_count > 0): ?>
                                                        <span class="badge text-bg-primary rounded-4 px-3 py-1 lh-sm">
                                                            <?php echo $new_notification_count; ?> new
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="message-body" data-simplebar style="max-height: 300px;">
                                                    <?php if ($all_notifications_count > 0): ?>
                                                        <?php foreach ($all_notifications as $notification): ?>
                                                            <a href="../officer/activity-logs.php"
                                                                class="notification-item d-flex align-items-center px-7 py-3 border-bottom">
                                                                <span
                                                                    class="flex-shrink-0 bg-primary-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-primary">
                                                                    <iconify-icon
                                                                        icon="solar:bell-bing-line-duotone"></iconify-icon>
                                                                </span>
                                                                <div class="w-75 d-inline-block ms-3">
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <?php
                                                                        // Determine if the notification is viewed
                                                                        $is_viewed = ($notification['LogID'] <= $last_seen_logid);
                                                                        $notification_class = $is_viewed ? 'notification-viewed' : 'notification-new';
                                                                        ?>
                                                                        <h6
                                                                            class="mb-1 fw-semibold <?php echo $notification_class; ?>">
                                                                            <?php echo htmlspecialchars($notification['Username'], ENT_QUOTES, 'UTF-8'); ?>
                                                                        </h6>
                                                                        <span
                                                                            class="d-block fs-2 notification-time"><?php echo date("h:i A", strtotime($notification['TimeStamp'])); ?></span>
                                                                    </div>
                                                                    <span
                                                                        class="d-block text-truncate notification-action fs-11 <?php echo $notification_class; ?>">
                                                                        <?php echo htmlspecialchars($notification['Action'], ENT_QUOTES, 'UTF-8'); ?>
                                                                    </span>
                                                                </div>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="py-6 px-7 d-flex align-items-center justify-content-center">
                                                            <span class="text-muted">No notifications</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($all_notifications_count > 0): ?>
                                                    <div class="py-3 px-7 mb-1">
                                                        <a href="../officer/activity-logs.php" class="btn btn-primary w-100">See
                                                            All Notifications</a>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="py-6 px-7 mb-1">
                                                        <a href="../officer/activity-logs.php" class="btn btn-primary w-100">See
                                                            All Notifications</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                    <!-- ------------------------------- -->
                                    <!-- end notification Dropdown (Horizontal Layout) -->
                                    <!-- ------------------------------- -->

                                    <!-- start profile Dropdown -->
                                    <li class="nav-item dropdown">
                                        <a class="nav-link" href="javascript:void(0)" id="drop1" aria-expanded="false">
                                            <div class="d-flex align-items-center gap-2 lh-base">
                                                <!-- Placeholder image; update dynamically if image data becomes available -->
                                                <img src="<?php echo $userImageSrc; ?>" class="rounded-circle"
                                                    width="35" height="35" alt="matdash-img" />
                                                <iconify-icon icon="solar:alt-arrow-down-bold"
                                                    class="fs-2"></iconify-icon>
                                            </div>
                                        </a>
                                        <div class="dropdown-menu profile-dropdown dropdown-menu-end dropdown-menu-animate-up"
                                            aria-labelledby="drop1">
                                            <div class="position-relative px-4 pt-3 pb-2">
                                                <div class="d-flex align-items-center mb-3 pb-3 border-bottom gap-6">
                                                    <!-- User's profile picture -->
                                                    <img src="<?php echo $userImageSrc; ?>" class="rounded-circle"
                                                        width="56" height="56" alt="user-img" />
                                                    <div>
                                                        <h5 class="mb-0 fs-12">
                                                            <?php echo $username; ?>
                                                            <span class="text-success fs-11"><?php echo $role; ?></span>
                                                        </h5>
                                                        <p class="mb-0 text-dark"
                                                            style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;">
                                                            <?php echo $email; ?>
                                                        </p>

                                                    </div>
                                                </div>
                                                <div class="message-body">
                                                    <a href="../officer/page-account-settings.php"
                                                        class="p-2 dropdown-item h6 rounded-1">
                                                        My Profile
                                                    </a>
                                                    <a href="../login/logout.php"
                                                        class="p-2 dropdown-item h6 rounded-1">
                                                        Sign Out
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </li>

                                </ul>
                            </div>
                        </div>
                    </nav>
                    <!-- End Vertical Layout Header -->

                </div>
                <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        // Function to mark notifications as seen
                        function markNotificationsAsSeen(maxLogID, dropdownId) {
                            $.ajax({
                                url: '', // Current page
                                type: 'POST',
                                data: {
                                    action: 'mark_seen',
                                    last_seen_logid: maxLogID
                                },
                                success: function (response) {
                                    try {
                                        var res = JSON.parse(response);
                                        if (res.status === 'success') {
                                            // Hide the badge
                                            $(dropdownId).find('.badge').remove();

                                            // Update notification items to viewed
                                            $(dropdownId).find('.notification-new').removeClass('notification-new').addClass('notification-viewed');
                                        } else {
                                            console.error(res.message);
                                        }
                                    } catch (e) {
                                        console.error('Invalid JSON response');
                                    }
                                },
                                error: function () {
                                    console.error('Failed to mark notifications as seen.');
                                }
                            });
                        }

                        <?php if ($isSuperAdmin || $isOfficer): // Only add event listeners if the user has access ?>
                            // Handle click on notification icon (Vertical Layout)
                            $('#notificationDropdown').on('click', function () {
                                <?php if ($new_notification_count > 0): ?>
                                    markNotificationsAsSeen(<?php echo $max_logid; ?>, '#notificationDropdown');
                                <?php endif; ?>
                            });

                            // Handle hover on notification icon (Vertical Layout)
                            $('#notificationDropdown').on('mouseenter', function () {
                                <?php if ($new_notification_count > 0): ?>
                                    markNotificationsAsSeen(<?php echo $max_logid; ?>, '#notificationDropdown');
                                <?php endif; ?>
                            });

                            // Handle click on notification icon (Horizontal Layout)
                            $('#notificationDropdownHorizontal').on('click', function () {
                                <?php if ($new_notification_count > 0): ?>
                                    markNotificationsAsSeen(<?php echo $max_logid; ?>, '#notificationDropdownHorizontal');
                                <?php endif; ?>
                            });

                            // Handle hover on notification icon (Horizontal Layout)
                            $('#notificationDropdownHorizontal').on('mouseenter', function () {
                                <?php if ($new_notification_count > 0): ?>
                                    markNotificationsAsSeen(<?php echo $max_logid; ?>, '#notificationDropdownHorizontal');
                                <?php endif; ?>
                            });
                        <?php endif; ?>

                        // No need for delete and clear-all event handlers since they are removed
                    });
                </script>

                <script>
                    // Load theme preference from localStorage
                    document.addEventListener("DOMContentLoaded", function () {
                        const savedTheme = localStorage.getItem("theme");
                        if (savedTheme) {
                            document.documentElement.setAttribute("data-bs-theme", savedTheme);
                            toggleIcons(savedTheme);
                        }

                        // Add click events for theme toggle buttons
                        document.querySelectorAll(".dark-layout").forEach((element) => {
                            element.addEventListener("click", () => {
                                setTheme("dark");
                            });
                        });

                        document.querySelectorAll(".light-layout").forEach((element) => {
                            element.addEventListener("click", () => {
                                setTheme("light");
                            });
                        });
                    });

                    // Function to set theme and save preference in localStorage
                    function setTheme(theme) {
                        document.documentElement.setAttribute("data-bs-theme", theme);
                        localStorage.setItem("theme", theme);
                        toggleIcons(theme);
                    }

                    // Toggle icons based on theme
                    function toggleIcons(theme) {
                        const isDark = theme === "dark";
                        document.querySelectorAll(".sun").forEach(el => el.style.display = isDark ? "flex" : "none");
                        document.querySelectorAll(".moon").forEach(el => el.style.display = isDark ? "none" : "flex");
                    }
                </script>

                <?php
                // Start the session if not already started
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                ?>
                <script>
                    <?php if (isset($_SESSION['reset_theme']) && $_SESSION['reset_theme'] === true): ?>
                        // Clear theme preference from localStorage
                        localStorage.removeItem("theme");
                        <?php $_SESSION['reset_theme'] = false; // Reset the flag 
                            ?>
                    <?php endif; ?>
                </script>

            </header>