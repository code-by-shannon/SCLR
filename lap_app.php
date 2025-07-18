<?php
include 'db.php';
$conn = connectToDB();
$message = '';

function secondsToLapFormat(float $seconds): string {
    $minutes = floor($seconds / 60);
    $remainder = $seconds - ($minutes * 60);
    return sprintf('%d:%06.3f', $minutes, $remainder);
}


// Handle insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lap_number'], $_POST['lap_time'])) {
    $lapNumber = (int) $_POST['lap_number'];
    $lapTime = trim($_POST['lap_time']);
    $lapSeconds = convertToSeconds($lapTime);
    $isPit = isset($_POST['is_pit']) ? 1 : 0; // Checkbox unchecked = 0

    if ($lapSeconds === false) {
        $message = "Invalid format. Use M:SS.mmm";
    } else {
        $stmt = $conn->prepare("INSERT INTO lap_times (lap_number, lap_time, lap_seconds, is_pit) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isdi", $lapNumber, $lapTime, $lapSeconds, $isPit);
        if ($stmt->execute()) {
            $message = "Lap added.";
        } else {
            $message = "Insert error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM lap_times WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: lap_app.php");
    exit;
}

// Time conversion
function convertToSeconds($time) {
    if (preg_match('/^(\d+):(\d{2}\.\d+)$/', $time, $m)) {
        return round($m[1] * 60 + $m[2], 3);
    }
    return false;
}

// Fetch existing laps
$result = $conn->query("SELECT * FROM lap_times ORDER BY lap_number ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lap App</title>
</head>
<body>

<h2>Enter Lap Time</h2>
<form method="post" action="lap_app.php">
    Lap #: <input type="number" name="lap_number" required>
    Time (M:SS.mmm): <input type="text" name="lap_time" required>
    Pit Lap? <input type="checkbox" name="is_pit" value="1">
    <button type="submit">Add</button>
</form>


<p><?= $message ?></p>

<h2>Lap Times</h2>
<table border="1" cellpadding="5">
    <tr>
        <th>Lap</th>
        <th>Time</th>
        <th>Seconds</th>
        <th>Pit?</th>
        <th>Delete</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['lap_number']) ?></td>
        <td><?= htmlspecialchars($row['lap_time']) ?></td>
        <td><?= htmlspecialchars($row['lap_seconds']) ?></td>
        <td><?= $row['is_pit'] ? '✅' : '' ?></td>
        <td><a href="?delete=<?= $row['id'] ?>">Delete</a></td>
    </tr>
    <?php endwhile; ?>
</table>

<?php
// Fetch average of clean laps (not pit stops)
$summaryQuery = $conn->query("
    SELECT COUNT(*) AS lap_count, 
           SUM(lap_seconds) AS total_sec,
           MIN(lap_seconds) AS best_lap,
           MAX(lap_seconds) AS worst_lap
    FROM lap_times
    WHERE is_pit = 0
");

$summary = $summaryQuery->fetch_assoc();

$lapCount = (int) $summary['lap_count'];
$totalSec = (float) $summary['total_sec'];
$avgSec   = $lapCount ? $totalSec / $lapCount : 0;

$bestLap = secondsToLapFormat((float)$summary['best_lap']);
$worstLap = secondsToLapFormat((float)$summary['worst_lap']);
$avgLap  = secondsToLapFormat($avgSec);
?>

<h3>Clean Laps Summary</h3>
<table border="1" cellpadding="5">
    <tr>
        <th>Laps Counted</th>
        <th>Average Lap</th>
        <th>Best Lap</th>
        <th>Worst Lap</th>
    </tr>
    <tr>
        <td><?= $lapCount ?></td>
        <td><?= $avgLap ?></td>
        <td><?= $bestLap ?></td>
        <td><?= $worstLap ?></td>
    </tr>
</table>

<?php
$pitQuery = $conn->query("
    SELECT
        AVG(CASE WHEN is_pit = 0 THEN lap_seconds END) AS avg_clean_lap,
        AVG(CASE WHEN is_pit = 1 THEN lap_seconds END) AS avg_pit_lap
    FROM lap_times
");

$pitData = $pitQuery->fetch_assoc();
$avgClean = (float) $pitData['avg_clean_lap'];
$avgPit = (float) $pitData['avg_pit_lap'];
$pitDelta = $avgPit - $avgClean;

$avgCleanFmt = secondsToLapFormat($avgClean);
$avgPitFmt = secondsToLapFormat($avgPit);
$pitDeltaFmt = round($pitDelta, 3);
?>

<h3>Pit Delta Summary</h3>
<table border="1" cellpadding="5">
    <tr>
        <th>Average Clean Lap</th>
        <th>Average Pit Lap</th>
        <th>Delta</th>
    </tr>
    <tr>
        <td><?= $avgCleanFmt ?></td>
        <td><?= $avgPitFmt ?></td>
        <td><?= $pitDeltaFmt ?> seconds</td>
    </tr>
</table>

</body>
</html>
