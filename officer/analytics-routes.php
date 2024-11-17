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
                <li class="breadcrumb-item active" aria-current="page">Route Optimization</li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <h5 class="border-bottom py-2 px-4 mb-4">Routes</h5>

    <div class="row">
      <!-- Map Section -->
      <div class="col-md-6">
        <h5 class="card-title">Truck Route Map</h5>
        <div id="map" style="width: 100%; height: 500px; border: 1px solid #ccc;"></div>
      </div>

      <!-- Route Details Section -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5>Select Cluster</h5>
            <label for="cluster" class="form-label">Cluster</label>
            <select id="cluster" class="form-select">
              <option value="" disabled selected>Select a cluster</option>
              <?php
              $query = "SELECT DISTINCT ClusterCategory, LocationsInCluster FROM clusters";
              $result = mysqli_query($conn, $query);
              while ($row = mysqli_fetch_assoc($result)) {
                  echo "<option value='{$row['LocationsInCluster']}'>{$row['ClusterCategory']}</option>";
              }
              ?>
            </select>
            <button class="btn btn-primary mt-3" onclick="showRoute()">Show Optimized Route</button>
          </div>
        </div>
        <div class="mt-3">
          <h6 class="text-primary">Route Summary</h6>
          <p id="route-summary" class="text-muted fw-bold"></p>
          <h6 class="text-primary">Overview</h6>
          <p id="route-overview" class="text-muted"></p>
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

    // Function to fetch coordinates for a given location
    async function fetchCoordinates(location) {
        const response = await fetch(`fetch_location_coordinates.php?location=${encodeURIComponent(location)}`);
        const data = await response.json();

        if (!data.lat || !data.lng) {
            throw new Error(data.error || `Coordinates not found for: ${location}`);
        }
        return data;
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

            // Fetch coordinates dynamically for all locations in the cluster
            for (const location of locationNames) {
                const coords = await fetchCoordinates(location);
                waypoints.push(`${coords.lng},${coords.lat}`);
            }

            // Construct the route URL
            const routeUrl = `https://router.project-osrm.org/route/v1/driving/${waypoints.join(";")}?overview=full&geometries=geojson`;
            const response = await fetch(routeUrl);
            const data = await response.json();

            if (data.code !== "Ok") {
                throw new Error("Error fetching route data. Ensure all locations have valid coordinates.");
            }

            const route = data.routes[0];
            const summary = `Distance: ${(route.distance / 1000).toFixed(1)} km, Duration: ${(route.duration / 60).toFixed(0)} minutes`;
            document.getElementById('route-summary').textContent = summary;

            const description = `Optimized route includes: ${locationNames.join(", ")}.`;
            document.getElementById('route-overview').textContent = description;

            // Clear existing route
            if (routeControl) {
                map.removeLayer(routeControl);
            }

            // Display the route on the map
            routeControl = L.geoJSON(route.geometry).addTo(map);
            map.fitBounds(L.geoJSON(route.geometry).getBounds());
        } catch (error) {
            alert(`Error displaying route: ${error.message}`);
        }
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
