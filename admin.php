<?php
session_start();
if (!($_SESSION['logged_in'] ?? false)) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "SCLR");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driverName = trim($_POST['driver_name'] ?? '');

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

    <p><a href="logout.php">Log out</a></p>
</body>
</html>


