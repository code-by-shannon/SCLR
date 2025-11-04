<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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
  Assumptions:
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

$drivers = [];         // keyed by driver_id
$driverIds = [];
while ($row = $resDrivers->fetch_assoc()) {
    $did = (int)$row['driver_id'];
    $drivers[$did] = [
        'driver_id'        => $did,
        'driver_name'      => $row['driver_name'],
        'per_track'        => [],  // track_id => ['pts'=>int, 'exists'=>bool]
        'total'            => 0,
        'completed_count'  => 0,   // number of completed events with a row for this driver
        'drop_tid'         => null,
        'drop_value'       => 0,
        'total_after_drop' => 0
    ];
    $driverIds[] = $did;
}

/***** Short-circuit if nothing to show yet *****/
if (empty($tracks) || empty($drivers)) {
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

/* 3a) Which tracks have been run at all (any row exists)? */
$sqlCompleted = "
  SELECT r.track_id
  FROM SCLR_WS_Fall_2025_Results r
  WHERE r.track_id IN ($inTrack)
  GROUP BY r.track_id
";
$resCompleted = $conn->query($sqlCompleted);
if (!$resCompleted) die('Completed-tracks query failed: ' . htmlspecialchars($conn->error));

$completedTrack = []; // set: track_id => true
while ($r = $resCompleted->fetch_assoc()) {
    $completedTrack[(int)$r['track_id']] = true;
}
$resCompleted->free();

/* 3b) Pull results, and also know if a row existed (COUNT>0) */
$sqlResults = "
  SELECT r.driver_id, r.track_id, SUM(r.points) AS pts, COUNT(*) AS row_count
  FROM SCLR_WS_Fall_2025_Results r
  WHERE r.track_id IN ($inTrack)
    AND r.driver_id IN ($inDriver)
  GROUP BY r.driver_id, r.track_id
";
$resResults = $conn->query($sqlResults);
if (!$resResults) die('Results query failed: ' . htmlspecialchars($conn->error));

/***** 4) Pivot in PHP + compute raw totals *****/
while ($row = $resResults->fetch_assoc()) {
    $did    = (int)$row['driver_id'];
    $tid    = (int)$row['track_id'];
    $pts    = (int)$row['pts'];
    $exists = ((int)$row['row_count']) > 0;

    if (isset($drivers[$did])) {
        $drivers[$did]['per_track'][$tid] = ['pts' => $pts, 'exists' => $exists];
        $drivers[$did]['total'] += $pts;

        // Count this as a completed appearance for drop logic only if track is completed
        if ($exists && isset($completedTrack[$tid])) {
            $drivers[$did]['completed_count']++;
        }
    }
}
$resResults->free();

/***** 4b) Per-driver: find the single lowest completed result to drop *****/
foreach ($drivers as &$d) {
    $lowestVal = PHP_INT_MAX;
    $lowestTid = null;

    // Only drop once there are at least 2 completed appearances
    if ($d['completed_count'] >= 2) {
        foreach ($tracks as $t) {
            $tid = (int)$t['id'];

            // Only consider tracks that have actually been run
            if (!isset($completedTrack[$tid])) continue;

            // Consider only if this driver has a row (includes DNS=0)
            if (isset($d['per_track'][$tid]) && $d['per_track'][$tid]['exists']) {
                $v = (int)$d['per_track'][$tid]['pts']; // 0..N
                if ($v < $lowestVal) {
                    $lowestVal = $v;
                    $lowestTid = $tid;
                }
            }
        }
    }

    $d['drop_tid']         = $lowestTid;
    $d['drop_value']       = ($lowestTid === null) ? 0 : $lowestVal;
    $d['total_after_drop'] = (int)$d['total'] - (int)$d['drop_value'];
}
unset($d);

/***** 5) Sort drivers by total_after_drop DESC, then name ASC *****/
usort($drivers, function($a, $b) {
    if ($a['total_after_drop'] === $b['total_after_drop']) {
        return strcasecmp($a['driver_name'], $b['driver_name']);
    }
    return $b['total_after_drop'] <=> $a['total_after_drop'];
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
  <a href="index.php">Home</a>
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

              // Not yet run by anyone => show en dash
              if (!isset($completedTrack[$tid])) {
                  $display = "–";
                  $isDropped = false;

              } else {
                  if (isset($row['per_track'][$tid]) && $row['per_track'][$tid]['exists']) {
                      $pts = (int)$row['per_track'][$tid]['pts'];
                      $display = ($pts === 0) ? "DNS" : (string)$pts; // DNS only when we have a 0 row
                      $isDropped = ($tid === $row['drop_tid']);
                  } else {
                      // Event ran, but this driver has no row
                      $display = "0";
                      $isDropped = false;
                  }
              }
          ?>
            <td class="num<?php echo $isDropped ? ' dropped' : ''; ?>" data-track-id="<?php echo $tid; ?>">
              <?php echo htmlspecialchars($display); ?>
            </td>
          <?php endforeach; ?>

          <td class="num">
            <strong><?php echo (int)$row['total_after_drop']; ?></strong>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <p class="legend">
    <strong>Legend:</strong>
    <span>DNS = Did Not Start</span> ·
    <span>– = Event not yet run</span> ·
    <span class="pill">dropped</span> = lowest round removed
  </p>

  <!-- Optional tiny auto-refresh (every 30s). Kill it if you don’t want it. -->
  <script>
    setTimeout(() => location.reload(), 30000);
  </script>
</body>
</html>
<?php
/***** CLEANUP *****/
$resTracks->free();
$resDrivers->free();
$conn->close();
