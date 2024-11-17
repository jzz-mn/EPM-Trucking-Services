<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
<div class="body-wrapper">
  <div class="container-fluid">
    <?php
    $sidebar_path = '../officer/sidebar.php';
    if (file_exists($sidebar_path)) {
        include $sidebar_path;
    } else {
        echo "<!-- Sidebar not found at $sidebar_path -->";
    }
    ?>
    <div class="card card-body py-3">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-4 mb-sm-0 card-title">Analytics - Route Optimization</h4>
            <nav aria-label="breadcrumb" class="ms-auto">
              <ol class="breadcrumb">
                <li class="breadcrumb-item d-flex align-items-center">
                  <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                  </a>
                </li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>
    <h5 class="border-bottom py-2 px-4 mb-4">Routes</h5>

    <!-- Map and Route Optimization Section -->
    <div class="body-wrapper">
      <div class="container-fluid p-0">
        <div class="row">
          <!-- Route Map Section -->
          <div class="col-lg-12">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Truck Route Map</h5>
                <div class="mb-4">
                  <label for="cluster" class="form-label">Select Cluster</label>
                  <select id="cluster" class="form-select">
                    <option value="" disabled selected>Select a cluster</option>
                    <option value="Padre Garcia, SAN PEDRO, ALAMINOS, UR STO. TOMAS, STO TOMAS, FPIP TANAUAN 2, WM TANAUAN 2, WM TANAUAN SM, CM TANAUAN, Talisay Area">
                      Tanauan-Talisay Cluster
                    </option>
                  </select>
                </div>
                <button class="btn btn-primary mb-3" onclick="showRoute()">Show Optimized Route</button>
                <!-- Map container -->
                <div id="map" style="width: 100%; height: 500px;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Include Leaflet.js and its CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<script>
    let map, routeControl;

    // Initialize the map centered on the Philippines
    function initMap() {
        map = L.map('map').setView([12.8797, 121.7740], 6); // Coordinates for the Philippines

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
    }

    // Function to display the route
    async function showRoute() {
        const clusterSelect = document.getElementById("cluster");
        const locations = clusterSelect.value;

        if (!locations) {
            alert("Please select a cluster.");
            return;
        }

        try {
            const locationNames = locations.split(",").map(loc => loc.trim());
            const waypoints = [];

            // Fetch coordinates for all locations
            for (let location of locationNames) {
                const coords = await getCoordinates(location);
                waypoints.push(L.latLng(coords.lat, coords.lng));
            }

            // Clear existing route
            if (routeControl) {
                map.removeControl(routeControl);
            }

            // Add routing layer to the map
            routeControl = L.Routing.control({
                waypoints: waypoints,
                routeWhileDragging: false,
                show: true,
                router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' })
            }).addTo(map);
        } catch (error) {
            alert("Error displaying route: " + error.message);
        }
    }

    // Function to fetch coordinates for a location using OpenStreetMap's Nominatim API
    async function getCoordinates(location) {
        const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(location)}&format=json`;
        const response = await fetch(url);
        const data = await response.json();

        if (data.length === 0) {
            throw new Error(`Location not found: ${location}`);
        }

        return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
    }

    // Load the map when the page loads
    window.onload = initMap;
</script>

<script src="../assets/js/vendor.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/libs/simplebar/dist/simplebar.min.js"></script>
<script src="../assets/js/theme/app.init.js"></script>
<script src="../assets/js/theme/theme.js"></script>
<script src="../assets/js/theme/app.min.js"></script>
<script src="../assets/js/theme/sidebarmenu-default.js"></script>
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
<script src="../assets/libs/owl.carousel/dist/owl.carousel.min.js"></script>
<script src="../assets/js/apps/productDetail.js"></script>
</body>
</html>
