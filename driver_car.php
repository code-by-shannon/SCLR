<?php
include 'db.php';
$conn = connectToDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $driverId = (int) $_POST['driver_id'];
    $carId = (int) $_POST['car_id'];

    // Check if this driver already has this car
    $check = $conn->prepare("SELECT id FROM driver_cars WHERE driver_id = ? AND car_id = ?");
    $check->bind_param("ii", $driverId, $carId);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<p style='color:red;'>❌ This car is already assigned to that driver.</p>";
    } else {
        // Count how many cars are assigned to this driver
        $count = $conn->prepare("SELECT COUNT(*) FROM driver_cars WHERE driver_id = ?");
        $count->bind_param("i", $driverId);
        $count->execute();
        $count->bind_result($carCount);
        $count->fetch();
        $count->close();

        if ($carCount >= 2) {
            echo "<p style='color:red;'>❌ This driver already has 2 cars assigned.</p>";
        } else {
            // All clear, insert the assignment
            $stmt = $conn->prepare("INSERT INTO driver_cars (driver_id, car_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $driverId, $carId);
            if ($stmt->execute()) {
                echo "<p style='color:green;'>✅ Car assigned successfully!</p>";
            } else {
                echo "<p style='color:red;'>❌ Error: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    }

    $check->close();
}


// Fetch drivers
$drivers = $conn->query("SELECT id, name FROM drivers ORDER BY name");

// Fetch cars
$cars = $conn->query("SELECT id, manufacturer, model, type FROM cars ORDER BY manufacturer, model");
?>

<div style='display:flex; gap:2rem'>
    <div>
        <h2>Drivers</h2>
        <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Name</th>
        </tr>
        <?php while ($row = $drivers->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
        </tr>
        <?php endwhile; ?>
        </table>
    </div>


    <div>
        <h2>Cars</h2>
        <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Manufacturer</th>
            <th>Model</th>
            <th>Type</th>
        </tr>
        <?php while ($car = $cars->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($car['id']) ?></td>
            <td><?= htmlspecialchars($car['manufacturer']) ?></td>
            <td><?= htmlspecialchars($car['model']) ?></td>
            <td><?= htmlspecialchars($car['type']) ?></td>
        </tr>
        <?php endwhile; ?>
        </table>
    </div>
<div>
<h2>Assign Car to Driver</h2>
<form method="post" action="">
    <label for="driver_id">Driver:</label>
    <select name="driver_id" required>
        <?php
        $drivers->data_seek(0); // rewind the result set
        while ($row = $drivers->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
        <?php endwhile; ?>
    </select>

    <label for="car_id">Car:</label>
    <select name="car_id" required>
        <?php
        $cars->data_seek(0); // rewind the result set
        while ($car = $cars->fetch_assoc()): ?>
            <option value="<?= $car['id'] ?>">
                <?= htmlspecialchars($car['manufacturer']) ?> <?= htmlspecialchars($car['model']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <button type="submit" name="assign">Assign</button>
</form>
</div>

</div>

<h2>Driver Assignments (GR3 & GR4)</h2>

<table border="1" cellpadding="8">
  <tr>
    <th>Driver</th>
    <th>Car (GR3)</th>
    <th>Car (GR4)</th>
  </tr>
  <?php
$query = $conn->query("
SELECT 
  d.name AS driver,
  MAX(CASE WHEN UPPER(c.type) = 'GR.3' THEN CONCAT(c.manufacturer, ' ', c.model) END) AS gr3_car,
  MAX(CASE WHEN UPPER(c.type) = 'GR.4' THEN CONCAT(c.manufacturer, ' ', c.model) END) AS gr4_car
FROM drivers d
LEFT JOIN driver_cars dc ON d.id = dc.driver_id
LEFT JOIN cars c ON dc.car_id = c.id
GROUP BY d.id, d.name
ORDER BY d.name
");


  while ($row = $query->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['driver']) ?></td>
      <td><?= htmlspecialchars($row['gr3_car'] ?? '—') ?></td>
      <td><?= htmlspecialchars($row['gr4_car'] ?? '—') ?></td>
    </tr>
  <?php endwhile; ?>
</table>


<a href="index.php">home</a>

