<?php
session_start();
if (!($_SESSION['logged_in'] ?? false)) {
    header("Location: login.php");
    exit;
}
$message = '';
?>

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

// delete logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        $message = "🗑️ Car deleted.";
    } else {
        $message = "❌ Error deleting car: " . $stmt->error;
    }
    $stmt->close();
}

// insert logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manufacturer'])) {
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $type = $_POST['type'] ?? '';

    if ($manufacturer !== '' && $model !== '' && $type !== '') {
        $stmt = $conn->prepare("INSERT INTO cars (manufacturer, model, type) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $manufacturer, $model, $type);
        if ($stmt->execute()) {
            $message = "✅ Car added successfully!";
        } else {
            $message = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "❌ All fields are required.";
    }
}

$cars = [];
$result = $conn->query("SELECT id, manufacturer, model, type FROM cars ORDER BY id DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cars[] = $row;
    }
}



$conn->close();
?>






<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Car - SCLR</title>
</head>
<body>
    <h1>Add Car</h1>

    <form method="post">
        <label for="manufacturer">Manufacturer:</label>
        <input type="text" id="manufacturer" name="manufacturer" required><br>

        <label for="model">Model:</label>
        <input type="text" id="model" name="model" required><br>

        <p>Type:</p>
<label>
    <input type="radio" name="type" value="Gr.3" required>
    Gr.3
</label>
<label>
    <input type="radio" name="type" value="Gr.4">
    Gr.4
</label><br>


        <button type="submit">Add Car</button>
    </form>

    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <h2>Car List</h2>

<?php if (count($cars) > 0): ?>
    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Manufacturer</th>
                <th>Model</th>
                <th>Type</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cars as $car): ?>
                <tr>
                    <td><?= htmlspecialchars($car['id']) ?></td>
                    <td><?= htmlspecialchars($car['manufacturer']) ?></td>
                    <td><?= htmlspecialchars($car['model']) ?></td>
                    <td><?= htmlspecialchars($car['type']) ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?= $car['id'] ?>">
                            <button type="submit" onclick="return confirm('Delete this car?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No cars in the garage yet.</p>
<?php endif; ?>

<a href="index.php">home</a>
</body>
</
