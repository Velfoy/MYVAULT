<?php
session_start();
include 'includes/functions.php';

check_login();


// Here, you would add admin functionalities like managing users, content, etc.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Admin Panel</h1>
    <p>Welcome to the admin panel. Here, you can manage users and content.</p>
    <a href="index.php">Back to Home</a>
</body>
</html>
