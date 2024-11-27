<?php
$allowedRoles = ['SuperAdmin', 'Officer'];

// Include the authentication script
require_once '../includes/auth.php';

include '../includes/db_connection.php';
include '../includes/config.php';

// Fetch clusters from database
$clusters_query = "SELECT DISTINCT ClusterCategory FROM clusters ORDER BY ClusterCategory";
$clusters_result = mysqli_query($conn, $clusters_query);
$clusters = [];
while ($row = mysqli_fetch_assoc($clusters_result)) {
    $clusters[] = $row;
}

include 'header.php';
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<div class="body-wrapper">
    <div class="container-fluid">
        <!-- Page Title -->
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-space-between">
                        <h4 class="mb-4 mb-sm-0 card-title">Analytics</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="breadcrumb-item" aria-current="page">
                                    <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                        Routes
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="border-bottom py-2 px-4 mb-4">Routes</h5>

        <!-- Summary Cards Row -->
        <div class="row mb-4">
            <!-- Distance Card -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="card-title mb-0">Total Distance</h6>
                            <div class="accuracy-badge">
                                <span class="badge bg-success" id="distance-badge">Loading...</span>
                            </div>
                        </div>
                        <h4 class="mb-0" id="total-distance">0 km</h4>
                        <small class="text-muted">Estimated Route Length</small>
                    </div>
                </div>
            </div>

            <!-- Time Card -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="card-title mb-0">Estimated Time</h6>
                            <div class="accuracy-badge">
                                <span class="badge bg-primary" id="time-badge">Loading...</span>
                            </div>
                        </div>
                        <h4 class="mb-0" id="total-time">0 hrs</h4>
                        <small class="text-muted">Total Travel Duration</small>
                    </div>
                </div>
            </div>

            <!-- Fuel Cost Card -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="card-title mb-0">Fuel Cost</h6>
                            <div class="accuracy-badge">
                                <span class="badge bg-warning" id="fuel-badge">Loading...</span>
                            </div>
                        </div>
                        <h4 class="mb-0" id="total-fuel">₱0.00</h4>
                        <small class="text-muted">Estimated Fuel Expense</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map and Delivery Sequence Section -->
        <div class="row g-3">
            <!-- Map Section -->
            <div class="col-12 col-lg-8">
                <div class="card h-100">
                    <div class="card-body">
                        <!-- Controls for Map -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <select id="routeSelect" class="form-select w-50">
                                <option value="">Select Delivery Cluster</option>
                                <?php foreach ($clusters as $cluster): ?>
                                    <option value="<?php echo htmlspecialchars($cluster['ClusterCategory']); ?>">
                                        <?php echo htmlspecialchars($cluster['ClusterCategory']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button id="getAISuggestions" class="btn btn-success btn-sm d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 shadow-sm" disabled>
                                <i class="ti ti-map-check fs-5"></i>
                                <span class="fw-medium">Confirm Route</span>
                            </button>
                        </div>

                        <!-- Map Container -->
                        <div id="map" class="w-100" style="height: 500px; border-radius: 10px; position:sticky">
                            <!-- Leaflet map will be rendered here -->
                        </div>
                    </div>
                </div>
            </div>
            <!-- Route Details -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <h5 class="mb-0">Delivery Sequence</h5>
                                <span class="badge bg-primary rounded-pill">15 Stops</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <button id="aiTipsButton" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2 px-3 py-2" onclick="showRouteTips()">
                                <i class="ti ti-robot fs-5"></i>
                                <span class="fw-medium">Get AI Route Tips</span>
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <!-- Sequence List -->
                        <div class="sequence-list bg-white">
                            <!-- Starting Point -->
                            <div class="sequence-item">
                                <div class="sequence-marker start">
                                    <i class="ti ti-flag"></i>
                                </div>
                                <div class="sequence-content">
                                    <span class="location-name">Bounty Plus</span>
                                    <span class="badge bg-light text-success">Start</span>
                                </div>
                            </div>

                            <!-- Delivery Points -->
                            <div id="sequencePoints">
                                <!-- Points will be populated dynamically -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tips Modal -->
<div class="modal fade" id="routeTipsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-route me-2"></i>Route Optimization Tips
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="routeTipsContent"></div>
        </div>
    </div>
</div>

<!-- Add Loading Modal for AI Tips -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <h5 class="mb-2">Generating AI Tips...</h5>
                <p class="text-muted mb-0">Please wait while we analyze your route</p>
            </div>
        </div>
    </div>
</div>

<script>
    let map;
    let routingControl;

    const LOCATION_COORDINATES = {
        'BOUNTY PLUS': [13.8781, 121.2117],
        // Add your other location coordinates here
    };

    document.addEventListener('DOMContentLoaded', function() {
        initializeMap();

        const routeSelect = document.getElementById('routeSelect');
        const aiSuggestBtn = document.getElementById('getAISuggestions');

        if (routeSelect && aiSuggestBtn) {
            // Handle route selection change
            routeSelect.addEventListener('change', function() {
                aiSuggestBtn.disabled = !this.value;
                if (this.value) {
                    // Clear previous route if any
                    if (routingControl) {
                        map.removeControl(routingControl);
                    }
                }
            });

            // Handle AI Suggestions button click
            aiSuggestBtn.addEventListener('click', async function() {
                const selectedCluster = routeSelect.value;
                if (!selectedCluster) return;

                try {
                    // Show loading state while maintaining button style
                    aiSuggestBtn.disabled = true;
                    const originalContent = aiSuggestBtn.innerHTML;
                    aiSuggestBtn.innerHTML = `
                        <i class="ti ti-loader animate-spin fs-5"></i>
                        <span class="fw-medium">Confirming...</span>
                    `;

                    const optimizationData = await getRouteOptimization(selectedCluster);

                    if (optimizationData && optimizationData.choices && optimizationData.choices[0]) {
                        const suggestions = optimizationData.choices[0].message.content;
                        updateUIWithSuggestions(suggestions);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showError('Failed to confirm route: ' + error.message);
                } finally {
                    // Reset button to original state while maintaining style
                    aiSuggestBtn.disabled = false;
                    aiSuggestBtn.innerHTML = `
                        <i class="ti ti-map-check fs-5"></i>
                        <span class="fw-medium">Confirm Route</span>
                    `;
                }
            });
        }
    });

    function updateUIWithSuggestions(data) {
        try {
            // Update metrics cards
            document.getElementById('total-distance').textContent = data.route.metrics.total_distance;
            document.getElementById('total-time').textContent = data.route.metrics.total_time;
            document.getElementById('total-fuel').textContent = data.route.metrics.fuel_cost;

            // Update badges
            document.getElementById('distance-badge').textContent = 'Optimized';
            document.getElementById('time-badge').textContent = 'Estimated';
            document.getElementById('fuel-badge').textContent = 'Calculated';

            // Update route details
            const routeStops = document.getElementById('routeStops');
            routeStops.innerHTML = `
            <div class="route-details p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="card-title mb-0">Delivery Sequence</h6>
                        <span class="badge bg-primary">${data.route.waypoints.length} Stops</span>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" onclick="showRouteTips()">
                        <i class="ti ti-bulb me-1"></i>View Tips
                    </button>
                </div>
                
                <div class="route-sequence">
                    <!-- Starting Point -->
                    <div class="route-point start-point">
                        <div class="point-marker start">S</div>
                        <div class="point-details">
                            <strong>${data.route.start_point.name}</strong>
                            <span class="badge bg-success">Starting Point</span>
                        </div>
                    </div>

                    <!-- Waypoints -->
                    ${data.route.waypoints.map((stop, index) => `
                        <div class="route-point">
                            <div class="point-marker">${index + 1}</div>
                            <div class="point-details">
                                <strong>${stop.name}</strong>
                            </div>
                        </div>
                    `).join('')}

                    <!-- End Point -->
                    <div class="route-point end-point">
                        <div class="point-marker end">E</div>
                        <div class="point-details">
                            <strong>${data.route.start_point.name}</strong>
                            <span class="badge bg-info">Return Point</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

            // Update map with waypoints
            updateMapRoute(data.route);
        } catch (error) {
            console.error('Error updating UI:', error);
            showError('Failed to update display with route details');
        }
    }

    function showError(message) {
        // Create and show error alert
        const errorAlert = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
        document.querySelector('.container-fluid').insertAdjacentHTML('afterbegin', errorAlert);
    }

    function initializeMap() {
        map = L.map('map').setView([13.8781, 121.2117], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add Bounty Plus marker
        addStartingPointMarker();
    }

    function addStartingPointMarker() {
        const bountyMarker = L.marker(LOCATION_COORDINATES['BOUNTY PLUS'], {
            icon: L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background-color: #4CAF50; color: white; padding: 5px; border-radius: 50%; width: 30px; height: 30px; text-align: center;">BP</div>`,
                iconSize: [30, 30]
            })
        }).addTo(map);
        bountyMarker.bindPopup('Bounty Plus (Start/End Point)');
    }

    async function getRouteOptimization(clusterCategory) {
        try {
            const response = await fetch(`get_cluster_details.php?category=${encodeURIComponent(clusterCategory)}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            // Update metrics cards
            if (data.route && data.route.metrics) {
                document.getElementById('total-distance').textContent = data.route.metrics.total_distance;
                document.getElementById('total-time').textContent = data.route.metrics.total_time;
                document.getElementById('total-fuel').textContent = data.route.metrics.fuel_cost;

                // Update badges
                document.getElementById('distance-badge').textContent = 'Optimized';
                document.getElementById('time-badge').textContent = 'Estimated';
                document.getElementById('fuel-badge').textContent = 'Calculated';
            }

            // Update UI with route data
            updateUIWithRouteData(data);

            // Update map with waypoints
            updateMapWithWaypoints(data.route);

            return data;
        } catch (error) {
            console.error('Error:', error);
            showError('Failed to get route details: ' + error.message);
            throw error;
        }
    }

    // Add this new function to update the map with the route
    function updateMapRoute(routeData) {
        // Clear existing route
        if (routingControl) {
            map.removeControl(routingControl);
        }

        const waypoints = [
            L.latLng(LOCATION_COORDINATES['BOUNTY PLUS']),
            ...routeData.waypoints.map(wp => L.latLng(wp.coordinates.split(','))),
            L.latLng(LOCATION_COORDINATES['BOUNTY PLUS'])
        ];

        routingControl = L.Routing.control({
            waypoints: waypoints,
            routeWhileDragging: false,
            lineOptions: {
                styles: [{
                    color: '#4CAF50',
                    weight: 6
                }]
            },
            createMarker: function(i, wp, n) {
                return L.marker(wp.latLng, {
                    icon: L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div style="background-color: #4CAF50; color: white; padding: 5px; border-radius: 50%; width: 30px; height: 30px; text-align: center;">${i + 1}</div>`,
                        iconSize: [30, 30]
                    })
                });
            },
            // Hide detailed instructions
            show: false,
            showAlternatives: false,
            formatter: function() {
                return '';
            }
        }).addTo(map);

        // Fit map to show all waypoints
        const bounds = L.latLngBounds(waypoints);
        map.fitBounds(bounds, {
            padding: [50, 50]
        });
    }

    function updateUIWithRouteData(data) {
        try {
            const sequencePoints = document.getElementById('sequencePoints');
            let html = '';

            // Add waypoints
            if (data.route && data.route.waypoints) {
                data.route.waypoints.forEach((waypoint, index) => {
                    html += `
                    <div class="sequence-item">
                        <div class="sequence-number">
                            ${index + 1}
                        </div>
                        <div class="sequence-content">
                            <span class="location-name">${waypoint.name}</span>
                            <span class="badge bg-light text-primary">Stop ${index + 1}</span>
                        </div>
                    </div>
                `;
                });
            }

            // Add end point only if we have waypoints
            if (data.route && data.route.waypoints && data.route.waypoints.length > 0) {
                html += `
                <div class="sequence-item">
                    <div class="sequence-marker end">
                        <i class="ti ti-flag-filled"></i>
                    </div>
                    <div class="sequence-content">
                        <span class="location-name">Bounty Plus</span>
                        <span class="badge bg-light text-info">End</span>
                    </div>
                </div>
            `;
            }

            sequencePoints.innerHTML = html;
        } catch (error) {
            console.error('Error updating sequence:', error);
            showError('Failed to update delivery sequence');
        }
    }

    function updateMapWithWaypoints(routeData) {
        try {
            if (window.routingControl) {
                map.removeControl(window.routingControl);
            }

            const waypoints = [
                L.latLng(routeData.start_point.coordinates.split(',')),
                ...routeData.waypoints.map(wp => L.latLng(wp.coordinates.split(','))),
                L.latLng(routeData.start_point.coordinates.split(','))
            ];

            window.routingControl = L.Routing.control({
                waypoints: waypoints,
                routeWhileDragging: false,
                lineOptions: {
                    styles: [{
                        color: '#4CAF50',
                        weight: 6
                    }]
                },
                createMarker: function(i, wp, n) {
                    const isStart = i === 0;
                    const isEnd = i === n - 1;
                    const label = isStart ? 'S' : isEnd ? 'E' : i;
                    return L.marker(wp.latLng, {
                        icon: L.divIcon({
                            className: 'custom-div-icon',
                            html: `<div class="marker-icon ${isStart || isEnd ? 'marker-endpoint' : ''}">${label}</div>`,
                            iconSize: [30, 30]
                        })
                    });
                },
                show: false,
                showAlternatives: false,
                fitSelectedRoutes: true,
                formatter: function() {
                    return '';
                }
            }).addTo(map);

            const bounds = L.latLngBounds(waypoints);
            map.fitBounds(bounds, {
                padding: [50, 50]
            });
        } catch (error) {
            console.error('Error updating map:', error);
            showError('Failed to update map with route');
        }
    }

    // Add this new function for showing general route tips
    async function showRouteTips() {
        const tipsButton = document.getElementById('aiTipsButton');
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        
        try {
            const routeSelect = document.getElementById('routeSelect');
            const selectedCluster = routeSelect.value;
            
            if (!selectedCluster) {
                throw new Error('Please select a cluster first');
            }

            // Show loading modal
            loadingModal.show();
            
            // Disable button and show loading state
            tipsButton.disabled = true;
            tipsButton.innerHTML = `
                <i class="ti ti-loader animate-spin"></i>
                <span>Generating Tips...</span>
            `;

            const response = await fetch('get_route_tips.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ cluster: selectedCluster })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Failed to get route tips');
            }

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to process route tips');
            }

            // Hide loading modal
            loadingModal.hide();

            // Show tips in modal
            const tipsHtml = data.tips.map((tip, index) => `
                <div class="tip-card d-flex align-items-start gap-3 mb-3">
                    <div class="tip-number">${index + 1}</div>
                    <div class="tip-content">
                        <h6 class="mb-1">Tip ${index + 1}</h6>
                        <p class="mb-0">${tip}</p>
                    </div>
                </div>
            `).join('');

            document.getElementById('routeTipsContent').innerHTML = tipsHtml;
            new bootstrap.Modal(document.getElementById('routeTipsModal')).show();

        } catch (error) {
            console.error('Error showing tips:', error);
            alert(`Could not load route tips: ${error.message}`);
        } finally {
            // Hide loading modal if still shown
            loadingModal.hide();
            
            // Reset button state
            tipsButton.disabled = false;
            tipsButton.innerHTML = `
                <i class="ti ti-robot"></i>
                <span>Get AI Route Tips</span>
            `;
        }
    }
