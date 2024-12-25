<?php

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// Function to sanitize input data (to prevent SQL Injection & XSS)
function sanitize_input($input) {
    return htmlspecialchars(trim($input));
}

// Function to check if user is admin
function is_admin() {
    if (isset($_SESSION['user_id'])) {
        include 'includes/db.php';
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT Role FROM users WHERE ID_USER = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return ($result['Role'] === 'admin');
    }
    return false;
}

// Function to redirect to another page
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Function to fetch user by ID
function get_user_by_id($user_id) {
    include 'includes/db.php';
    $sql = "SELECT * FROM users WHERE ID_USER = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>
