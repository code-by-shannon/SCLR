<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/db.php';

// ✅ Add this right here:
$season_table = preg_replace('/[^a-zA-Z0-9_]/', '_', $_SESSION['season.name'] ?? 'Unnamed_Season');

$conn = connectToDB();

// create the new season table
$sql = "CREATE TABLE IF NOT EXISTS `$season_table` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    round INT UNSIGNED NOT NULL,
    track_id INT UNSIGNED NOT NULL
)";
if (!$conn->query($sql)) {
    die('Create failed: ' . $conn->error);
}

// insert track list from session
$stmt = $conn->prepare("INSERT INTO `$season_table` (round, track_id) VALUES (?, ?)");
foreach ($_SESSION['season.tracks'] as $row) {
    $stmt->bind_param('ii', $row['round'], $row['track_id']);
    $stmt->execute();
}
$stmt->close();
$conn->close();

echo "<h2>Step 2: Drivers (placeholder)</h2>";
echo "<p>Season table '$season_table' created and tracks inserted!</p>";
echo "<pre>" . htmlspecialchars(json_encode($_SESSION['season.tracks'] ?? [], JSON_PRETTY_PRINT)) . "</pre>";
?>

