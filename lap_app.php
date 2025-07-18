<?php
include 'db.php';
$conn = connectToDB();
$message = '';

// Handle insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lap_number'], $_POST['lap_time'])) {
    $lapNumber = (int) $_POST['lap_number'];
    $lapTime = trim($_POST['lap_time']);
    $lapSeconds = convertToSeconds($lapTime);

    if ($lapSeconds === false) {
        $message = "Invalid format. Use M:SS.mmm";
    } else {
        $stmt = $conn->prepare("INSERT INTO lap_times (lap_number, lap_time, lap_seconds) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $lapNumber, $lapTime, $lapSeconds);
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
    <button type="submit">Add</button>
</form>

<p><?= $message ?></p>

<h2>Lap Times</h2>
<table border="1" cellpadding="5">
    <tr>
        <th>Lap</th>
        <th>Time</th>
        <th>Seconds</th>
        <th>Delete</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['lap_number']) ?></td>
        <td><?= htmlspecialchars($row['lap_time']) ?></td>
        <td><?= htmlspecialchars($row['lap_seconds']) ?></td>
        <td><a href="?delete=<?= $row['id'] ?>">Delete</a></td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