</script>

<style>
    .route-details {
        padding: 1rem;
    }

    .route-item {
        padding: 0.8rem;
        border-bottom: 1px solid #eee;
        margin-bottom: 0.5rem;
    }

    .route-number .badge {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .route-content {
        font-size: 0.95rem;
        line-height: 1.4;
    }

    .optimization-notes {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
    }

    .note-item {
        font-size: 0.9rem;
        color: #666;
    }

    .route-section {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .route-timeline {
        position: relative;
        padding-left: 40px;
    }

    .timeline-item {
        position: relative;
        padding: 1rem 0;
        border-left: 2px solid #e9ecef;
    }

    .timeline-marker {
        position: absolute;
        left: -20px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #4CAF50;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        z-index: 1;
    }

    .timeline-marker.start,
    .timeline-marker.end {
        background: #2196F3;
    }

    .timeline-content {
        background: white;
        padding: 1rem;
        border-radius: 8px;
        margin-left: 1rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .ai-suggestions {
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #4CAF50;
    }

    .marker-icon {
        background-color: #4CAF50;
        color: white;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .marker-endpoint {
        background-color: #2196F3;
    }

    .tip-item {
        padding: 8px;
        border-radius: 6px;
        background: white;
        margin-bottom: 8px;
        font-size: 0.9rem;
    }

    .tip-item:hover {
        background: #f8f9fa;
    }

    .tips-content {
        max-height: 300px;
        overflow-y: auto;
    }

    .btn-light {
        border: 1px solid #dee2e6;
        background: white;
    }

    .btn-light:hover {
        background: #f8f9fa;
    }

    .route-sequence {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1rem;
        max-height: 500px;
        overflow-y: auto;
    }

    .route-point {
        display: flex;
        align-items: center;
        padding: 0.5rem;
        border-bottom: 1px solid #eee;
    }

    .point-marker {
        background-color: #4CAF50;
        color: white;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 0.5rem;
    }

    .point-details {
        font-size: 0.9rem;
        line-height: 1.4;
    }

    .point-marker.start,
    .point-marker.end {
        background-color: #2196F3;
    }

    .point-marker.start {
        background-color: #4CAF50;
    }

    .point-marker.end {
        background-color: #2196F3;
    }

    .tip-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        border-left: 4px solid #4CAF50;
        transition: transform 0.2s;
    }

    .tip-card:hover {
        transform: translateX(5px);
    }

    .tip-number {
        background: #4CAF50;
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        flex-shrink: 0;
    }

    .tip-content {
        flex: 1;
    }

    .tip-content h6 {
        color: #2196F3;
        font-weight: 600;
    }

    .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }

    .modal-body::-webkit-scrollbar {
        width: 6px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: #4CAF50;
        border-radius: 3px;
    }

    .route-sequence-container {
        max-height: 500px;
        overflow-y: auto;
        padding: 0.5rem;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .sequence-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem;
        background: white;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        border: 1px solid #e9ecef;
        transition: transform 0.2s;
    }

    .sequence-item:hover {
        transform: translateX(5px);
    }

    .sequence-marker {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #4CAF50;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        flex-shrink: 0;
    }

    .sequence-marker.start {
        background: #2196F3;
    }

    .sequence-marker.end {
        background: #FFA000;
    }

    .sequence-content {
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Custom scrollbar */
    .route-sequence-container::-webkit-scrollbar {
        width: 6px;
    }

    .route-sequence-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .route-sequence-container::-webkit-scrollbar-thumb {
        background: #4CAF50;
        border-radius: 3px;
    }

    /* Updated styles for cleaner UI */
    .delivery-sequence {
        position: relative;
    }

    .sequence-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-left: 2px solid #e9ecef;
        margin-left: 20px;
        position: relative;
    }

    .sequence-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: -2px;
        bottom: 0;
        width: 2px;
        height: 100%;
        background: #e9ecef;
    }

    .sequence-marker {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #4CAF50;
        color: #4CAF50;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 1rem;
        position: relative;
        z-index: 1;
    }

    .sequence-item.start .sequence-marker,
    .sequence-item.end .sequence-marker {
        background: #fff;
        border-color: #2196F3;
        color: #2196F3;
    }

    .sequence-content {
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .location-name {
        font-weight: 500;
        color: #2c3e50;
    }

    .sequence-list {
        max-height: 400px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #4CAF50 #f1f1f1;
    }

    .sequence-list::-webkit-scrollbar {
        width: 4px;
    }

    .sequence-list::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .sequence-list::-webkit-scrollbar-thumb {
        background: #4CAF50;
        border-radius: 4px;
    }

    /* Hover effects */
    .sequence-item:hover {
        background: #f8fafb;
    }

    .badge {
        font-weight: 500;
        padding: 0.5em 0.8em;
    }

    .sequence-container {
        position: relative;
        padding: 1rem;
    }

    .sequence-item {
        display: flex;
        align-items: flex-start;
        padding: 1rem;
        background: white;
        border-radius: 8px;
        margin-bottom: 0.75rem;
        border: 1px solid #e9ecef;
        transition: all 0.2s ease;
    }

    .sequence-item:hover {
        background: #f8f9fa;
        transform: translateX(5px);
        border-color: #dee2e6;
    }

    .sequence-marker {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #4CAF50;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    .sequence-marker.start {
        border-color: #28a745;
    }

    .sequence-marker.end {
        border-color: #007bff;
    }

    .sequence-content {
        flex: 1;
    }

    .location-name {
        font-weight: 500;
        color: #2c3e50;
        display: block;
        margin-bottom: 0.25rem;
    }

    .sequence-list {
        max-height: 400px;
        overflow-y: auto;
        margin: 1rem 0;
        padding-right: 0.5rem;
    }

    .route-info-banner {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
    }

    /* Custom scrollbar */
    .sequence-list::-webkit-scrollbar {
        width: 4px;
    }

    .sequence-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .sequence-list::-webkit-scrollbar-thumb {
        background: #4CAF50;
        border-radius: 4px;
    }

    .badge {
        font-weight: 500;
        padding: 0.5em 0.8em;
    }

    /* Simplified and cleaned up styles */
    .sequence-list {
        max-height: 600px;
        overflow-y: auto;
    }

    .sequence-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.2s;
    }

    .sequence-item:hover {
        background-color: #f8f9fa;
    }

    .sequence-marker,
    .sequence-number {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        flex-shrink: 0;
    }

    .sequence-marker {
        background: #fff;
        border: 2px solid #4CAF50;
        color: #4CAF50;
    }

    .sequence-marker.start {
        border-color: #28a745;
        color: #28a745;
    }

    .sequence-marker.end {
        border-color: #007bff;
        color: #007bff;
    }

    .sequence-number {
        background: #4CAF50;
        color: white;
        font-weight: 500;
    }

    .sequence-content {
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .location-name {
        font-weight: 500;
        color: #2c3e50;
    }

    /* Custom scrollbar */
    .sequence-list::-webkit-scrollbar {
        width: 4px;
    }

    .sequence-list::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .sequence-list::-webkit-scrollbar-thumb {
        background: #4CAF50;
        border-radius: 4px;
    }

    /* Updated muted color palette */
    :root {
        --primary-color: #6B7B8C;
        /* Muted blue-gray */
        --success-color: #7C9082;
        /* Muted sage green */
        --info-color: #8E8BA3;
        /* Muted purple */
        --warning-color: #B69B85;
        /* Muted tan */
        --danger-color: #B68D8D;
        /* Muted rose */
        --background-hover: #F5F6F8;
        /* Light gray hover */
    }

    .sequence-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid #eef0f2;
        transition: background-color 0.2s;
    }

    .sequence-item:hover {
        background-color: var(--background-hover);
    }

    .sequence-marker {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        flex-shrink: 0;
    }

    .sequence-marker.start {
        border-color: var(--success-color);
        color: var(--success-color);
    }

    .sequence-marker.end {
        border-color: var(--info-color);
        color: var(--info-color);
    }

    .badge {
        font-weight: 500;
        padding: 0.5em 0.8em;
    }

    .badge.text-success {
        color: var(--success-color) !important;
    }

    .badge.text-info {
        color: var(--info-color) !important;
    }

    /* Update scrollbar colors */
    .sequence-list::-webkit-scrollbar-thumb {
        background: var(--primary-color);
    }

    /* Custom button styles */
    #getAISuggestions {
        transition: all 0.3s ease;
        border: none;
        background: linear-gradient(45deg, #28a745, #20c997);
    }

    #getAISuggestions:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
    }

    #getAISuggestions:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    #aiTipsButton {
        transition: all 0.3s ease;
    }

    #aiTipsButton:hover {
        background-color: #0d6efd;
        color: white;
        transform: translateY(-1px);
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Ensure map container maintains size */
    #map {
        height: 500px !important;
        min-height: 500px;
        border-radius: 10px;
        position: relative;
        z-index: 1;
    }

    /* Button styles */
    #getAISuggestions {
        transition: all 0.3s ease;
        border: none;
        background: linear-gradient(45deg, #28a745, #20c997);
        min-width: 150px; /* Prevent size changes */
    }

    #getAISuggestions:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
    }

    #getAISuggestions:disabled {
        opacity: 0.85;
        cursor: not-allowed;
        /* Maintain gradient even when disabled */
        background: linear-gradient(45deg, #28a745, #20c997);
    }

    /* Ensure consistent button content layout */
    #getAISuggestions i,
    #getAISuggestions span {
        display: inline-block;
        vertical-align: middle;
    }

    /* Maintain icon size */
    #getAISuggestions i {
        font-size: 1.25rem;
        width: 1.25rem;
        height: 1.25rem;
        line-height: 1;
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>

<?php include 'footer.php'; ?>