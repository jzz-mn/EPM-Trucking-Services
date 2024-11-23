<?php
session_start();
include '../includes/db_connection.php';
include '../officer/header.php';
?>

<!-- Page specific CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
  #map {
    height: 600px;
    width: 100%;
    border-radius: 5px;
    z-index: 1;
  }

  .card-body {
    padding: 1rem;
  }

  .leaflet-container {
    height: 100%;
    width: 100%;
  }

  .leaflet-top.leaflet-right {
    display: none !important;
  }
</style>

<div class="body-wrapper">
  <div class="container-fluid">

    <!-- Page Title -->
    <div class="card card-body py-3">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-4 mb-sm-0 card-title">Analytics</h4>
            <nav aria-label="breadcrumb" class="ms-auto">
              <ol class="breadcrumb">
                <li class="breadcrumb-item d-flex align-items-center">
                  <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
                    <i class="ti ti-home fs-6"></i>
                  </a>
                </li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <h5 class="border-bottom py-2 px-4 mb-4">Routes</h5>

    <!-- Route Selection and Map -->
    <div class="row">
      <!-- Control Panel -->
      <div class="col-md-4 mb-4">
        <div class="card bg-secondary-subtle overflow-hidden shadow-none">
          <div class="card-body">
            <h5 class="card-title fw-semibold mb-4">Route Selection</h5>
            <div class="mb-4">
              <label for="routeSelect" class="form-label">Select Delivery Cluster</label>
              <select id="routeSelect" class="form-select">
                <option value="">Select a cluster...</option>
                <?php
                try {
                  $query = "SELECT DISTINCT ClusterID, ClusterCategory, LocationsInCluster 
                                            FROM clusters 
                                            ORDER BY ClusterCategory";

                  $result = mysqli_query($conn, $query);
                  if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                      echo "<option value='" . htmlspecialchars($row['ClusterID']) . "' 
                                                  data-locations='" . htmlspecialchars($row['LocationsInCluster']) . "'>"
                        . htmlspecialchars($row['ClusterCategory']) .
                        "</option>";
                    }
                  }
                } catch (Exception $e) {
                  echo "<option value=''>Error loading clusters</option>";
                  error_log("Error loading clusters: " . $e->getMessage());
                }
                ?>
              </select>
            </div>

            <!-- Route Statistics -->
            <div class="mt-4">
              <h6 class="fw-semibold mb-3">Route Statistics</h6>
              <div class="route-statistics mb-4">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="ti ti-road-sign fs-6 text-primary"></i>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Total Distance</h6>
                                        <div id="totalDistance" class="fs-5">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="ti ti-clock fs-6 text-warning"></i>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Est. Time</h6>
                                        <div id="estimatedTime" class="fs-5">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="ti ti-gas-station fs-6 text-danger"></i>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Fuel Cost</h6>
                                        <div id="fuelCost" class="fs-5">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="ti ti-traffic-lights fs-6 text-success"></i>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Traffic</h6>
                                        <div id="trafficLevel" class="fs-5">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Map Section -->
      <div class="col-md-8 mb-4">
        <div class="card bg-warning-subtle overflow-hidden shadow-none h-100">
          <div class="card-body p-0 position-relative">
            <div id="map"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Directions Modal -->
<div class="modal fade" id="directionsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold">Route Directions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <ol id="routeDirectionsList" class="list-group list-group-numbered"></ol>
      </div>
    </div>
  </div>
</div>

