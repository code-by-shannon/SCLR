<?php
include 'db.php';
$conn = connectToDB();

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
</div>


