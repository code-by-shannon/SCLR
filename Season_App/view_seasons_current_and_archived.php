<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: choose_user.php");
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

// ---- DB CONFIG ----
$conn = new mysqli("localhost", "root", "", "SCLR");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$errors = [];