<!-- AI Suggestions Modal -->
<div class="modal fade" id="aiSuggestionsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold">Route Optimization</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="starting-point mb-3">
          <h6 class="fw-semibold mb-2">Starting Point</h6>
          <div class="p-3 bg-light rounded">
            <i class="ti ti-map-pin me-2"></i>Bounty Plus, Padre Garcia
          </div>
        </div>
        
        <div class="route-details mb-3">
          <h6 class="fw-semibold mb-2">Route Itinerary</h6>
          <div id="routeStops" class="list-group">
            <!-- Route stops will be inserted here -->
          </div>
        </div>

        <div class="route-summary">
          <h6 class="fw-semibold mb-2">Route Summary</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="p-3 bg-light rounded">
                <h6 class="mb-1">Total Distance</h6>
                <p class="mb-0" id="totalDistance">-</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 bg-light rounded">
                <h6 class="mb-1">Estimated Time</h6>
                <p class="mb-0" id="estimatedTime">-</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 bg-light rounded">
                <h6 class="mb-1">Fuel Cost</h6>
                <p class="mb-0" id="fuelCost">-</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 bg-light rounded">
                <h6 class="mb-1">Traffic Condition</h6>
                <p class="mb-0" id="trafficLevel">-</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="applyAndCloseModal()">
          <i class="ti ti-route me-1"></i>Apply Route
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Core Scripts -->
<script src="../assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/libs/simplebar/dist/simplebar.min.js"></script>

<!-- Map Scripts -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<!-- Your Route Logic -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<script>
let map = null;
let routingControl = null;
const NOMINATIM_BASE_URL = "https://nominatim.openstreetmap.org/search";
const BATANGAS_BOUNDS = {
    south: 13.5,
    north: 14.5,
    west: 120.5,
    east: 121.5
};

const LOCATION_COORDINATES = {
    // Starting Point
    'BOUNTY PLUS': { lat: 13.8781, lng: 121.2117 },
    
    // Pure Gold Locations
    'PG BATANGAS': { lat: 13.7565, lng: 121.0583 },
    'PG LIPA': { lat: 13.9395, lng: 121.1621 },
    'PG BINAN': { lat: 14.3333, lng: 121.0833 },
    'PG GMA': { lat: 14.3000, lng: 121.0000 },
    'PG TAGAYTAY': { lat: 14.1153, lng: 120.9621 },
    'PG IMUS': { lat: 14.2997, lng: 120.9367 },
    
    // City Marts and Shop On
    'CM CAEDO': { lat: 13.7565, lng: 121.0583 },
    'CM SHOP ON': { lat: 13.7565, lng: 121.0583 },
    'CM BAUA': { lat: 13.7565, lng: 121.0583 },
    'SHOP ON CM BAYMALL': { lat: 13.7565, lng: 121.0583 },
    'BAYMALL': { lat: 13.7565, lng: 121.0583 },
    
    // Markets
    'MEAT MARKET BATS': { lat: 13.7565, lng: 121.0583 },
    'MEAT MARKET': { lat: 13.7565, lng: 121.0583 },
    
    // Savory Locations
    'SAV LIPA': { lat: 13.9395, lng: 121.1621 },
    'SAVORY LIPA': { lat: 13.9395, lng: 121.1621 },
    'LIPA OUTLET': { lat: 13.9395, lng: 121.1621 },
    
    // Areas with multiple names
    'GULOD': { lat: 13.7744, lng: 121.0488 },
    'ALANGILAN': { lat: 13.7744, lng: 121.0488 },
    'GULOD ALANGILAN': { lat: 13.7744, lng: 121.0488 },
    
    // Keep your existing locations...
};

