<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Analytics - Route Optimization</title>

  <!-- Bootstrap CSS -->
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

    .leaflet-top.leaflet-right {
      display: none !important;
      /* Hide the route details in the map view */
    }
  </style>
</head>

<body>
  <?php
  session_start();
  include '../officer/header.php';
  include '../includes/db_connection.php';
  ?>

  <div class="body-wrapper">
    <div class="container-fluid">
      <?php
      // Include sidebar for navigation
      $sidebar_path = '../officer/sidebar.php';
      if (file_exists($sidebar_path)) {
        include $sidebar_path;
      } else {
        echo "<!-- Sidebar not found: $sidebar_path -->";
      }
      ?>

      <div class="card card-body py-3">
        <div class="row align-items-center">
          <div class="col-12">
            <div class="d-sm-flex align-items-center justify-content-between">
              <h4 class="mb-4 mb-sm-0 card-title">Analytics</h4>
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
                  $query = "SELECT ro.RouteID, wg.GroupName 
                            FROM route_optimization ro 
                            JOIN waypoint_groups wg ON ro.WaypointGroupID = wg.WaypointGroupID 
                            ORDER BY wg.GroupName";
                  $result = mysqli_query($conn, $query);
                  while ($row = mysqli_fetch_assoc($result)) {
                    echo "<option value='{$row['RouteID']}'>{$row['GroupName']}</option>";
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

  <!-- Directions Modal -->
  <div class="modal fade" id="directionsModal" tabindex="-1" aria-labelledby="directionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="directionsModalLabel">Route Directions</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="mapModal" style="height: 400px; display: none;"></div>
          <ol id="routeDirectionsList" class="mt-3"></ol>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Required Libraries -->
  <script src="../assets/js/vendor.min.js"></script>
  <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/libs/simplebar/dist/simplebar.min.js"></script>
  <script src="../assets/js/theme/app.init.js"></script>
  <script src="../assets/js/theme/theme.js"></script>
  <script src="../assets/js/theme/app.min.js"></script>
  <script src="../assets/js/theme/sidebarmenu-default.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

  <script>
    let map, routingControl, mapModal, startMarker, endMarker;

    function initMap() {
      map = L.map("map").setView([14.5995, 120.9842], 7);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap contributors",
      }).addTo(map);
    }

    function initMapModal() {
      mapModal = L.map("mapModal").setView([14.5995, 120.9842], 7);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap contributors",
      }).addTo(mapModal);
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
      if (routingControl) map.removeControl(routingControl);
      if (startMarker) map.removeLayer(startMarker);
      if (endMarker) map.removeLayer(endMarker);
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

        const {
          route,
          waypoints
        } = data;

        if (!waypoints || waypoints.length < 2) {
          throw new Error("At least two waypoints are required to calculate a route.");
        }

        // Update route statistics
        document.getElementById("totalDistance").textContent = `${route.TotalDistance.toFixed(2)} km`;
        document.getElementById("estTime").textContent = `${Math.floor(route.EstimatedTime / 60)}h ${route.EstimatedTime % 60}m`;
        document.getElementById("estFuel").textContent = `${route.EstimatedFuel.toFixed(2)} L`;
        document.getElementById("estCost").textContent = route.TotalCost ? `₱${parseFloat(route.TotalCost).toFixed(2)}` : "-";

        // Clear existing map routes and markers
        clearMap();

        // Prepare waypoints for the map
        const waypointCoordinates = waypoints.map(wp => L.latLng(wp.Latitude, wp.Longitude));

        // Add route to map using waypoints
        routingControl = L.Routing.control({
          waypoints: waypointCoordinates,
          routeWhileDragging: false,
          fitSelectedRoutes: true,
          showAlternatives: false,
          lineOptions: {
            styles: [{
              color: "blue",
              weight: 4
            }]
          },
          createMarker: (i, wp) => {
            return L.marker(wp.latLng, {
              title: waypoints[i].LocationName
            });
          }
        }).addTo(map);

        // Populate the directions list (only for start and end points)
        const directionsList = document.getElementById("routeDirectionsList");
        directionsList.innerHTML = "";
        waypoints.forEach((wp, index) => {
          const listItem = document.createElement("li");
          listItem.textContent = `${index + 1}. ${wp.LocationName}`;
          directionsList.appendChild(listItem);
        });
      } catch (error) {
        console.error("Error calculating route:", error);
        alert(error.message || "Error calculating route.");
      }
    }



    document.addEventListener("DOMContentLoaded", () => {
      initMap();
      initMapModal();
    });
  </script>
</body>

</html>