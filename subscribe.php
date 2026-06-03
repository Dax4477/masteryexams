<?php
// Define database credentials
$host = 'localhost';
$db   = 'mastissx_newsletter';
$user = 'mastissx_admin';
$pass = 'nasteryadmin'; // Replace with the password you just set
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Essential for the try/catch to work!
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo "error"; // Failed to connect to database
    exit;
}

// Check if an email was sent via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    
    // Sanitize the email just to be safe
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        
        try {
            // Prepare the insert statement
            $stmt = $pdo->prepare("INSERT INTO subscribers (email) VALUES (:email)");
            
            // Execute the statement
            $stmt->execute(['email' => $email]);
            
            // If we reach here, it worked!
            echo "success";
            
        } catch (\PDOException $e) {
            // 23000 is the standard SQLSTATE code for an integrity constraint violation (like a duplicate key)
            if ($e->getCode() == 23000 || $e->getCode() == 1062) {
                echo "duplicate";
            } else {
                // Some other random database error occurred
                echo "error"; 
            }
        }
    } else {
        // The email format was invalid (e.g., "vishnu@")
        echo "invalid";
    }
} else {
    // Someone visited the file directly without submitting a form
    echo "error";
}
?>