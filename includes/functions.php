<?php

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function sanitize_input($input) {
    return htmlspecialchars(trim($input));
}

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

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function get_user_by_id($user_id) {
    include 'includes/db.php';
    $sql = "SELECT * FROM users WHERE ID_USER = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>
