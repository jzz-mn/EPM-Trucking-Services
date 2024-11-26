<?php
header('Content-Type: application/json');
include '../includes/db_connection.php';
include '../includes/config.php';

// Temporary debug
error_log("Debug - Environment check:");
error_log("Direct getenv(): " . (getenv('OPENAI_API_KEY') ? "Found" : "Not found"));
error_log("Constant defined: " . (defined('OPENAI_API_KEY') ? "Yes" : "No"));
error_log("Constant value empty: " . (defined('OPENAI_API_KEY') && empty(OPENAI_API_KEY) ? "Yes" : "No"));

try {
    // Debug logging
    error_log("Starting route tips generation...");
    error_log("OPENAI_API_KEY defined: " . (defined('OPENAI_API_KEY') ? 'Yes' : 'No'));
    error_log("OPENAI_API_KEY empty: " . (empty(OPENAI_API_KEY) ? 'Yes' : 'No'));
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    $cluster = $data['cluster'] ?? '';

    if (empty($cluster)) {
        throw new Exception('Cluster is required');
    }

    // Verify OpenAI API key exists with more detailed error
    if (!defined('OPENAI_API_KEY')) {
        throw new Exception('OpenAI API key constant is not defined');
    }
    
    if (empty(OPENAI_API_KEY)) {
        // Debug: Check environment variable directly
        $env_key = getenv('OPENAI_API_KEY');
        error_log("Direct environment check for OPENAI_API_KEY: " . ($env_key ? 'Found' : 'Not found'));
        throw new Exception('OpenAI API key is empty. Environment check: ' . ($env_key ? 'Key exists in env' : 'Key not in env'));
    }

    // Get cluster-specific data
    $query = "SELECT c.*, 
              COUNT(DISTINCT rw.id) as total_stops,
              c.LocationsInCluster as locations,
              c.Tonner as vehicle_type,
              c.KMRADIUS as radius,
              c.FuelPrice as fuel_price
              FROM clusters c
              LEFT JOIN route_optimizations ro ON c.ClusterCategory = ro.cluster_category
              LEFT JOIN route_waypoints rw ON ro.id = rw.route_id
              WHERE c.ClusterCategory = ?
              GROUP BY c.ClusterCategory";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception('Database query preparation failed: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, 's', $cluster);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Database query execution failed: ' . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    $clusterData = mysqli_fetch_assoc($result);

    if (!$clusterData) {
        throw new Exception('No data found for this cluster');
    }

    // Prepare context for ChatGPT
    $context = [
        'cluster_name' => $cluster,
        'total_stops' => $clusterData['total_stops'] ?? 0,
        'km_radius' => $clusterData['radius'] ?? 30,
        'vehicle_type' => $clusterData['Tonner'] . ' Tonner',
        'route_distance' => $clusterData['KMRADIUS'] * 2 ?? 0,
        'fuel_price' => $clusterData['FuelPrice'] ?? 65.00,
        'locations' => $clusterData['LocationsInCluster'] ?? ''
    ];

    // Initialize cURL with SSL verification disabled
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    if (!$ch) {
        throw new Exception('Failed to initialize cURL');
    }

    // Update the prompt to focus on driver guidance and location-specific advice
    $prompt = "As a trucking operations expert, provide 5 specific tips for drivers operating in the {$context['cluster_name']} area. Consider:
    - Current route: {$context['cluster_name']}
    - Vehicle: {$context['vehicle_type']} truck
    - Daily stops: {$context['total_stops']} deliveries
    - Service areas: {$context['locations']}

    Give 5 practical tips that owners can share with their drivers to improve daily operations. Focus on:
    - Local road conditions and shortcuts
    - Specific loading/unloading spots
    - Common traffic bottlenecks in this area
    - Best delivery times for this route
    - Local parking and maneuvering tips

    Keep tips specific to {$context['cluster_name']} area. No general advice. One clear tip per line.";

    // Update system message to focus on driver operations
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a local trucking expert who knows every road and challenge in each delivery area. Provide specific, practical advice that owners can share with their drivers.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.8, // Slightly higher for more location-specific variety
            'max_tokens' => 300
        ]),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);

    // Execute cURL request
    $response = curl_exec($ch);
    
    if ($response === false) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        throw new Exception('OpenAI API error: ' . ($errorData['error']['message'] ?? 'Unknown error'));
    }

    $responseData = json_decode($response, true);
    if (!isset($responseData['choices'][0]['message']['content'])) {
        throw new Exception('Invalid API response format');
    }

    // Process ChatGPT response
    $aiResponse = $responseData['choices'][0]['message']['content'];
    $tips = array_filter(
        array_map(
            'trim',
            explode("\n", $aiResponse)
        ),
        function($tip) {
            return !empty($tip) && strlen($tip) > 10;
        }
    );

    // Ensure we have exactly 5 tips
    $tips = array_slice($tips, 0, 5);

    // Return success response
    echo json_encode([
        'success' => true,
        'tips' => $tips,
        'cluster_info' => $context
    ]);

} catch (Exception $e) {
    error_log('Route Tips Error: ' . $e->getMessage());
    error_log('Route Tips Error Trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'openai_key_defined' => defined('OPENAI_API_KEY'),
            'openai_key_empty' => empty(OPENAI_API_KEY),
            'env_check' => getenv('OPENAI_API_KEY') ? 'exists' : 'not found'
        ]
    ]);
}
?> 