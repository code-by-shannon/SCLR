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

/*
  Assumptions (from your code/screenshot):
  - Season tracks in: SCLR_world_series_fall_2025 (columns: track_id, …)
  - Season drivers in: SCLR_world_series_fall_2025_drivers (driver_id, …)
  - Track names in:    tracks (id, course)
  - Driver names in:   Drivers (id, name)
  - Results in:        SCLR_WS_Fall_2025_Results (track_id, driver_id, points)
*/

/***** 1) Get season tracks (human-readable) *****/
$sqlTracks = "
  SELECT DISTINCT t.id AS track_id, t.course AS track_name
  FROM SCLR_world_series_fall_2025 s
  JOIN tracks t ON t.id = s.track_id
  ORDER BY FIELD(t.id, 52,74,39,11,77,51,36,5,54)

";
$resTracks = $conn->query($sqlTracks);
if (!$resTracks) die('Tracks query failed: ' . htmlspecialchars($conn->error));

$tracks = [];          // ordered list of tracks for headers
$trackIds = [];        // for IN (...) filtering
while ($row = $resTracks->fetch_assoc()) {
    $tid = (int)$row['track_id'];
    $tracks[] = ['id' => $tid, 'name' => $row['track_name']];
    $trackIds[] = $tid;
}

/***** 2) Get season drivers (human-readable) *****/
$sqlDrivers = "
  SELECT d.id AS driver_id, d.name AS driver_name
  FROM SCLR_world_series_fall_2025_drivers sd
  JOIN Drivers d ON d.id = sd.driver_id
  ORDER BY d.name ASC
";
$resDrivers = $conn->query($sqlDrivers);
if (!$resDrivers) die('Drivers query failed: ' . htmlspecialchars($conn->error));

$drivers = [];         // will hold driver rows with totals later
$driverIds = [];
while ($row = $resDrivers->fetch_assoc()) {
    $did = (int)$row['driver_id'];
    $drivers[$did] = [
        'driver_id'   => $did,
        'driver_name' => $row['driver_name'],
        'per_track'   => [],  // track_id => points
        'total'       => 0
    ];
    $driverIds[] = $did;
}

/***** Short-circuit if nothing to show yet *****/
if (empty($tracks) || empty($drivers)) {
    // Render minimal page
    ?>
    <!doctype html><meta charset="utf-8">
    <h1>Leaderboard</h1>
    <p><?php echo empty($tracks) ? "No tracks in season." : ""; ?>
       <?php echo empty($drivers) ? "No drivers in season." : ""; ?></p>
    <?php
    exit;
}

/***** 3) Load results for just these drivers + tracks *****/
$inTrack  = implode(',', array_map('intval', $trackIds));
$inDriver = implode(',', array_map('intval', $driverIds));

$sqlResults = "
  SELECT r.driver_id, r.track_id, SUM(r.points) AS pts
  FROM SCLR_WS_Fall_2025_Results r
  WHERE r.track_id IN ($inTrack)
    AND r.driver_id IN ($inDriver)
  GROUP BY r.driver_id, r.track_id
";
$resResults = $conn->query($sqlResults);
if (!$resResults) die('Results query failed: ' . htmlspecialchars($conn->error));

/***** 4) Pivot in PHP + compute totals *****/
while ($row = $resResults->fetch_assoc()) {
    $did = (int)$row['driver_id'];
    $tid = (int)$row['track_id'];
    $pts = (int)$row['pts'];
    if (isset($drivers[$did])) {
        $drivers[$did]['per_track'][$tid] = $pts;
        $drivers[$did]['total'] += $pts;
    }
}

/***** 5) Sort drivers by total DESC, then name ASC *****/
usort($drivers, function($a, $b) {
    if ($a['total'] === $b['total']) {
        return strcasecmp($a['driver_name'], $b['driver_name']);
    }
    return $b['total'] <=> $a['total'];
});

/***** 6) Render the table *****/
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>SCLR — Season Leaderboard</title>
  <link rel="stylesheet" href="leaderboard.css">
</head>
<body>
  <h1>SCLR — Season Leaderboard</h1>

  <table id="leaderboard">
    <thead>
      <tr>
        <th class="driver sticky">Driver</th>
        <?php foreach ($tracks as $t): ?>
          <th class="sticky"><?php echo htmlspecialchars($t['name']); ?></th>
        <?php endforeach; ?>
        <th class="sticky">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($drivers as $row): ?>
        <tr data-driver-id="<?php echo (int)$row['driver_id']; ?>">
          <th scope="row" class="driver"><?php echo htmlspecialchars($row['driver_name']); ?></th>
          <?php foreach ($tracks as $t): 
            $tid = (int)$t['id'];
            $val = $row['per_track'][$tid] ?? 0; ?>
            <td class="num" data-track-id="<?php echo $tid; ?>"><?php echo (int)$val; ?></td>
          <?php endforeach; ?>
          <td class="num"><strong><?php echo (int)$row['total']; ?></strong></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Optional tiny auto-refresh (every 30s). Kill it if you don’t want it. -->
  <script>
    // If you later expose a JSON endpoint, swap this for AJAX.
    // For now, a low-impact full refresh keeps it "dynamic".
    setTimeout(() => location.reload(), 30000);
  </script>
</body>
</html>
<?php
/***** CLEANUP *****/
$resTracks->free();
$resDrivers->free();
if (isset($resResults) && $resResults) $resResults->free();
$conn->close();