// Initialize the map
function initMap() {
    try {
        console.log('Initializing map...');
        map = L.map('map').setView([13.8781, 121.2117], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add marker for Bounty Plus (starting point)
        L.marker([13.8781, 121.2117])
            .addTo(map)
            .bindPopup('Bounty Plus, Padre Garcia')
            .openPopup();

        routingControl = L.Routing.control({
            waypoints: [],
            routeWhileDragging: false,
            show: false,
            addWaypoints: false
        });

        console.log('Map initialized successfully');
    } catch (error) {
        console.error('Error initializing map:', error);
        showError('Failed to initialize map: ' + error.message);
    }
}

// Geocode a location name to coordinates
async function geocodeLocation(locationName) {
    try {
        // Append ", Batangas, Philippines" to improve geocoding accuracy
        const searchQuery = `${locationName}, Batangas, Philippines`;
        const params = new URLSearchParams({
            q: searchQuery,
            format: 'json',
            limit: 1,
            viewbox: `${BATANGAS_BOUNDS.west},${BATANGAS_BOUNDS.north},${BATANGAS_BOUNDS.east},${BATANGAS_BOUNDS.south}`,
            bounded: 1
        });

        const response = await fetch(`${NOMINATIM_BASE_URL}?${params}`);
        const data = await response.json();

        if (data && data.length > 0) {
            return {
                name: locationName,
                lat: parseFloat(data[0].lat),
                lng: parseFloat(data[0].lon)
            };
        }
        throw new Error(`Location not found: ${locationName}`);
    } catch (error) {
        console.error(`Error geocoding ${locationName}:`, error);
        return null;
    }
}

// Process locations from cluster
async function processClusterLocations(locationsStr) {
    // Split the string by commas and clean up each location name
    const locationNames = locationsStr.split(',')
        .map(loc => loc.trim())
        .filter(loc => loc.length > 0);

    // Show loading state
    showLoading('Processing locations...');

    // Geocode all locations
    const locations = [];
    for (const name of locationNames) {
        const location = await geocodeLocation(name);
        if (location) {
            locations.push(location);
        }
    }

    hideLoading();
    return locations;
}

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    initMap();

    // Handle route selection
    const routeSelect = document.getElementById('routeSelect');
    if (routeSelect) {
        routeSelect.addEventListener('change', async function() {
            try {
                // Clear the map completely before processing new route
                clearMap();
                
                if (!this.value) {
                    return; // Exit if no value selected
                }

                const selectedOption = this.options[this.selectedIndex];
                const locationsStr = selectedOption.getAttribute('data-locations');
                
                // Process locations for the new cluster
                const locations = await processClusterLocations(locationsStr);
                
                if (locations.length === 0) {
                    throw new Error('No valid locations found in cluster');
                }

                // Create new route data
                const routeData = {
                    waypoints: [
                        { name: 'Bounty Plus', lat: 13.8781, lng: 121.2117 },
                        ...locations,
                        { name: 'Bounty Plus', lat: 13.8781, lng: 121.2117 }
                    ]
                };

                // Display the new route
                displayOptimalRoute(routeData);
            } catch (error) {
                console.error('Error processing route:', error);
                showError('Failed to process route: ' + error.message);
            }
        });
    }
});

