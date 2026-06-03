<?php
header('Content-Type: application/json');

// 1. Read the JSON data sent by JavaScript
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// 2. Safely extract the variables
$name = $data['name'] ?? 'Unknown';
$phone = $data['phone'] ?? 'Unknown';
$level = $data['level'] ?? 'Unknown';
$score = $data['score'] ?? '0/0';

// --- YOUR EXISTING MYSQL CODE CAN GO HERE (if you have any) ---


// --- UPDATE LIVE RADAR WITH USER IDENTITY ---
$device_id = $_COOKIE['mastery_device_id'] ?? null;
if ($device_id && $name !== 'Unknown') {
    $radar_db_file = __DIR__ . '/mastery_tracker.sqlite';
    if (file_exists($radar_db_file)) {
        try {
            $radar_pdo = new PDO("sqlite:" . $radar_db_file);
            $radar_stmt = $radar_pdo->prepare("UPDATE visitors SET user_name = ?, user_phone = ? WHERE device_id = ?");
            $radar_stmt->execute([$name, $phone, $device_id]);
        } catch (Exception $e) {
            // Silently fail if radar DB is locked
        }
    }
}
// ---------------------------------------------

// 3. Tell the browser it was a success so it unlocks the results
echo json_encode(['status' => 'success']);
exit;
?>