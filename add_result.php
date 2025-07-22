<?php
include 'db.php';
$conn = connectToDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_id = $_POST['driver_id'];
    $track_id = $_POST['track_id'];
    $position = (int)$_POST['position'];

    // F1 style points (no fastest lap)
    $points_lookup = [1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10, 6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1];
    $points = $points_lookup[$position] ?? 0;

    $stmt = $conn->prepare("INSERT INTO race_results (driver_id, track_id, position, points) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiii", $driver_id, $track_id, $position, $points);
    $stmt->execute();
    $stmt->close();

    echo "<p>✅ Result added!</p>";
}

// Fetch drivers and tracks
$drivers = $conn->query("SELECT id, name FROM drivers ORDER BY name");
$tracks = $conn->query("SELECT id, name FROM tracks ORDER BY name");
?>

<form method="POST">
    <label for="driver">Driver:</label>
    <select name="driver_id" id="driver" required>
        <?php while ($row = $drivers->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <label for="track">Track:</label>
    <select name="track_id" id="track" required>
        <?php while ($row = $tracks->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <label for="position">Finish Position:</label>
    <input type="number" name="position" id="position" min="1" required><br><br>

    <button type="submit">Submit Result</button>
</form>
