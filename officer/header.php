<?php
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
      $_SESSION['last_seen_logid'] = intval($_POST['last_seen_logid']);
      echo json_encode(['status' => 'success']);
      exit();
    }
  } elseif ($action === 'delete_notification') {
    if (isset($_POST['logid'])) {
      $logid = intval($_POST['logid']);
      $userID = intval($_SESSION['UserID']); // Ensure UserID is an integer

      // Prepare and execute the delete statement
      if ($stmt = $conn->prepare("DELETE FROM activitylogs WHERE LogID = ? AND UserID = ?")) {
        $stmt->bind_param("ii", $logid, $userID);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
          echo json_encode(['status' => 'success']);
        } else {
          echo json_encode(['status' => 'error', 'message' => 'Notification not found']);
        }
        $stmt->close();
        exit();
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
        exit();
      }
    }
  } elseif ($action === 'clear_notifications') {
    $userID = intval($_SESSION['UserID']); // Ensure UserID is an integer

    // Prepare and execute the delete statement
    if ($stmt = $conn->prepare("DELETE FROM activitylogs WHERE UserID = ? AND Action NOT IN ('Logged In', 'Logged Out')")) {
      $stmt->bind_param("i", $userID);
      $stmt->execute();
      echo json_encode(['status' => 'success']);
      $stmt->close();
      exit();
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Database error']);
      exit();
    }
  }
}

// Check if the user is logged in
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

// Get last_seen_logid from session
$last_seen_logid = isset($_SESSION['last_seen_logid']) ? intval($_SESSION['last_seen_logid']) : 0;

