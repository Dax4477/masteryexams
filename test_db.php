<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// PASTE YOUR CREDENTIALS HERE
$servername = "localhost";
$username = "mastissx_lead"; 
$password = "Obsera@1234";
$dbname = "mastissx_lead";

// Try to connect
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("❌ CONNECTION FAILED: " . $conn->connect_error);
} 
echo "✅ CONNECTION SUCCESSFUL! User '$username' can access database '$dbname'.";
?>