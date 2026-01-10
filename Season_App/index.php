<?php
define('BASE_URL', '/sclr/Season_App/');

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "choose_user.php");
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

// DB connection
$conn = new mysqli("localhost", "root", "", "SCLR");
if ($conn->connect_error) {
    die("DB connection failed");
}

// Fetch active user's name
$currentUserName = 'Unknown';

$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$stmt->bind_result($currentUserName);
$stmt->fetch();
$stmt->close();

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCLR</title>
    <link rel="stylesheet" href="main.css">
</head>
<body>
<div style="
  padding: 0.5rem 1rem;
  background: #f4f6f8;
  border-bottom: 1px solid #ddd;
  margin-bottom: 1rem;
  font-size: 0.95rem;
">
  Active user: <strong><?= htmlspecialchars($currentUserName) ?></strong>
  &nbsp;|&nbsp;
  <a href="<?= BASE_URL ?>exit_user.php">Exit user</a>


</div>


    <h1>Welcome to Seriously Casual League Racing</h1>
    <ul>
        <li>   <a href="drivers.php">🧑‍✈️ View / Add Drivers to master DB</a></li>
        <li><a href="view_seasons_current_and_archived.php">📅  View Current Season / Season Archives</a></li>
        <li><a href="createNewSeason.php">Build a Season</a></li>
        <li><a href="driver_recruit_WS2025.php">Recruit Racers For a Series</a></li>
        <li><a href="results.php">Enter Race Results Form</a></li>
        <li><a href="leaderboard.php">Leadboard</a></li>
    </ul>
   

</body>
</html>