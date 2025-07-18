<?php
session_start();
if (!($_SESSION['logged_in'] ?? false)) {
    header("Location: login.php");
    exit;
}

include 'db.php';
$conn = connectToDB();

$message = '';

// 🚗 Add new driver
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['driver_name'])) {
    $driverName = trim($_POST['driver_name']);

    if ($driverName !== '') {
        $stmt = $conn->prepare("INSERT INTO Drivers (name) VALUES (?)");
        $stmt->bind_param("s", $driverName);
        if ($stmt->execute()) {
            $message = "✅ Driver added successfully!";
        } else {
            $message = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "❌ No name provided.";
    }
}

// 🗑️ Delete driver
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM Drivers WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        $message = "🗑️ Driver deleted.";
    } else {
        $message = "❌ Failed to delete driver: " . $stmt->error;
    }
    $stmt->close();
}

// 🧠 Fetch all drivers
$drivers = [];
$result = $conn->query("SELECT id, name FROM Drivers ORDER BY id DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $drivers[] = $row;
    }
}

$conn->close();
?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SCLR Admin</title>
</head>
<body>
    <h1>Admin Panel</h1>

    <form method="post">
        <label for="driver_name">Driver Name:</label>
        <input type="text" id="driver_name" name="driver_name" required>
        <button type="submit">Add Driver</button>
    </form>

    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <h2>Driver Roster - Euro Series</h2>

    <?php if (!empty($drivers)): ?>
        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($drivers as $driver): ?>
                    <tr>
                        <td><?= htmlspecialchars($driver['id']) ?></td>
                        <td><?= htmlspecialchars($driver['name']) ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= $driver['id'] ?>">
                                <button type="submit" onclick="return confirm('Delete this driver?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No drivers found yet.</p>
    <?php endif; ?>

    <p><a href="logout.php">Log out</a></p>
    <p><a href="index.php">Home</a></p>
</body>
</html>
