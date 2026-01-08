<?php
/***** CONFIG *****/
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "SCLR";

/***** CONNECT *****/
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
  die("DB connection failed: " . htmlspecialchars($conn->connect_error));
}

/***** GRAB POST *****/
$trackId  = (int)($_POST['track_id'] ?? 0);
$results  = $_POST['results'] ?? [];  // e.g. ['1'=>'7','2'=>'3', ...]
$errors   = [];

if ($trackId <= 0) {
  $errors[] = "Please choose a track.";
}

/***** LOAD SEASON DRIVERS (to compute DNFs) *****/
$seasonDrivers = [];
$q = "
  SELECT d.id
  FROM January_Sprint_2026_No_BOP_drivers sd
  JOIN Drivers d ON d.id = sd.driver_id
";

if (!($rs = $conn->query($q))) {
  $errors[] = "Could not load season drivers: " . htmlspecialchars($conn->error);
} else {
  while ($row = $rs->fetch_assoc()) $seasonDrivers[] = (int)$row['id'];
  $rs->free();
}
if (!$seasonDrivers) $errors[] = "No season drivers found.";

/***** SIMPLE GUARD: no duplicate drivers across positions *****/
$chosenDrivers = [];
foreach ($results as $pos => $driverId) {
  $driverId = (int)$driverId;
  if ($driverId > 0) {
    if (in_array($driverId, $chosenDrivers, true)) {
      $errors[] = "Driver ID $driverId selected more than once (duplicate selection).";
      break;
    }
    $chosenDrivers[] = $driverId;
  }
}

if ($errors) {
  echo "<h2>Could not save</h2><ul>";
  foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>";
  echo "</ul><p><a href='results.php'>&larr; Back</a></p>";
  exit;
}

/***** POINT LOOKUP (position → points). DNF handled as 'DNF' = 0 *****/
$getPoints = $conn->prepare("SELECT points FROM points_system WHERE position = ?");
if (!$getPoints) {
  die("Prepare failed: " . htmlspecialchars($conn->error));
}

/***** START TRANSACTION *****/
$conn->begin_transaction();

try {
  // Optional: replace any prior results for this track (so re-submits overwrite)
  $del = $conn->prepare(
    "DELETE FROM January_Sprint_2026_No_BOP_Results WHERE track_id = ?"
  );
  
  $del->bind_param("i", $trackId);
  if (!$del->execute()) throw new Exception("Delete failed: " . $conn->error);
  $del->close();

  // Insert all chosen finishers
  $ins = $conn->prepare("
    INSERT INTO January_Sprint_2026_No_BOP_Results (track_id, driver_id, points)
    VALUES (?, ?, ?)
  ");
  if (!$ins) throw new Exception("Prepare insert failed: " . $conn->error);

  foreach ($results as $pos => $driverId) {
    $driverId = (int)$driverId;
    if ($driverId <= 0) continue; // skip empty dropdowns

    // lookup points for this numeric position
    $posKey = (string)$pos; // positions are stored as strings in points_system
    $getPoints->bind_param("s", $posKey);
    $getPoints->execute();
    $getPoints->bind_result($pts);
    $found = $getPoints->fetch();
    $getPoints->free_result();
    if (!$found) $pts = 0; // fallback (shouldn't happen if table is seeded)

    $ins->bind_param("iii", $trackId, $driverId, $pts);
    if (!$ins->execute()) throw new Exception("Insert finisher failed: " . $conn->error);
  }

  // Any drivers not chosen → DNF (0 points from table)
  $unchosen = array_values(array_diff($seasonDrivers, $chosenDrivers));
  if ($unchosen) {
    // get DNF points (expected 0)
    $dnfKey = 'DNF';
    $getPoints->bind_param("s", $dnfKey);
    $getPoints->execute();
    $getPoints->bind_result($dnfPts);
    $foundDNF = $getPoints->fetch();
    $getPoints->free_result();
    if (!$foundDNF) $dnfPts = 0;

    foreach ($unchosen as $driverId) {
      $ins->bind_param("iii", $trackId, $driverId, $dnfPts);
      if (!$ins->execute()) throw new Exception("Insert DNF failed: " . $conn->error);
    }
  }

  $ins->close();
  $getPoints->close();

  $conn->commit();

} catch (Throwable $e) {
  $conn->rollback();
  echo "<h2>Save failed</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
  echo "<p><a href='results.php'>&larr; Back</a></p>";
  exit;
}

/***** SHOW A QUICK SUMMARY *****/
echo "<h2>Results saved for track ID " . (int)$trackId . "</h2>";

echo "<p><a href='results.php'>&larr; Enter another race</a></p>";

/***** OPTIONAL: show what’s now in the table for this track *****/
$check = $conn->prepare("
  SELECT r.driver_id, d.name, r.points
  FROM January_Sprint_2026_No_BOP_Results r
  JOIN Drivers d ON d.id = r.driver_id
  WHERE r.track_id = ?
  ORDER BY r.points DESC, d.name ASC
");
$check->bind_param("i", $trackId);
$check->execute();
$res = $check->get_result();

echo "<table border='1' cellpadding='6' cellspacing='0'>
        <tr><th>Driver</th><th>Points</th></tr>";
while ($row = $res->fetch_assoc()) {
  echo "<tr><td>" . htmlspecialchars($row['name']) . "</td><td>" . (int)$row['points'] . "</td></tr>";
}
echo "</table>";

$check->close();
$conn->close();
