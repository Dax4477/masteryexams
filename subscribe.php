<?php
// Define database credentials
$host = 'localhost';
$db   = 'mastissx_newsletter';
$user = 'mastissx_admin';
$pass = 'masteryadmin'; // Fixed potential 'n' to 'm' typo. Update if needed!
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Essential for error trapping
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // DIAGNOSTIC: Tells you exactly if the username/password/database name is wrong
    echo "Database Connection Error: " . $e->getMessage();
    exit;
}

// Check if an email was sent via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    
    // Sanitize the email
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
            // 23000 is the SQLSTATE code for constraint violation. 
            // errorInfo[1] == 1062 is MySQL's specific driver code for Duplicate Entry.
            if ($e->getCode() == 23000 || (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062)) {
                echo "duplicate";
            } else {
                // DIAGNOSTIC: Tells you exactly if the table name 'subscribers' or column name is wrong!
                echo "SQL Query Error: " . $e->getMessage(); 
            }
        }
    } else {
        echo "invalid";
    }
} else {
    echo "Direct access not allowed.";
}
?>
