<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


include 'db.php';
$conn = connectToDB();
$message = '';

function secondsToLapFormat(float $seconds): string {
    $minutes = floor($seconds / 60);
    $remainder = $seconds - ($minutes * 60);
    return sprintf('%d:%06.3f', $minutes, $remainder);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_session_id'])) {
    $idToDelete = (int)$_POST['delete_session_id'];
    $stmt = $conn->prepare("DELETE FROM session_summaries WHERE id = ?");
    $stmt->bind_param("i", $idToDelete);
    if ($stmt->execute()) {
        $message = "🗑 Session deleted.";
    } else {
        $message = "❌ Error deleting session: " . $stmt->error;
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_archive'])) {
    $conn->query("DELETE FROM session_summaries");
    $message = "🧹 Archive cleared.";
}

 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_session'])) {
    $notes = trim($_POST['session_notes'] ?? '');
    $notes = (string)$notes;  // ✅ make sure it's a string

    if ($notes === '') {
        $notes = ' ';
    }

    
    
    

    // Get summary stats
    $summaryQuery = $conn->query("
        SELECT COUNT(*) AS lap_count, 
               AVG(CASE WHEN is_pit = 0 THEN lap_seconds END) AS avg_clean_lap,
               MIN(CASE WHEN is_pit = 0 THEN lap_seconds END) AS best_lap,
               AVG(CASE WHEN is_pit = 1 THEN lap_seconds END) -
               AVG(CASE WHEN is_pit = 0 THEN lap_seconds END) AS pit_delta
        FROM lap_times
    ");

    $summary = $summaryQuery->fetch_assoc();
    $avgLap = (float)$summary['avg_clean_lap'];
    $bestLap = (float)$summary['best_lap'];
    $pitDelta = (float)$summary['pit_delta'];
    $totalLaps = (int)$summary['lap_count'];

    // Insert into session_summaries table
    $stmt = $conn->prepare("
        INSERT INTO session_summaries (avg_clean_lap, best_lap, pit_delta, total_laps, notes)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("dddis", $avgLap, $bestLap, $pitDelta, $totalLaps, $notes);
    
    if ($stmt->execute()) {
        $message = "✅ Session saved. You can now reset your laps.";
    } else {
        $message = "❌ Error saving session: " . $stmt->error;
    }
    $stmt->close();

  

    // ✅ Now clear the lap data for a fresh start
    $conn->query("DELETE FROM lap_times");


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
        $stmt = $conn->prepare("
    INSERT INTO session_summaries (avg_clean_lap, best_lap, pit_delta, total_laps, notes)
    VALUES (?, ?, ?, ?, ?)
");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

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

$result = $conn->query("SELECT * FROM lap_times ORDER BY lap_number ASC");

if (!$result) {
    die("Lap query failed: " . $conn->error);
}


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

<h3>Save Session</h3>
<form method="post" action="lap_app.php">
    Notes (optional): <br>
    <textarea name="session_notes" rows="3" cols="40"></textarea><br><br>
    <button type="submit" name="save_session">Save Session & Reset</button>
</form>

<?php
$sessionResults = $conn->query("SELECT * FROM session_summaries ORDER BY session_date DESC");

if ($sessionResults && $sessionResults->num_rows > 0): ?>
    <h3>Session Archive</h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>Date</th>
            <th>Avg Lap (M:S.ms)</th>
            <th>Best Lap (M:S.ms)</th>
            <th>Pit Delta (s)</th>
            <th>Total Laps</th>
            <th>Notes</th>
        </tr>
        <?php while ($row = $sessionResults->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['session_date']) ?></td>
            <td><?= secondsToLapFormat((float)$row['avg_clean_lap']) ?></td>
            <td><?= secondsToLapFormat((float)$row['best_lap']) ?></td>
            <td><?= round($row['pit_delta'], 3) ?> s</td>
            <td><?= (int)$row['total_laps'] ?></td>
            <td>
            <?= nl2br(htmlspecialchars($row['notes'])) ?><br>

                <form method="post" action="lap_app.php" style="display:inline;">
                    <input type="hidden" name="delete_session_id" value="<?= $row['id'] ?>">
                    <button type="submit" onclick="return confirm('Delete this session?')">🗑 Delete</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <h3>Clear Archive</h3>
        <form method="post" action="lap_app.php" onsubmit="return confirm('Are you sure you want to clear the session archive? This cannot be undone.')">
            <button type="submit" name="clear_archive">🧹 Start New Archive</button>
            <p>No session summaries saved yet.</p>
        </form>    


    <?php else: ?>
    
    
<?php endif; ?>



</body>
</html>
