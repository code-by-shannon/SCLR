<?php
// Connect to the DB
include '../db.php';
$conn = connectToDB();

// Get the results from the joined tables — Gr.3 cars only
$sql = "
    SELECT 
        d.name AS driver,
        CONCAT(c.manufacturer, ' ', c.model) AS car,
        r.position,
        r.points
    FROM race_results r
    JOIN drivers d ON r.driver_id = d.id
    JOIN tracks t ON r.track_id = t.id
    JOIN driver_cars dc ON d.id = dc.driver_id
    JOIN cars c ON dc.car_id = c.id AND c.type = 'Gr.3'
    WHERE t.name = 'Monza'
    ORDER BY r.points DESC
    LIMIT 25
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Monza Results</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 2rem;
      background-color: #f2f2f2;
    }

    h1 {
      text-align: center;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ccc;
    }

    thead {
      background-color: #333;
      color: #fff;
    }

    tbody tr:hover {
      background-color: #f0f0f0;
    }
  </style>
</head>
<body>
  <h1>Monza Race Results – Gr.3</h1>

  <table>
    <thead>
      <tr>
        <th>Driver</th>
        <th>Car</th>
        <th>Position</th>
        <th>Points</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['driver']) ?></td>
            <td><?= htmlspecialchars($row['car']) ?></td>
            <td><?= $row['position'] ?></td>
            <td><?= $row['points'] ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="4">No results for Monza.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