// Fetch new notifications for badge count
// Join with useraccounts to get the Username of the action performer
$stmt = $conn->prepare("
    SELECT al.LogID, al.Action, al.TimeStamp, ua.Username 
    FROM activitylogs al
    JOIN useraccounts ua ON al.UserID = ua.UserID
    WHERE al.UserID = ? 
      AND al.Action NOT IN ('Logged In', 'Logged Out') 
      AND al.LogID > ? 
    ORDER BY al.TimeStamp DESC
");
$stmt->bind_param("ii", $userID, $last_seen_logid);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$new_notification_count = count($notifications);
$max_logid = 0;
if ($new_notification_count > 0) {
  $max_logid = max(array_column($notifications, 'LogID'));
}
$stmt->close();

// Fetch all notifications for display in dropdown
$stmt = $conn->prepare("
    SELECT al.LogID, al.Action, al.TimeStamp, ua.Username 
    FROM activitylogs al
    JOIN useraccounts ua ON al.UserID = ua.UserID
    WHERE al.UserID = ? 
      AND al.Action NOT IN ('Logged In', 'Logged Out') 
    ORDER BY al.TimeStamp DESC
");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$all_notifications = $result->fetch_all(MYSQLI_ASSOC);
$all_notifications_count = count($all_notifications);
$stmt->close();
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
            <img src="../assetsEPM/logos/epm-logo-no-bg1.png" alt="Logo" class="img-fluid"
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
                <?php if (isUserRole('SuperAdmin')): ?>
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
            <?php if (isUserRole('SuperAdmin')): ?>
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

      /* Style for the badge */
      .notification-badge {
        position: absolute;
        top: 0;
        right: 0;
        transform: translate(50%, -50%);
      }

      /* Style for delete icon */
      .delete-notification {
        cursor: pointer;
        color: #0d6efd;
        /* Bootstrap Primary Color */
        margin-left: auto;
      }

      /* Style for 'Clear All' button */
      .clear-all {
        cursor: pointer;
        color: #0d6efd;
        /* Bootstrap Primary Color */
        text-decoration: underline;
        text-align: right;
      }

      /* Adjust timestamp font size */
      .notification-time {
        font-size: 0.8rem;
        /* Small font size */
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
            <a class="navbar-toggler p-0 border-0 nav-icon-hover-bg rounded-circle" href="javascript:void(0)"
              data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
              aria-label="Toggle navigation">
              <iconify-icon icon="solar:menu-dots-bold-duotone" class="fs-6"></iconify-icon>
            </a>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
              <div class="d-flex align-items-center justify-content-between">
                <ul class="navbar-nav flex-row mx-auto ms-lg-auto align-items-center justify-content-center">
                  <li class="nav-item">
                    <a class="btn btn-primary" href="add_data.php">Add Data</a>
                  </li>

                  <li class="nav-item dropdown">
                    <a href="javascript:void(0)"
                      class="nav-link nav-icon-hover-bg rounded-circle d-flex d-lg-none align-items-center justify-content-center"
                      type="button" data-bs-toggle="offcanvas" data-bs-target="#mobilenavbar"
                      aria-controls="offcanvasWithBothOptions">
                      <iconify-icon icon="solar:sort-line-duotone" class="fs-6"></iconify-icon>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link moon dark-layout nav-icon-hover-bg rounded-circle" href="javascript:void(0)">
                      <iconify-icon icon="solar:moon-line-duotone" class="moon fs-6"></iconify-icon>
                    </a>
                    <a class="nav-link sun light-layout nav-icon-hover-bg rounded-circle" href="javascript:void(0)"
                      style="display: none">
                      <iconify-icon icon="solar:sun-2-line-duotone" class="sun fs-6"></iconify-icon>
                    </a>
                  </li>
                  <li class="nav-item d-block d-xl-none">
                    <a class="nav-link nav-icon-hover-bg rounded-circle" href="javascript:void(0)"
                      data-bs-toggle="modal" data-bs-target="#exampleModal">
                      <iconify-icon icon="solar:magnifer-line-duotone" class="fs-6"></iconify-icon>
                    </a>
                  </li>

                  <!-- ------------------------------- -->
                  <!-- start notification Dropdown -->
                  <!-- ------------------------------- -->
                  <li class="nav-item dropdown nav-icon-hover-bg rounded-circle">
                    <a class="nav-link position-relative notification-icon" href="javascript:void(0)"
                      id="notificationDropdown" aria-expanded="false">
                      <iconify-icon icon="solar:bell-bing-line-duotone" class="fs-6"></iconify-icon>
                      <?php if ($new_notification_count > 0): ?>
                        <span
                          class="badge bg-primary rounded-pill notification-badge"><?php echo $new_notification_count; ?></span>
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
                            <div class="notification-item d-flex align-items-center px-7 py-3 border-bottom">
                              <span class="flex-shrink-0 bg-<?php
                              // Determine the background based on Action type
                              // You can customize this logic as per your requirements
                              switch ($notification['Action']) {
                                case 'Launch Admin':
                                  echo 'danger-subtle';
                                  break;
                                case 'Event today':
                                  echo 'primary-subtle';
                                  break;
                                case 'Settings':
                                  echo 'secondary-subtle';
                                  break;
                                default:
                                  echo 'secondary-subtle';
                              }
                              ?> rounded-circle round d-flex align-items-center justify-content-center fs-6 text-<?php
                               switch ($notification['Action']) {
                                 case 'Launch Admin':
                                   echo 'danger';
                                   break;
                                 case 'Event today':
                                   echo 'primary';
                                   break;
                                 case 'Settings':
                                   echo 'secondary';
                                   break;
                                 default:
                                   echo 'secondary';
                               }
                               ?>">
                                <?php
                                // Determine the icon based on Action type
                                switch ($notification['Action']) {
                                  case 'Launch Admin':
                                    echo '<iconify-icon icon="solar:widget-3-line-duotone"></iconify-icon>';
                                    break;
                                  case 'Event today':
                                    echo '<iconify-icon icon="solar:calendar-line-duotone"></iconify-icon>';
                                    break;
                                  case 'Settings':
                                    echo '<iconify-icon icon="solar:settings-line-duotone"></iconify-icon>';
                                    break;
                                  default:
                                    echo '<iconify-icon icon="solar:activity-line-duotone"></iconify-icon>';
                                }
                                ?>
                              </span>
                              <div class="w-75 d-inline-block ms-3">
                                <div class="d-flex align-items-center justify-content-between">
                                  <h6 class="mb-1 fw-semibold">
                                    <?php echo htmlspecialchars($notification['Username'], ENT_QUOTES, 'UTF-8'); ?></h6>
                                  <span
                                    class="d-block fs-6 notification-time"><?php echo date("h:i A", strtotime($notification['TimeStamp'])); ?></span>
                                </div>
                                <span class="d-block text-truncate notification-action fs-11">
                                  <?php echo htmlspecialchars($notification['Action'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                              </div>
                              <span class="delete-notification" data-logid="<?php echo $notification['LogID']; ?>">
                                <iconify-icon icon="mdi:close-circle-outline" class="fs-6"></iconify-icon>
                              </span>
                            </div>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <div class="py-6 px-7 d-flex align-items-center justify-content-center">
                            <span class="text-muted">No notifications</span>
                          </div>
                        <?php endif; ?>
                      </div>
                      <?php if ($all_notifications_count > 0): ?>
                        <div class="py-3 px-7 d-flex justify-content-between">
                          <span class="clear-all">Clear All</span>
                        </div>
                        <div class="py-3 px-7 mb-1">
                          <a href="../officer/activity-logs.php" class="btn btn-primary w-100">See All Notifications</a>
                        </div>
                      <?php else: ?>
                        <div class="py-6 px-7 mb-1">
                          <a href="../officer/activity-logs.php" class="btn btn-primary w-100">See All Notifications</a>
                        </div>
                      <?php endif; ?>
                    </div>
                  </li>
                  <!-- ------------------------------- -->
                  <!-- end notification Dropdown -->
                  <!-- ------------------------------- -->

                  <!-- Mini Profile -->
                  <li class="nav-item dropdown">
                    <a class="nav-link" href="javascript:void(0)" id="drop1" aria-expanded="false">
                      <div class="d-flex align-items-center gap-2 lh-base">
                        <!-- User's profile picture -->
                        <img src="<?php echo $userImageSrc; ?>" class="rounded-circle" width="35" height="35"
                          alt="user-img" />
                        <iconify-icon icon="solar:alt-arrow-down-bold" class="fs-2"></iconify-icon>
                      </div>
                    </a>
                    <div class="dropdown-menu profile-dropdown dropdown-menu-end dropdown-menu-animate-up"
                      aria-labelledby="drop1">
                      <div class="position-relative px-4 pt-3 pb-2">
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom gap-6">
                          <!-- User's profile picture -->
                          <img src="<?php echo $userImageSrc; ?>" class="rounded-circle" width="56" height="56"
                            alt="user-img" />
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
                          <a href="../officer/page-account-settings.php" class="p-2 dropdown-item h6 rounded-1">
                            My Profile
                          </a>
                          <a href="../login/logout.php" class="p-2 dropdown-item h6 rounded-1">
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
                <a class="nav-link" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#exampleModal">
                  <iconify-icon icon="solar:magnifer-linear" class="fs-6"></iconify-icon>
                </a>
              </li>
              <li class="nav-item d-none d-lg-flex align-items-center dropdown nav-icon-hover-bg rounded-circle">
                <div class="hover-dd">
                  <a class="nav-link" id="drop2" href="javascript:void(0)" aria-haspopup="true" aria-expanded="false">
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
              href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav"
              aria-expanded="false" aria-label="Toggle navigation">
              <span class="p-2">
                <i class="ti ti-dots fs-7"></i>
              </span>
            </a>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
              <div class="d-flex align-items-center justify-content-between px-0 px-xl-8">
                <ul class="navbar-nav flex-row mx-auto ms-lg-auto align-items-center justify-content-center">
                  <li class="nav-item dropdown">
                    <a href="javascript:void(0)"
                      class="nav-link nav-icon-hover-bg rounded-circle d-flex d-lg-none align-items-center justify-content-center"
                      type="button" data-bs-toggle="offcanvas" data-bs-target="#mobilenavbar"
                      aria-controls="offcanvasWithBothOptions">
                      <iconify-icon icon="solar:sort-line-duotone" class="fs-6"></iconify-icon>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link nav-icon-hover-bg rounded-circle moon dark-layout" href="javascript:void(0)">
                      <iconify-icon icon="solar:moon-line-duotone" class="moon fs-6"></iconify-icon>
                    </a>
                    <a class="nav-link nav-icon-hover-bg rounded-circle sun light-layout" href="javascript:void(0)"
                      style="display: none">
                      <iconify-icon icon="solar:sun-2-line-duotone" class="sun fs-6"></iconify-icon>
                    </a>
                  </li>
                  <li class="nav-item d-block d-xl-none">
                    <a class="nav-link nav-icon-hover-bg rounded-circle" href="javascript:void(0)"
                      data-bs-toggle="modal" data-bs-target="#exampleModal">
                      <iconify-icon icon="solar:magnifer-line-duotone" class="fs-6"></iconify-icon>
                    </a>
                  </li>

                  <!-- ------------------------------- -->
                  <!-- start notification Dropdown -->
                  <!-- ------------------------------- -->
                  <li class="nav-item dropdown nav-icon-hover-bg rounded-circle">
                    <a class="nav-link position-relative notification-icon" href="javascript:void(0)"
                      id="notificationDropdownHorizontal" aria-expanded="false">
                      <iconify-icon icon="solar:bell-bing-line-duotone" class="fs-6"></iconify-icon>
                      <?php if ($new_notification_count > 0): ?>
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
                            <div class="notification-item d-flex align-items-center px-7 py-3 border-bottom">
                              <span class="flex-shrink-0 bg-<?php
                              // Determine the background based on Action type
                              // Customize as needed
                              switch ($notification['Action']) {
                                case 'Launch Admin':
                                  echo 'danger-subtle';
                                  break;
                                case 'Event today':
                                  echo 'primary-subtle';
                                  break;
                                case 'Settings':
                                  echo 'secondary-subtle';
                                  break;
                                default:
                                  echo 'secondary-subtle';
                              }
                              ?> rounded-circle round d-flex align-items-center justify-content-center fs-6 text-<?php
                               switch ($notification['Action']) {
                                 case 'Launch Admin':
                                   echo 'danger';
                                   break;
                                 case 'Event today':
                                   echo 'primary';
                                   break;
                                 case 'Settings':
                                   echo 'secondary';
                                   break;
                                 default:
                                   echo 'secondary';
                               }
                               ?>">
                                <?php
                                // Remove the icon as per user request
                                // If you still want icons based on Action, keep this switch case
                                // Otherwise, remove this block
                                /*
                                switch ($notification['Action']) {
                                    case 'Launch Admin':
                                        echo '<iconify-icon icon="solar:widget-3-line-duotone"></iconify-icon>';
                                        break;
                                    case 'Event today':
                                        echo '<iconify-icon icon="solar:calendar-line-duotone"></iconify-icon>';
                                        break;
                                    case 'Settings':
                                        echo '<iconify-icon icon="solar:settings-line-duotone"></iconify-icon>';
                                        break;
                                    default:
                                        echo '<iconify-icon icon="solar:activity-line-duotone"></iconify-icon>';
                                }
                                */
                                ?>
                              </span>
                              <div class="w-75 d-inline-block ms-3">
                                <div class="d-flex align-items-center justify-content-between">
                                  <h6 class="mb-1 fw-semibold">
                                    <?php echo htmlspecialchars($notification['Username'], ENT_QUOTES, 'UTF-8'); ?></h6>
                                  <span
                                    class="d-block fs-6 notification-time"><?php echo date("h:i A", strtotime($notification['TimeStamp'])); ?></span>
                                </div>
                                <span class="d-block text-truncate notification-action fs-11">
                                  <?php echo htmlspecialchars($notification['Action'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                              </div>
                              <span class="delete-notification" data-logid="<?php echo $notification['LogID']; ?>">
                                <iconify-icon icon="mdi:close-circle-outline" class="fs-6"></iconify-icon>
                              </span>
                            </div>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <div class="py-6 px-7 d-flex align-items-center justify-content-center">
                            <span class="text-muted">No notifications</span>
                          </div>
                        <?php endif; ?>
                      </div>
                      <?php if ($all_notifications_count > 0): ?>
                        <div class="py-3 px-7 d-flex justify-content-between">
                          <span class="clear-all">Clear All</span>
                        </div>
                        <div class="py-3 px-7 mb-1">
                          <a href="../officer/activity-logs.php" class="btn btn-primary w-100">See All Notifications</a>
                        </div>
                      <?php else: ?>
                        <div class="py-6 px-7 mb-1">
                          <a href="../officer/activity-logs.php" class="btn btn-primary w-100">See All Notifications</a>
                        </div>
                      <?php endif; ?>
                    </div>
                  </li>
                  <!-- ------------------------------- -->
                  <!-- end notification Dropdown -->
                  <!-- ------------------------------- -->

                  <!-- start profile Dropdown -->
                  <li class="nav-item dropdown">
                    <a class="nav-link" href="javascript:void(0)" id="drop1" aria-expanded="false">
                      <div class="d-flex align-items-center gap-2 lh-base">
                        <!-- Placeholder image; update dynamically if image data becomes available -->
                        <img src="<?php echo $userImageSrc; ?>" class="rounded-circle" width="35" height="35"
                          alt="matdash-img" />
                        <iconify-icon icon="solar:alt-arrow-down-bold" class="fs-2"></iconify-icon>
                      </div>
                    </a>
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
                <a class="nav-link" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#exampleModal">
                  <iconify-icon icon="solar:magnifer-linear" class="fs-6"></iconify-icon>
                </a>
              </li>
              <li class="nav-item d-none d-lg-flex align-items-center dropdown nav-icon-hover-bg rounded-circle">
                <div class="hover-dd">
                  <a class="nav-link" id="drop2" href="javascript:void(0)" aria-haspopup="true" aria-expanded="false">
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
              href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav"
              aria-expanded="false" aria-label="Toggle navigation">
              <span class="p-2">
                <i class="ti ti-dots fs-7"></i>
              </span>
            </a>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
              <div class="d-flex align-items-center justify-content-between px-0 px-xl-8">
                <ul class="navbar-nav flex-row mx-auto ms-lg-auto align-items-center justify-content-center">
                  <li class="nav-item dropdown">
                    <a href="javascript:void(0)"
                      class="nav-link nav-icon-hover-bg rounded-circle d-flex d-lg-none align-items-center justify-content-center"
                      type="button" data-bs-toggle="offcanvas" data-bs-target="#mobilenavbar"
                      aria-controls="offcanvasWithBothOptions">
                      <iconify-icon icon="solar:sort-line-duotone" class="fs-6"></iconify-icon>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link nav-icon-hover-bg rounded-circle moon dark-layout" href="javascript:void(0)">
                      <iconify-icon icon="solar:moon-line-duotone" class="moon fs-6"></iconify-icon>
                    </a>
                    <a class="nav-link nav-icon-hover-bg rounded-circle sun light-layout" href="javascript:void(0)"
                      style="display: none">
                      <iconify-icon icon="solar:sun-2-line-duotone" class="sun fs-6"></iconify-icon>
                    </a>
                  </li>
                  <li class="nav-item d-block d-xl-none">
                    <a class="nav-link nav-icon-hover-bg rounded-circle" href="javascript:void(0)"
                      data-bs-toggle="modal" data-bs-target="#exampleModal">
                      <iconify-icon icon="solar:magnifer-line-duotone" class="fs-6"></iconify-icon>
                    </a>
                  </li>

                  <!-- ------------------------------- -->
                  <!-- start notification Dropdown -->
                  <!-- ------------------------------- -->
                  <li class="nav-item dropdown nav-icon-hover-bg rounded-circle">
                    <a class="nav-link position-relative notification-icon" href="javascript:void(0)"
                      id="notificationDropdownHorizontal" aria-expanded="false">
                      <iconify-icon icon="solar:bell-bing-line-duotone" class="fs-6"></iconify-icon>
                      <?php if ($new_notification_count > 0): ?>
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
                            <div class="notification-item d-flex align-items-center px-7 py-3 border-bottom">
                              <span class="flex-shrink-0 bg-<?php
                              // Determine the background based on Action type
                              // Customize as needed
                              switch ($notification['Action']) {
                                case 'Launch Admin':
                                  echo 'danger-subtle';
                                  break;
                                case 'Event today':
                                  echo 'primary-subtle';
                                  break;
                                case 'Settings':
                                  echo 'secondary-subtle';
                                  break;
                                default:
                                  echo 'secondary-subtle';
                              }
                              ?> rounded-circle round d-flex align-items-center justify-content-center fs-6 text-<?php
                               switch ($notification['Action']) {
                                 case 'Launch Admin':
                                   echo 'danger';
                                   break;
                                 case 'Event today':
                                   echo 'primary';
                                   break;
                                 case 'Settings':
                                   echo 'secondary';
                                   break;
                                 default:
                                   echo 'secondary';
                               }
                               ?>">
                                <?php
                                // Remove the icon as per user request
                                // If you still want icons based on Action, keep this switch case
                                // Otherwise, remove this block
                                /*
                                switch ($notification['Action']) {
                                    case 'Launch Admin':
                                        echo '<iconify-icon icon="solar:widget-3-line-duotone"></iconify-icon>';
                                        break;
                                    case 'Event today':
                                        echo '<iconify-icon icon="solar:calendar-line-duotone"></iconify-icon>';
                                        break;
                                    case 'Settings':
                                        echo '<iconify-icon icon="solar:settings-line-duotone"></iconify-icon>';
                                        break;
                                    default:
                                        echo '<iconify-icon icon="solar:activity-line-duotone"></iconify-icon>';
                                }
                                */
                                ?>
                              </span>
                              <div class="w-75 d-inline-block ms-3">
                                <div class="d-flex align-items-center justify-content-between">
                                  <h6 class="mb-1 fw-semibold">
                                    <?php echo htmlspecialchars($notification['Username'], ENT_QUOTES, 'UTF-8'); ?></h6>
                                  <span
                                    class="d-block fs-6 notification-time"><?php echo date("h:i A", strtotime($notification['TimeStamp'])); ?></span>
                                </div>
                                <span class="d-block text-truncate notification-action fs-11">
                                  <?php echo htmlspecialchars($notification['Action'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                              </div>
                              <span class="delete-notification" data-logid="<?php echo $notification['LogID']; ?>">
                                <iconify-icon icon="mdi:close-circle-outline" class="fs-6"></iconify-icon>
                              </span>
                            </div>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <div class="py-6 px-7 d-flex align-items-center justify-content-center">
                            <span class="text-muted">No notifications</span>
                          </div>
                        <?php endif; ?>
                      </div>
                      <?php if ($all_notifications_count > 0): ?>
                        <div class="py-3 px-7 d-flex justify-content-between">
                          <span class="clear-all">Clear All</span>
                        </div>
                        <div class="py-3 px-7 mb-1">
                          <a href="../officer/activity-logs.php" class="btn btn-primary w-100">See All Notifications</a>
                        </div>
                      <?php else: ?>
                        <div class="py-6 px-7 mb-1">
                          <a href="../officer/activity-logs.php" class="btn btn-primary w-100">See All Notifications</a>
                        </div>
                      <?php endif; ?>
                    </div>
                  </li>
                  <!-- ------------------------------- -->
                  <!-- end notification Dropdown -->
                  <!-- ------------------------------- -->

                  <!-- start profile Dropdown -->
                  <li class="nav-item dropdown">
                    <a class="nav-link" href="javascript:void(0)" id="drop1" aria-expanded="false">
                      <div class="d-flex align-items-center gap-2 lh-base">
                        <!-- Placeholder image; update dynamically if image data becomes available -->
                        <img src="<?php echo $userImageSrc; ?>" class="rounded-circle" width="35" height="35"
                          alt="matdash-img" />
                        <iconify-icon icon="solar:alt-arrow-down-bold" class="fs-2"></iconify-icon>
                      </div>
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </nav>
        </div>
      </header>


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
                var res = JSON.parse(response);
                if (res.status === 'success') {
                  // Hide the badge
                  $(dropdownId).find('.notification-badge').remove();
                }
              }
            });
          }

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

          // Handle delete notification
          $(document).on('click', '.delete-notification', function (e) {
            e.stopPropagation(); // Prevent the dropdown from closing
            var logid = $(this).data('logid');
            var notificationElement = $(this).closest('.notification-item');

            if (confirm('Are you sure you want to delete this notification?')) {
              $.ajax({
                url: '', // Current page
                type: 'POST',
                data: {
                  action: 'delete_notification',
                  logid: logid
                },
                success: function (response) {
                  var res = JSON.parse(response);
                  if (res.status === 'success') {
                    // Remove the notification from the DOM
                    notificationElement.remove();

                    // Optionally, update the notification count badge
                    var badge = $('#notificationDropdown, #notificationDropdownHorizontal').find('.notification-badge');
                    if (badge.length > 0) {
                      var currentCount = parseInt(badge.text());
                      if (currentCount > 1) {
                        badge.text(currentCount - 1);
                      } else {
                        badge.remove();
                      }
                    }

                    // Optionally, show a message if no notifications are left
                    if ($('.notification-item').length === 0) {
                      $('.message-body').html('<div class="py-6 px-7 d-flex align-items-center justify-content-center"><span class="text-muted">No notifications</span></div>');
                      $('.clear-all').remove();
                    }
                  } else {
                    alert('Failed to delete notification.');
                  }
                }
              });
            }
          });

          // Handle clear all notifications
          $(document).on('click', '.clear-all', function () {
            if (confirm('Are you sure you want to clear all notifications?')) {
              $.ajax({
                url: '', // Current page
                type: 'POST',
                data: {
                  action: 'clear_notifications'
                },
                success: function (response) {
                  var res = JSON.parse(response);
                  if (res.status === 'success') {
                    // Remove all notifications from the DOM
                    $('.notification-item').remove();

                    // Remove the badge
                    $('#notificationDropdown, #notificationDropdownHorizontal').find('.notification-badge').remove();

                    // Show 'No notifications' message
                    $('.message-body').html('<div class="py-6 px-7 d-flex align-items-center justify-content-center"><span class="text-muted">No notifications</span></div>');

                    // Remove the 'Clear All' button
                    $('.clear-all').remove();
                  } else {
                    alert('Failed to clear notifications.');
                  }
                }
              });
            }
          });
        });
      </script>