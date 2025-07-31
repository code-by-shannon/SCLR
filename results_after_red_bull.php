<?php
include 'db.php';
$conn = connectToDB();

$sql = "
  SELECT 
    d.name AS driver,
    COALESCE(SUM(CASE WHEN rr.track_id = 1 THEN rr.points ELSE 0 END), 0) AS `Track 1`,
    COALESCE(SUM(CASE WHEN rr.track_id = 2 THEN rr.points ELSE 0 END), 0) AS `Track 2`,
    COALESCE(SUM(rr.points), 0) AS season_points
  FROM Drivers d
  LEFT JOIN race_results rr ON d.id = rr.driver_id
  GROUP BY d.id, d.name
  ORDER BY season_points DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Red Bull Ring Leaderboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/red_bull.css">
  

</head>
<body>
  <img src="imgs/redbull_logo.png" alt="Red Bull Logo" class="logo">
  <h1>Red Bull Ring Leaderboard</h1>

  <table>
    <thead>
      <tr>
        <th>Driver</th>
        <th>Monza</th>
        <th>Red Bull Ring</th>
        <th>Season Points</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['driver']) ?></td>
          <td><?= $row['Track 1'] ?></td>
          <td><?= $row['Track 2'] ?></td>
          <td><?= $row['season_points'] ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>
