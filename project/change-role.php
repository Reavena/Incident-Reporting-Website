<?php
require 'template.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $role_id = $_POST['role_id'];

    // validation and sanitization
    $user_id = (int) $user_id;
    $role_id = (int) $role_id;

    $query = "UPDATE User SET role_id = $role_id WHERE user_id = $user_id";
    $mysqli->query($query);

    header("Location: all-users.php");
    exit;
}
?>