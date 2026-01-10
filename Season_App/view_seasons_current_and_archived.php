<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('BASE_URL', '/sclr/Season_App/');

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "choose_user.php");
    exit;
    
}

$currentUserId = (int)$_SESSION['user_id'];

// ---- DB CONFIG ----
$conn = new mysqli("localhost", "root", "", "SCLR");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$errors = [];

// current season fetch
$januaryRows = [];

$stmt = $conn->prepare("
  SELECT j.round, t.course AS track_name
  FROM January_Sprint_2026_No_BOP j
  LEFT JOIN tracks t ON j.track_id = t.id
  WHERE j.user_id = ?
  ORDER BY j.round ASC
");

$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $januaryRows[] = $row;
}

$stmt->close();


//archived seasons fetched

$worldSeriesRows = [];

$stmt = $conn->prepare("
  SELECT w.round, t.course AS track_name
  FROM SCLR_World_Series_Fall_2025 w
  LEFT JOIN tracks t ON w.track_id = t.id
  WHERE w.user_id = ?
  ORDER BY w.round ASC
");

$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $worldSeriesRows[] = $row;
}

$stmt->close();

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Seasons</title>
<style>
  body { font-family: system-ui, Arial, sans-serif; margin:2rem; }
  table { border-collapse: collapse; margin-bottom:2rem; width:100%; max-width:700px; }
  th, td { border:1px solid #ddd; padding:.6rem; }
  th { background:#f5f5f5; }
</style>
</head>
<body>

<a href="index.php">Home</a>
<h1>My Seasons</h1>

<h2>Current Season: January Sprint 2026 (No BOP)</h2>
<table>
  <tr>
    <th>Round</th>
    <th>Track</th>
  </tr>
  <?php if (!$januaryRows): ?>
    <tr><td colspan="2"><em>No rounds found.</em></td></tr>
  <?php endif; ?>
  <?php foreach ($januaryRows as $r): ?>
    <tr>
      <td><?= (int)$r['round'] ?></td>
      <td><?= htmlspecialchars($r['track_name']) ?></td>
    </tr>
  <?php endforeach; ?>
</table>

<h2>Archived Season: World Series Fall 2025</h2>
<table>
  <tr>
    <th>Round</th>
    <th>Track</th>
  </tr>
  <?php if (!$worldSeriesRows): ?>
    <tr><td colspan="2"><em>No rounds found.</em></td></tr>
  <?php endif; ?>
  <?php foreach ($worldSeriesRows as $r): ?>
    <tr>
      <td><?= (int)$r['round'] ?></td>
      <td><?= htmlspecialchars($r['track_name']) ?></td>
    </tr>
  <?php endforeach; ?>
</table>

</body>
</html>
