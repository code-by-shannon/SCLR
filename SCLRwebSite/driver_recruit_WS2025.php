<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['season.name'])) {
  die('No active season in session. Go back to Step 1.');
}

$season_table_base = preg_replace('/[^a-zA-Z0-9_]/', '_', $_SESSION['season.name']);
$season_drivers_table = $season_table_base . '_drivers';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $driver_ids = $_POST['driver_ids'] ?? [];
  // sanitize to ints and dedupe
  $driver_ids = array_values(array_unique(array_map('intval', $driver_ids)));
  if (count($driver_ids) < 1) $errors[] = "Select at least one driver.";

  if (empty($errors)) {
    $_SESSION['season.drivers'] = $driver_ids;

    $conn = connectToDB();

    // Create/ensure per-season drivers table
    $sql = "CREATE TABLE IF NOT EXISTS `$season_drivers_table` (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              driver_id INT UNSIGNED NOT NULL,
              UNIQUE(driver_id)
            ) ENGINE=InnoDB";
    if (!$conn->query($sql)) {
      die('Create drivers table failed: ' . htmlspecialchars($conn->error));
    }

    // Insert selections (IGNORE avoids dupes if user reloads)
    $stmt = $conn->prepare("INSERT IGNORE INTO `$season_drivers_table` (driver_id) VALUES (?)");
    foreach ($driver_ids as $did) {
      $stmt->bind_param('i', $did);
      $stmt->execute();
    }
    $stmt->close();
    $conn->close();

    // Simple success note
    echo "<h2>Drivers saved for season: " . htmlspecialchars($_SESSION['season.name']) . "</h2>";
    echo "<p>Table <code>" . htmlspecialchars($season_drivers_table) . "</code> updated.</p>";
    echo "<pre>" . htmlspecialchars(json_encode($_SESSION['season.drivers'], JSON_PRETTY_PRINT)) . "</pre>";
    exit;
  }
}

// Fetch all drivers for dropdown
$conn = connectToDB();
$res = $conn->query("SELECT id, name FROM drivers ORDER BY name ASC");
if (!$res) die('Drivers query failed: ' . htmlspecialchars($conn->error));
$drivers = $res->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Recruit Drivers – <?= htmlspecialchars($_SESSION['season.name']) ?></title>
</head>
<body>
  <h2>Step 2: Recruit Drivers for <?= htmlspecialchars($_SESSION['season.name']) ?></h2>

  <?php if (!empty($errors)): ?>
    <ul style="color:red;">
      <?php foreach($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="POST">
    <label>Select Driver:</label><br>
    <select id="driverSelect">
      <?php foreach ($drivers as $d): ?>
        <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['name'], ENT_QUOTES) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" onclick="addDriver()">Add Driver</button>

    <h3>Selected Drivers (click to remove)</h3>
    <ol id="selectedDrivers"></ol>

    <div id="hiddenContainer"></div>
    <br>
    <button type="submit">Save Drivers →</button>
  </form>

<script>
const select = document.getElementById('driverSelect');
const list = document.getElementById('selectedDrivers');
const hidden = document.getElementById('hiddenContainer');

function addDriver() {
  const id = select.value;
  const name = select.options[select.selectedIndex].text;

  // prevent duplicates
  if ([...list.children].some(li => li.dataset.id === id)) return;

  const li = document.createElement('li');
  li.dataset.id = id;
  li.textContent = name;
  li.title = 'Click to remove';
  li.onclick = () => { li.remove(); rebuildHidden(); };
  list.appendChild(li);
  rebuildHidden();
}

function rebuildHidden() {
  hidden.innerHTML = '';
  [...list.children].forEach(li => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'driver_ids[]';
    input.value = li.dataset.id;
    hidden.appendChild(input);
  });
}

document.querySelector('form').addEventListener('submit', rebuildHidden);
</script>
</body>
</html>
