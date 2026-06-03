<?php
// track.php - UPDATED to accept User Time

$servername = "localhost";
$username = "mastissx_log_user"; 
$password = "crbY}~zsI9Fy"; 
$dbname = "mastissx_mastery_logs";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed"); }

// 1. Get IP
if (!empty($_SERVER['HTTP_CLIENT_IP'])) { $ip = $_SERVER['HTTP_CLIENT_IP']; }
elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ip = $_SERVER['HTTP_X_FORWARDED_FOR']; }
else { $ip = $_SERVER['REMOTE_ADDR']; }

// 2. Get Data sent from the Browser
$page = isset($_POST['url']) ? $conn->real_escape_string($_POST['url']) : 'Unknown';
$device_id = isset($_POST['device_id']) ? $conn->real_escape_string($_POST['device_id']) : 'unknown';

// 3. CRITICAL: Check if 'user_time' was sent
if (isset($_POST['user_time']) && !empty($_POST['user_time'])) {
    // Use the time from the visitor's laptop/phone
    $visit_time = $conn->real_escape_string($_POST['user_time']);
} else {
    // Fallback to server time if something failed
    $visit_time = date("Y-m-d H:i:s");
}

// 4. Insert into Database (Note: We explicitly insert 'visit_time')
$sql = "INSERT INTO visitors (ip_address, page_url, device_id, visit_time) VALUES ('$ip', '$page', '$device_id', '$visit_time')";

$conn->query($sql);
$conn->close();
?>