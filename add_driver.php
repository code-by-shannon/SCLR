<?php
session_start();
if (!($_SESSION['logged_in'] ?? false)) {
    header("Location: login.php");
    exit;
}
?>



<?php
// Connect to the SCLR database
$conn = new mysqli("localhost", "root", "", "SCLR");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$driverName = trim($_POST['driver_name'] ?? '');

if ($driverName !== '') {
    $stmt = $conn->prepare("INSERT INTO Drivers (name) VALUES (?)");
    $stmt->bind_param("s", $driverName);
    if ($stmt->execute()) {
        echo "✅ Driver added successfully!";
    } else {
        echo "❌ Error: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "❌ No name provided.";
}

$conn->close();
?>
