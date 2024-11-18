<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />

<div class="body-wrapper">
  <div class="container-fluid">

    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-4">Route Optimization System</h4>

        <div class="row">
          <!-- Control Panel -->
          <div class="col-md-4">
            <div class="card">
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label">Select Cluster</label>
                  <select id="clusterSelect" class="form-select mb-3">
                    <option value="">Select a cluster...</option>
                    <?php
                    $query = "SELECT DISTINCT ClusterID, ClusterCategory FROM clusters ORDER BY ClusterCategory";
                    $result = mysqli_query($conn, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                      echo "<option value='{$row['ClusterID']}'>{$row['ClusterCategory']}</option>";
                    }
                    ?>
                  </select>

                  <label class="form-label">Select Truck</label>
                  <select id="truckSelect" class="form-select mb-3">
                    <option value="">Select a truck...</option>
                    <?php
                    $query = "SELECT TruckID, PlateNo, TruckBrand FROM trucksinfo";
                    $result = mysqli_query($conn, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                      echo "<option value='{$row['TruckID']}'>{$row['PlateNo']} - {$row['TruckBrand']}</option>";
                    }
                    ?>
                  </select>

                  <button class="btn btn-primary w-100" onclick="calculateRoute()">
                    Calculate Route
                  </button>
                </div>

                <div class="mt-4">
                  <div class="card bg-light">
                    <div class="card-body">
                      <h5 class="card-title">Route Statistics</h5>
                      <div id="routeStats">
                        <p class="mb-2"><i class="bi bi-geo-alt"></i> <strong>Distance:</strong>
                          <span id="totalDistance">-</span>
                        </p>
                        <p class="mb-2"><i class="bi bi-clock"></i> <strong>Est. Time:</strong>
                          <span id="estTime">-</span>
                        </p>
                        <p class="mb-2"><i class="bi bi-fuel-pump"></i> <strong>Est. Fuel:</strong>
                          <span id="estFuel">-</span>
                        </p>
                        <p class="mb-2"><i class="bi bi-currency-dollar"></i> <strong>Est. Cost:</strong>
                          <span id="estCost">-</span>
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Map Section -->
          <div class="col-md-8">
            <div class="card">
              <div class="card-body">
                <div id="map" style="height: 600px;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<script>
  let map, routingControl;
  let markers = [];
  const fuelConsumptionRate = 0.12; // Liters per kilometer
  const defaultFuelPrice = 52.00; // Default fuel price in PHP

  // Initialize map
  function initMap() {
    // Center on Philippines
    map = L.map('map').setView([14.5995, 120.9842], 7);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(map);
  }

  // Clear existing markers and routes
  function clearMap() {
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];
    if (routingControl) {
      map.removeControl(routingControl);
    }
  }

  // Fetch all locations for a cluster
  async function fetchClusterLocations(clusterId) {
    const response = await fetch(`get_cluster_coordinates.php?cluster_id=${clusterId}`);
    if (!response.ok) throw new Error('Failed to fetch cluster locations');
    return await response.json();
  }

  // Save the calculated route
  async function saveRoute(data) {
    const response = await fetch('save_route.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });
    if (!response.ok) throw new Error('Failed to save route');
    return await response.json();
  }

  // Calculate route
  async function calculateRoute() {
    const clusterId = document.getElementById('clusterSelect').value;
    const truckId = document.getElementById('truckSelect').value;

    if (!clusterId || !truckId) {
      alert('Please select both a cluster and a truck');
      return;
    }

    try {
      // Fetch cluster locations
      const locations = await fetchClusterLocations(clusterId);
      if (!locations.length) throw new Error('No locations found in this cluster');

      clearMap();

      // Add markers for each location
      locations.forEach(location => {
        const marker = L.marker([location.Latitude, location.Longitude])
          .bindPopup(location.LocationName)
          .addTo(map);
        markers.push(marker);
      });

      // Create waypoints for routing
      const waypoints = locations.map(loc => L.latLng(loc.Latitude, loc.Longitude));

      // Calculate route
      if (routingControl) {
        map.removeControl(routingControl);
      }

      routingControl = L.Routing.control({
        waypoints: waypoints,
        routeWhileDragging: false,
        show: false,
      }).addTo(map);

      routingControl.on('routesfound', async function (e) {
        const route = e.routes[0];
        const distance = route.summary.totalDistance / 1000; // Convert to km
        const time = route.summary.totalTime;

        // Calculate estimates
        const fuelUsed = distance * fuelConsumptionRate;
        const cost = fuelUsed * defaultFuelPrice;

        // Update statistics display
        document.getElementById('totalDistance').textContent = `${distance.toFixed(2)} km`;
        document.getElementById('estTime').textContent = formatTime(time);
        document.getElementById('estFuel').textContent = `${fuelUsed.toFixed(2)} L`;
        document.getElementById('estCost').textContent = `₱${cost.toFixed(2)}`;

        // Save route data
        await saveRoute({
          clusterId,
          truckId,
          distance,
          time,
          fuel: fuelUsed,
          cost,
          fuelPrice: defaultFuelPrice,
          waypoints: locations,
        });
      });

      // Fit map to show all markers
      const group = L.featureGroup(markers);
      map.fitBounds(group.getBounds().pad(0.1));
    } catch (error) {
      console.error(error);
      alert(error.message);
    }
  }

  // Format time from seconds to hours and minutes
  function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${hours}h ${minutes}m`;
  }

  // Initialize map on page load
  window.onload = initMap;
</script>

<?php include '../officer/footer.php'; ?>

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
