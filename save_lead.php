<?php
// ... your existing code where you get $_POST['name'] and $_POST['phone'] ...
$name = $_POST['name'] ?? 'Unknown';
$phone = $_POST['phone'] ?? 'Unknown';


// --- MAGICAL RADAR SYNC ---
// This grabs the invisible cookie the radar assigned them earlier
$device_id = $_COOKIE['mastery_device_id'] ?? null;

if ($device_id && $name !== 'Unknown') {
    $radar_db_file = __DIR__ . '/mastery_tracker.sqlite';
    if (file_exists($radar_db_file)) {
        try {
            $radar_pdo = new PDO("sqlite:" . $radar_db_file);
            // Update their anonymous ID with their real Name and Phone!
            $radar_stmt = $radar_pdo->prepare("UPDATE visitors SET user_name = ?, user_phone = ? WHERE device_id = ?");
            $radar_stmt->execute([$name, $phone, $device_id]);
        } catch (Exception $e) {
            // Silently fail if DB is locked so we don't interrupt the user
        }
    }
}
// --------------------------


// ... the rest of your save_lead.php code ...
echo "Success";
?>