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

/***** TRACKS (radio list) *****/
$sqlTracks = "
  SELECT DISTINCT t.id AS track_id, t.course AS track_name
  FROM January_Sprint_2026_No_BOP s
  JOIN tracks t ON s.track_id = t.id
  ORDER BY t.course ASC
";
$tracks = $conn->query($sqlTracks);
if (!$tracks) die('Tracks query failed: ' . htmlspecialchars($conn->error));

/***** DRIVERS for this season *****/
$sqlDrivers = "
  SELECT d.id, d.name
  FROM January_Sprint_2026_No_BOP_drivers sd
  JOIN Drivers d ON d.id = sd.driver_id
  ORDER BY d.name ASC
";

$drivers = $conn->query($sqlDrivers);
if (!$drivers) die('Drivers query failed: ' . htmlspecialchars($conn->error));
$driverOptions = $drivers->fetch_all(MYSQLI_ASSOC);

/***** POSITIONS / POINTS (1..16 only) *****/
$sqlPoints = "
  SELECT position, points
  FROM points_system
  WHERE position <> 'DNF'
  ORDER BY CAST(position AS UNSIGNED)
";
$positions = $conn->query($sqlPoints);
if (!$positions) die('Points query failed: ' . htmlspecialchars($conn->error));

function ordinal_label($pos) {
  $n = (int)$pos;
  $suffix = 'th';
  if (!in_array($n % 100, [11,12,13], true)) {
    $last = $n % 10;
    if ($last === 1) $suffix = 'st';
    elseif ($last === 2) $suffix = 'nd';
    elseif ($last === 3) $suffix = 'rd';
  }
  return $n . $suffix;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Results Entry – WS Fall 2025</title>
  <style>
    body { font-family: system-ui, sans-serif; line-height: 1.4; max-width: 800px; margin: 2rem auto; }
    .row { display: flex; align-items: center; gap: 12px; margin: 6px 0; }
    .label { width: 90px; text-align: right; font-weight: 600; }
    select { min-width: 260px; }
    .section { margin: 20px 0; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
  </style>
</head>
<body>
  <a href="index.php">Home</a>
  <h1>Enter Race Results</h1>

  <form method="post" action="save_results.php">

    <!-- TRACK PICKER -->
    <div class="section">
      <h2>1) Choose the track</h2>
      <?php if ($tracks->num_rows === 0): ?>
        <p>No tracks found.</p>
      <?php else: ?>
        <?php while ($row = $tracks->fetch_assoc()): ?>
          <div>
            <label>
              <input type="radio" name="track_id" value="<?php echo (int)$row['track_id']; ?>">
              <?php echo htmlspecialchars($row['track_name']); ?>
            </label>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>

    <!-- POSITIONS & DRIVER DROPDOWNS -->
    <div class="section">
      <h2>2) Assign positions to drivers</h2>

      <?php if ($positions->num_rows === 0): ?>
        <p>No positions found.</p>
      <?php else: ?>
        <?php while ($p = $positions->fetch_assoc()): ?>
          <?php
            $posVal = $p['position'];
            $label  = ordinal_label($posVal);
          ?>
          <div class="row">
            <div class="label"><?php echo htmlspecialchars($label); ?></div>
            <select class="driver-select" name="results[<?php echo $posVal; ?>]">
              <option value="">— Select driver —</option>
              <?php foreach ($driverOptions as $d): ?>
                <option value="<?php echo (int)$d['id']; ?>">
                  <?php echo htmlspecialchars($d['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span>(<?php echo (int)$p['points']; ?> pts)</span>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>

    <button type="submit">Submit</button>
  </form>

  <!-- ✅ DRIVER LOCKOUT SCRIPT -->
  <script>
    const selects = document.querySelectorAll('.driver-select');

    function updateDropdowns() {
      const chosen = new Set();
      selects.forEach(sel => {
        if (sel.value) chosen.add(sel.value);
      });

      selects.forEach(sel => {
        [...sel.options].forEach(opt => {
          if (opt.value === "") return;
          if (chosen.has(opt.value) && opt.value !== sel.value) {
            opt.disabled = true;
            opt.style.color = "#bbb";
          } else {
            opt.disabled = false;
            opt.style.color = "";
          }
        });
      });
    }

    selects.forEach(sel => sel.addEventListener('change', updateDropdowns));
  </script>
</body>
</html>
<?php
/***** CLEANUP *****/
$tracks->free();
$drivers->free();
$positions->free();
$conn->close();
?>
