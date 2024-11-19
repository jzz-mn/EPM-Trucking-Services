<?php
// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['UserID'])) {
  // Redirect to login page if not logged in
  header("Location: ../index.php");
  exit();
}

// Fetch current logged-in user's ID
$userID = $_SESSION['UserID'];

// Include database connection
require_once('../includes/db_connection.php');

// Initialize the user image source with a default placeholder
$userImageSrc = '../assets/images/profile/user-1.jpg';

// Fetch user data from the database
if ($stmt = $conn->prepare("SELECT Username, EmailAddress, Role, UserImage FROM useraccounts WHERE UserID = ?")) {
  $stmt->bind_param("i", $userID);
  $stmt->execute();
  $stmt->bind_result($dbUsername, $dbEmail, $dbRole, $userImageData);
  if ($stmt->fetch()) {
    // Use the fetched data
    $username = htmlspecialchars($dbUsername, ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($dbEmail, ENT_QUOTES, 'UTF-8');
    $role = htmlspecialchars($dbRole, ENT_QUOTES, 'UTF-8');
    if (!empty($userImageData)) {
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mimeType = $finfo->buffer($userImageData);
      $userImageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($userImageData);
    }
  }
  $stmt->close();
}

// Close database connection
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Orange_Theme" data-layout="vertical">

<head>
  <!-- Required meta tags -->
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Favicon icon-->
  <link rel="shortcut icon" type="image/png" href="../assetsEPM/logos/epm-logo.png" />

  <!-- Core Css -->
  <link rel="stylesheet" href="../assets/css/styles.css" />

  <title>EPM Trucking Services</title>
</head>

<body class="link-sidebar">

  <!-- Preloader -->
  <div class="preloader">
    <img src="../assetsEPM/logos/epm-logo.png" alt="loader" class="lds-ripple img-fluid" />
  </div>
  <div id="main-wrapper">
    <!-- Sidebar Start -->
    <aside class="left-sidebar with-vertical">
      <!-- Start Vertical Layout Sidebar -->
      <div>
        <div class="brand-logo d-flex align-items-center">
          <a href="../employee/home.php" class="text-nowrap logo-img">
            <img src="../assets/images/logos/epm-logo-no-bg.png" alt="Logo" />
          </a>
        </div>

        <!-- Dashboard -->
        <nav class="sidebar-nav scroll-sidebar" data-simplebar>
          <ul class="sidebar-menu" id="sidebarnav">
            <!-- Home -->
            <li class="nav-small-cap">
              <iconify-icon icon="solar:menu-dots-linear" class="mini-icon"></iconify-icon>
              <span class="hide-menu">Menu</span>
            </li>
            <!-- Dashboard -->
            <li class="sidebar-item">
              <a id="get-url" aria-expanded="false">
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="../employee/home.php" aria-expanded="false">
                <iconify-icon icon="mdi:home-outline"></iconify-icon>
                <span class="hide-menu">Home</span>
              </a>
            </li>

            <li class="sidebar-item">
              <a class="sidebar-link" href="../employee/maintenance.php">
                <iconify-icon icon="mdi:truck-outline"></iconify-icon>
                <span class="hide-menu">Maintenance</span>
              </a>
            </li>

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
              <img src="../assets/images/logos/epm-logo-no-bg.png" alt="matdash-img" />
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
                    <a class="nav-link moon dark-layout nav-icon-hover-bg rounded-circle" href="javascript:void(0)">
                      <iconify-icon icon="solar:moon-line-duotone" class="moon fs-6"></iconify-icon>
                    </a>
                    <a class="nav-link sun light-layout nav-icon-hover-bg rounded-circle" href="javascript:void(0)"
                      style="display: none">
                      <iconify-icon icon="solar:sun-2-line-duotone" class="sun fs-6"></iconify-icon>
                    </a>
                  </li>

                  <!-- Start profile Dropdown -->
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
                              <?php echo $username; ?> <span class="text-success fs-11"><?php echo $role; ?></span>
                            </h5>
                            <p class="mb-0 text-dark">
                              <?php echo $email; ?>
                            </p>
                          </div>
                        </div>
                        <div class="message-body">
                          <a href="../employee/page-account-settings.php" class="p-2 dropdown-item h6 rounded-1"
                            style="transition: color 0.3s;" onmouseover="this.style.color='#FA896B';"
                            onmouseout="this.style.color='';">
                            My Profile
                          </a>
                          <a href="../login/logout.php" class="p-2 dropdown-item h6 rounded-1"
                            style="transition: color 0.3s;" onmouseover="this.style.color='#FA896B';"
                            onmouseout="this.style.color='';">
                            Sign Out
                          </a>
                        </div>
                      </div>
                    </div>
                  </li>
                  <!-- End profile Dropdown -->
                </ul>
              </div>
            </div>
          </nav>
          <!-- End Vertical Layout Header -->

          <script>
            // Load theme preference from localStorage
            document.addEventListener("DOMContentLoaded", function() {
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


        </div>
      </header>