// Loading indicator functions
function showLoading(message) {
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'loadingIndicator';
    loadingDiv.className = 'alert alert-info';
    loadingDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2"></div>
            <div>${message}</div>
        </div>
    `;
    document.querySelector('.route-statistics').appendChild(loadingDiv);
}

function hideLoading() {
    const loadingDiv = document.getElementById('loadingIndicator');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger alert-dismissible fade show';
    errorDiv.innerHTML = `
        <strong>Error:</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    if (container) {
        const existingError = container.querySelector('.alert');
        if (existingError) {
            existingError.remove();
        }
        container.insertBefore(errorDiv, container.firstChild);
    }
  }

  function calculateFuelCost(distance) {
    const fuelPricePerLiter = 65; // Current diesel price
    const fuelEfficiency = 8; // km per liter
    const distanceKm = distance / 1000;
    const fuelNeeded = distanceKm / fuelEfficiency;
    return (fuelNeeded * fuelPricePerLiter).toFixed(2);
  }

  function predictTrafficLevel(duration, distance) {
    const speed = (distance / 1000) / (duration / 3600); // km/h
    if (speed > 50) return 'Low';
    if (speed > 30) return 'Moderate';
    return 'Heavy';
  }

  // Add this check before initializing charts
  function initializeCharts() {
    // Only initialize charts if they exist in the page
    if (document.querySelector("#financialChart")) {
      // Initialize financial chart
    }
    if (document.querySelector("#maintenancePredictionChart")) {
      // Initialize maintenance chart
    }
  }

  function displayOptimalRoute(routeData) {
    try {
        if (!map) {
            throw new Error('Map not initialized');
        }
        
        if (!routeData || !routeData.waypoints || routeData.waypoints.length < 2) {
            throw new Error('Invalid route data');
        }

        // Create waypoints from coordinates
        const waypoints = routeData.waypoints.map(point => 
            L.latLng(point.lat, point.lng)
        );

        // Add routing control
        routingControl = L.Routing.control({
            waypoints: waypoints,
            routeWhileDragging: false,
            show: false,
            addWaypoints: false,
            lineOptions: {
                styles: [{ color: '#0066CC', weight: 4 }]
            }
        }).addTo(map);

        // Add markers for each point
        routeData.waypoints.forEach((point, index) => {
            if (index === 0 || index === routeData.waypoints.length - 1) {
                // Start/End point (Bounty Plus)
                L.marker([point.lat, point.lng], {
                    icon: L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div style="background-color: #4CAF50; color: white; padding: 5px; border-radius: 50%; width: 30px; height: 30px; text-align: center;">BP</div>`,
                        iconSize: [30, 30]
                    })
                }).addTo(map)
                  .bindPopup('Bounty Plus (Start/End Point)');
            } else {
                // Numbered stops
                L.marker([point.lat, point.lng], {
                    icon: L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div style="background-color: #FF5722; color: white; padding: 5px; border-radius: 50%; width: 25px; height: 25px; text-align: center;">${index}</div>`,
                        iconSize: [25, 25]
                    })
                }).addTo(map)
                  .bindPopup(`Stop ${index}: ${point.name}`);
            }
        });

        // Fit map to route bounds
        const bounds = L.latLngBounds(waypoints);
        map.fitBounds(bounds, { padding: [50, 50] });

        // Calculate and display route statistics
        updateRouteStatistics(routeData.waypoints);
        
        // Update itinerary list
        updateItineraryList(routeData.waypoints);

        console.log('Route displayed successfully');
    } catch (error) {
        console.error('Error displaying route:', error);
        showError('Failed to display route: ' + error.message);
    }
  }

  function updateRouteStatistics(waypoints) {
    // Calculate total distance
    let totalDistance = 0;
    for (let i = 0; i < waypoints.length - 1; i++) {
        const start = L.latLng(waypoints[i].lat, waypoints[i].lng);
        const end = L.latLng(waypoints[i + 1].lat, waypoints[i + 1].lng);
        totalDistance += start.distanceTo(end) / 1000; // Convert to kilometers
    }

    // Estimate time (assuming average speed of 40 km/h)
    const averageSpeed = 40; // km/h
    const estimatedHours = totalDistance / averageSpeed;
    
    // Calculate fuel cost (assuming 1L/10km consumption and current fuel price)
    const fuelConsumption = 10; // km/L
    const fuelPrice = 52.00; // PHP per liter
    const fuelCost = (totalDistance / fuelConsumption) * fuelPrice;

    // Estimate traffic level based on time of day
    const hour = new Date().getHours();
    let trafficLevel = "Moderate";
    if (hour >= 7 && hour <= 9 || hour >= 17 && hour <= 19) {
        trafficLevel = "Heavy";
    } else if (hour >= 22 || hour <= 5) {
        trafficLevel = "Light";
    }

    // Update statistics in the modal
    document.getElementById('totalDistance').textContent = `${totalDistance.toFixed(1)} km`;
    document.getElementById('estimatedTime').textContent = formatTime(estimatedHours);
    document.getElementById('fuelCost').textContent = `₱${fuelCost.toFixed(2)}`;
    document.getElementById('trafficLevel').textContent = trafficLevel;
  }

  function updateItineraryList(waypoints) {
    const routeStops = document.getElementById('routeStops');
    routeStops.innerHTML = ''; // Clear existing stops

    waypoints.forEach((point, index) => {
        const estimatedTime = calculateEstimatedTime(index, waypoints);
        const stopElement = document.createElement('div');
        stopElement.className = 'list-group-item';
        
        if (index === 0 || index === waypoints.length - 1) {
            // Starting/Ending point (Bounty Plus)
            stopElement.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="ti ti-building-warehouse text-success me-2"></i>
                        <strong>Bounty Plus</strong>
                        <div class="text-muted small">Starting/Ending Point</div>
                    </div>
                    <div class="text-end">
                        <div class="small">${estimatedTime}</div>
                    </div>
                </div>
            `;
        } else {
            // Route stops
            stopElement.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary me-2">${index}</span>
                        <strong>${point.name}</strong>
                        <div class="text-muted small">Delivery Stop</div>
                    </div>
                    <div class="text-end">
                        <div class="small">${estimatedTime}</div>
                        <div class="text-muted smaller">Est. 15-20 min stop</div>
                    </div>
                </div>
            `;
        }
        routeStops.appendChild(stopElement);
    });
  }

  function calculateEstimatedTime(index, waypoints) {
    // Calculate cumulative time to reach this point
    let totalDistance = 0;
    for (let i = 0; i < index; i++) {
        const start = L.latLng(waypoints[i].lat, waypoints[i].lng);
        const end = L.latLng(waypoints[i + 1].lat, waypoints[i + 1].lng);
        totalDistance += start.distanceTo(end) / 1000;
    }
    
    const averageSpeed = 40; // km/h
    const travelHours = totalDistance / averageSpeed;
    const stopTime = index * 0.25; // 15 minutes per stop
    const totalHours = travelHours + stopTime;

    // Convert to timestamp
    const startTime = new Date();
    startTime.setHours(8, 0, 0); // Assume 8:00 AM start
    const estimatedTime = new Date(startTime.getTime() + (totalHours * 60 * 60 * 1000));
    
    return estimatedTime.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true 
    });
  }

  function formatTime(hours) {
    const totalMinutes = Math.round(hours * 60);
    const hrs = Math.floor(totalMinutes / 60);
    const mins = totalMinutes % 60;
    return `${hrs}h ${mins}m`;
  }

  function applyAndCloseModal() {
    // The route is already displayed on the map from calculateRoute()
    const modal = bootstrap.Modal.getInstance(document.getElementById('aiSuggestionsModal'));
    modal.hide();
  }

  // Initialize DataTable only if the element exists
  $(document).ready(function() {
    if ($.fn.DataTable && $('.datatable').length) {
      $('.datatable').DataTable({
        // Your DataTable options here
      });
    }
  });

  document.addEventListener('DOMContentLoaded', function() {
    const routeSelect = document.getElementById('routeSelect');
    const suggestButton = document.getElementById('suggestButton');

    routeSelect.addEventListener('change', function() {
      // Enable/disable the AI suggestions button based on selection
      suggestButton.disabled = !this.value;

      if (this.value) {
        const selectedOption = this.options[this.selectedIndex];
        const locations = selectedOption.dataset.locations;
        console.log('Selected cluster:', this.value);
        console.log('Locations:', locations);
      }
    });
  });

  // Add a function to clear the map completely
  function clearMap() {
    // Remove routing control
    if (routingControl && map.hasLayer(routingControl)) {
        map.removeControl(routingControl);
        routingControl = null;
    }

    // Remove all layers except the base tile layer
    map.eachLayer((layer) => {
        if (!(layer instanceof L.TileLayer)) {
            map.removeLayer(layer);
        }
    });

    // Reset the map view to default if needed
    map.setView([13.8781, 121.2117], 13);

    // Clear any existing route statistics
    document.getElementById('estimatedTime').textContent = '-';
    document.getElementById('fuelCost').textContent = '-';
    document.getElementById('trafficLevel').textContent = '-';
  }

  // Add AI Suggestions Button
  <button type="button" class="btn btn-primary mb-3" onclick="getAISuggestions()">
      <i class="ti ti-brain me-2"></i>Get AI Route Analysis
  </button>

  function getAISuggestions() {
    const currentRoute = getCurrentRouteData();
    
    // Simulate AI processing
    showLoading('AI analyzing route...');
    
    setTimeout(() => {
        const suggestions = generateRouteSuggestions(currentRoute);
        displayAISuggestions(suggestions);
        hideLoading();
    }, 1500);
  }

  function generateRouteSuggestions(route) {
    // Simulate AI analysis
    const suggestions = {
        optimization: [],
        timing: [],
        cost: []
    };

    // Route order optimization
    if (route.waypoints.length > 3) {
        suggestions.optimization.push(
            "Consider visiting LIPA locations consecutively to minimize backtracking",
            "Group nearby locations like GULOD and ALANGILAN together"
        );
    }

    // Timing suggestions
    const hour = new Date().getHours();
    if (hour >= 7 && hour <= 9) {
        suggestions.timing.push(
            "Heavy morning traffic expected. Consider starting earlier",
            "Take alternative routes through secondary roads"
        );
    }

    // Cost optimization
    suggestions.cost.push(
        "Estimated fuel savings of 15% possible by optimizing stop order",
        "Consider refueling at stations with lower prices along the route"
    );

    return suggestions;
  }

  function displayAISuggestions(suggestions) {
    const modal = new bootstrap.Modal(document.getElementById('aiSuggestionsModal'));
    const modalBody = document.querySelector('#aiSuggestionsModal .modal-body');
    
    let html = `
        <div class="suggestions-container">
            <div class="mb-4">
                <h6 class="fw-semibold"><i class="ti ti-route me-2"></i>Route Optimization</h6>
                <ul class="list-unstyled">
                    ${suggestions.optimization.map(s => `
                        <li class="mb-2">
                            <i class="ti ti-circle-check text-success me-2"></i>${s}
                        </li>
                    `).join('')}
                </ul>
            </div>
            <div class="mb-4">
                <h6 class="fw-semibold"><i class="ti ti-clock me-2"></i>Timing Suggestions</h6>
                <ul class="list-unstyled">
                    ${suggestions.timing.map(s => `
                        <li class="mb-2">
                            <i class="ti ti-circle-check text-warning me-2"></i>${s}
                        </li>
                    `).join('')}
                </ul>
            </div>
            <div class="mb-4">
                <h6 class="fw-semibold"><i class="ti ti-coin me-2"></i>Cost Optimization</h6>
                <ul class="list-unstyled">
                    ${suggestions.cost.map(s => `
                        <li class="mb-2">
                            <i class="ti ti-circle-check text-info me-2"></i>${s}
                        </li>
                    `).join('')}
                </ul>
            </div>
        </div>
    `;
    
    modalBody.innerHTML = html;
    modal.show();
  }

  function getCurrentRouteData() {
    // Get current route data from the map
    const waypoints = routingControl ? routingControl.getWaypoints() : [];
    return {
        waypoints: waypoints.map(wp => ({
            lat: wp.latLng.lat,
            lng: wp.latLng.lng,
            name: wp.name || 'Unknown'
        }))
    };
  }

  // Add your OpenAI API key configuration
  const OPENAI_API_KEY = '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';

  async function getAISuggestions() {
    try {
        showLoading('AI analyzing route...');
        
        const currentRoute = getCurrentRouteData();
        const routeContext = analyzeRouteContext();
        
        // Prepare data for AI analysis
        const prompt = generateAIPrompt(currentRoute, routeContext);
        
        // Call OpenAI API
        const suggestions = await callChatGPT(prompt);
        
        // Display results
        displayAISuggestions(suggestions);
        
        hideLoading();
    } catch (error) {
        console.error('Error getting AI suggestions:', error);
        showError('Failed to generate AI suggestions: ' + error.message);
        hideLoading();
    }
  }

  function analyzeRouteContext() {
    return {
        time: new Date(),
        weather: getCurrentWeather(),
        trafficConditions: getTrafficConditions(),
        vehicleStatus: getVehicleStatus(),
        deliveryConstraints: getDeliveryConstraints()
    };
  }

  async function callChatGPT(prompt) {
    try {
        const response = await fetch('https://api.openai.com/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${OPENAI_API_KEY}`
            },
            body: JSON.stringify({
                model: "gpt-3.5-turbo",
                messages: [{
                    role: "system",
                    content: "You are a logistics AI assistant specialized in route optimization and delivery planning."
                }, {
                    role: "user",
                    content: prompt
                }],
                temperature: 0.7,
                max_tokens: 500
            })
        });

        const data = await response.json();
        return parseAIResponse(data.choices[0].message.content);
    } catch (error) {
        console.error('Error calling ChatGPT:', error);
        throw new Error('Failed to get AI suggestions');
    }
  }

  function generateAIPrompt(route, context) {
    const { time, weather, trafficConditions, vehicleStatus, deliveryConstraints } = context;
    
    return `Analyze this delivery route and provide optimization suggestions:

Route Details:
- Starting Point: Bounty Plus, Padre Garcia
- Number of Stops: ${route.waypoints.length - 2}
- Total Distance: ${calculateTotalDistance(route.waypoints)} km
- Estimated Duration: ${calculateEstimatedDuration(route.waypoints)} hours

Context:
- Time: ${time.toLocaleTimeString()}
- Weather: ${weather}
- Traffic Conditions: ${trafficConditions}
- Vehicle Status: ${vehicleStatus}
- Delivery Constraints: ${deliveryConstraints}

Stops:
${route.waypoints.slice(1, -1).map((wp, i) => 
    `${i + 1}. ${wp.name}`
).join('\n')}

Please provide:
1. Route optimization suggestions
2. Timing recommendations
3. Cost-saving opportunities
4. Risk mitigation strategies
5. Alternative routes if available`;
  }

  function parseAIResponse(response) {
    // Parse and structure the AI response
    const sections = response.split('\n\n');
    return {
        optimization: extractSuggestions(sections, 'Route optimization'),
        timing: extractSuggestions(sections, 'Timing'),
        costSaving: extractSuggestions(sections, 'Cost-saving'),
        risks: extractSuggestions(sections, 'Risk'),
        alternatives: extractSuggestions(sections, 'Alternative')
    };
  }

  function displayAISuggestions(suggestions) {
    const modal = new bootstrap.Modal(document.getElementById('aiSuggestionsModal'));
    const modalBody = document.querySelector('#aiSuggestionsModal .modal-body');
    
    let html = `
        <div class="ai-suggestions">
            <div class="alert alert-info mb-4">
                <i class="ti ti-brain me-2"></i>
                AI Analysis Results
            </div>
            
            <div class="suggestion-section mb-4">
                <h6 class="fw-semibold">
                    <i class="ti ti-route me-2"></i>Route Optimization
                </h6>
                <ul class="list-group">
                    ${suggestions.optimization.map(s => `
                        <li class="list-group-item">
                            <i class="ti ti-check text-success me-2"></i>${s}
                        </li>
                    `).join('')}
                </ul>
            </div>

            <div class="suggestion-section mb-4">
                <h6 class="fw-semibold">
                    <i class="ti ti-clock me-2"></i>Timing Recommendations
                </h6>
                <ul class="list-group">
                    ${suggestions.timing.map(s => `
                        <li class="list-group-item">
                            <i class="ti ti-check text-warning me-2"></i>${s}
                        </li>
                    `).join('')}
                </ul>
            </div>

            <div class="suggestion-section mb-4">
                <h6 class="fw-semibold">
                    <i class="ti ti-coin me-2"></i>Cost Saving Opportunities
                </h6>
                <ul class="list-group">
                    ${suggestions.costSaving.map(s => `
                        <li class="list-group-item">
                            <i class="ti ti-check text-info me-2"></i>${s}
                        </li>
                    `).join('')}
                </ul>
            </div>

            <div class="suggestion-section mb-4">
                <h6 class="fw-semibold">
                    <i class="ti ti-alert-triangle me-2"></i>Risk Mitigation
                </h6>
                <ul class="list-group">
                    ${suggestions.risks.map(s => `
                        <li class="list-group-item">
                            <i class="ti ti-check text-danger me-2"></i>${s}
                        </li>
                    `).join('')}
                </ul>
            </div>
        </div>
    `;
    
    modalBody.innerHTML = html;
    modal.show();
  }

  // Helper functions for context analysis
  function getCurrentWeather() {
    // Implement weather API call or return mock data
    return "Sunny, 30°C";
  }

  function getTrafficConditions() {
    const hour = new Date().getHours();
    if (hour >= 7 && hour <= 9) return "Heavy morning traffic";
    if (hour >= 17 && hour <= 19) return "Heavy evening traffic";
    return "Normal traffic conditions";
  }

  function getVehicleStatus() {
    // Implement vehicle status check or return mock data
    return "Operational, 75% fuel capacity";
  }

  function getDeliveryConstraints() {
    // Implement delivery constraints check or return mock data
    return "Time-sensitive deliveries: 2, Temperature-controlled items: 1";
  }
</script>

<?php include '../officer/footer.php'; ?>