<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Analytics - Route Optimization</title>
  
  <!-- Bootstrap CSS (Ensure it's the same version as in analytics-finance.php) -->
  <link rel="stylesheet" href="../assets/libs/bootstrap/dist/css/bootstrap.min.css">
  
  <!-- Bootstrap Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
  
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
  
  <!-- Custom Styles -->
  <style>
    #map {
      height: 100%;
      min-height: 500px;
      width: 100%;
      border-radius: 5px;
    }
  </style>
</head>

<body>
  <?php
  session_start();
  include '../officer/header.php';
  include '../includes/db_connection.php';

  $sidebar_path = '../officer/sidebar.php';
  if (file_exists($sidebar_path)) {
      include $sidebar_path;
  } else {
      echo "<!-- Sidebar not found: $sidebar_path -->";
  }
  ?>

  <div class="body-wrapper">
    <div class="container-fluid">
      <div class="card card-body py-3">
        <h4 class="mb-4">Route Optimization System</h4>
        <div class="row">
          <!-- Control Panel -->
          <div class="col-md-4 mb-4">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Route Details</h5>
                <div class="mb-3">
                  <label for="routeSelect" class="form-label">Select Route</label>
                  <select id="routeSelect" class="form-select">
                    <option value="">Select a route...</option>
                    <?php
                    $query = "SELECT RouteID, StartLocation, EndLocation FROM route_optimization ORDER BY StartLocation";
                    $result = mysqli_query($conn, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                      echo "<option value='{$row['RouteID']}'>{$row['StartLocation']} → {$row['EndLocation']}</option>";
                    }
                    ?>
                  </select>
                </div>
                <button class="btn btn-primary w-100" onclick="calculateRoute()">Calculate Route</button>
                <div class="mt-4">
                  <div class="card bg-light">
                    <div class="card-body">
                      <h5 class="card-title">Route Statistics</h5>
                      <p><strong>Distance:</strong> <span id="totalDistance">-</span></p>
                      <p><strong>Est. Time:</strong> <span id="estTime">-</span></p>
                      <p><strong>Est. Fuel:</strong> <span id="estFuel">-</span></p>
                      <p><strong>Est. Cost:</strong> <span id="estCost">-</span></p>
                    </div>
                  </div>
                </div>
                <button class="btn btn-secondary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#directionsModal">View Directions</button>
              </div>
            </div>
          </div>

          <!-- Map Section -->
          <div class="col-md-8 mb-4">
            <div class="card">
              <div class="card-body p-0">
                <div id="map"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Directions Modal -->
  <div class="modal fade" id="directionsModal" tabindex="-1" aria-labelledby="directionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="directionsModalLabel">Route Directions</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <ol id="routeDirectionsList"></ol>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Required Libraries -->
  <!-- Ensure the order matches analytics-finance.php to prevent conflicts -->
  <script src="../assets/js/vendor.min.js"></script>
  <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/libs/simplebar/dist/simplebar.min.js"></script>
  <script src="../assets/js/theme/app.init.js"></script>
  <script src="../assets/js/theme/theme.js"></script>
  <script src="../assets/js/theme/app.min.js"></script>
  <script src="../assets/js/theme/sidebarmenu-default.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  <script src="../assets/libs/owl.carousel/dist/owl.carousel.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

  <!-- Custom Scripts -->
  <script>
    let map, routingControl;

    function initMap() {
      map = L.map("map").setView([14.5995, 120.9842], 7);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap contributors",
      }).addTo(map);
    }

    async function fetchRouteData(routeId) {
      try {
        const response = await fetch(`get_route_data.php?route_id=${routeId}`);
        if (!response.ok) throw new Error("Failed to fetch route data");
        return await response.json();
      } catch (error) {
        console.error("Error fetching route data:", error);
        alert("Failed to fetch route data.");
      }
    }

    function clearMap() {
      if (routingControl) {
        map.removeControl(routingControl);
      }
    }

    async function calculateRoute() {
      const routeId = document.getElementById("routeSelect").value;
      if (!routeId) {
        alert("Please select a route.");
        return;
      }

      try {
        const data = await fetchRouteData(routeId);
        if (!data || data.error) {
          throw new Error(data.error || "No data found for the selected route.");
        }

        const { route, waypoints, directions } = data;

        document.getElementById("totalDistance").textContent = `${route.TotalDistance} km`;
        document.getElementById("estTime").textContent = `${Math.floor(route.EstimatedTime / 60)}h ${route.EstimatedTime % 60}m`;
        document.getElementById("estFuel").textContent = `${route.EstimatedFuel} L`;
        document.getElementById("estCost").textContent = route.TotalCost ? `₱${parseFloat(route.TotalCost).toFixed(2)}` : "-";

        clearMap();

        const waypointCoordinates = waypoints.map((wp) => L.latLng(wp.Latitude, wp.Longitude));
        routingControl = L.Routing.control({
          waypoints: waypointCoordinates,
          routeWhileDragging: false,
          fitSelectedRoutes: true,
          showAlternatives: false,
          createMarker: () => null, // Suppress the markers
        }).addTo(map);

        // Populate directions in the modal
        const directionsList = document.getElementById("routeDirectionsList");
        directionsList.innerHTML = "";
        directions.forEach((step) => {
          const listItem = document.createElement("li");
          listItem.textContent = step;
          directionsList.appendChild(listItem);
        });
      } catch (error) {
        console.error("Error calculating route:", error);
        alert("Error calculating route.");
      }
    }

    document.addEventListener("DOMContentLoaded", () => {
      initMap();
      // Initialize other features if needed
    });
  </script>
</body>

</html>
