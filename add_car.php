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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
</body>
</
