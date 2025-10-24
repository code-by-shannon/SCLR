<?php
// ---- CONFIG ----
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "SCLR"; // your schema name

// ---- CONNECT ----
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$errors = [];
$notice = "";

// ---- HANDLE CREATE (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newDriver = trim($_POST['driver_name'] ?? '');

    if ($newDriver === '') {
        $errors[] = "Driver name can't be empty.";
    } elseif (mb_strlen($newDriver) > 100) {
        $errors[] = "Driver name is too long (max 100).";
    }

    if (!$errors) {
        $stmt = $conn->prepare("INSERT INTO `Drivers` (`name`) VALUES (?)");
        if (!$stmt) {
            $errors[] = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("s", $newDriver);
            if ($stmt->execute()) {
                $notice = "✅ Added driver: " . htmlspecialchars($newDriver);
            } else {
                $errors[] = "Insert failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// ---- FETCH DRIVERS ----
$result = $conn->query("SELECT `id`, `name` FROM `Drivers` ORDER BY `name` ASC");
$drivers = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $drivers[] = $row;
    }
    $result->free();
} else {
    $errors[] = "Query failed: " . $conn->error;
}

// ---- FETCH WORLD SERIES ROUNDS WITH TRACK NAMES ----
$seasonRows = [];
$seasonQ = "
  SELECT w.id, w.round, w.track_id, t.course AS track_name
  FROM SCLR_World_Series_Fall_2025 AS w
  LEFT JOIN tracks AS t ON w.track_id = t.id
  ORDER BY w.round ASC, w.id ASC
";
if ($res2 = $conn->query($seasonQ)) {
    while ($row = $res2->fetch_assoc()) {
        $seasonRows[] = $row;
    }
    $res2->free();
} else {
    $errors[] = "World Series query failed: " . $conn->error;
}

// ---- FETCH POINTS SYSTEM (no join) ----
$pointsRows = [];
$pointsQ = "
  SELECT id, position, points
  FROM points_system
  ORDER BY position ASC
";
if ($res3 = $conn->query($pointsQ)) {
    while ($row = $res3->fetch_assoc()) $pointsRows[] = $row;
    $res3->free();
} else {
    $errors[] = "Points System query failed: " . $conn->error;
}




$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Drivers</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
  body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 2rem; }
  h1 { margin-bottom: .5rem; }
  .msg { margin: .5rem 0; padding: .5rem .75rem; border-radius: .5rem; }
  .ok { background:#eef8ee; border:1px solid #b9e3b9; }
  .err { background:#fff0f0; border:1px solid #ffb3b3; }
  form { margin: 1rem 0 2rem; display: flex; gap:.5rem; flex-wrap: wrap; align-items: center; }
  input[type="text"] { padding: .5rem .6rem; min-width: 260px; }
  button { padding:.55rem .9rem; cursor:pointer; }
  table { border-collapse: collapse; width: 100%; max-width: 700px; }
  th, td { border: 1px solid #ddd; padding: .6rem .7rem; text-align: left; }
  th { background: #f6f6f6; }
  .muted { color:#666; font-size:.9rem; }
</style>
</head>
<body>
  <a href="index.php">Home</a>
  <h1>Drivers</h1>
  <p class="muted">DB: <strong>SCLR</strong> · Table: <strong>Drivers</strong></p>

  <?php if ($notice): ?>
    <div class="msg ok"><?= $notice ?></div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="msg err">
      <?php foreach ($errors as $e): ?>
        <div>❌ <?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Add Driver -->
  <form method="post" autocomplete="off">
    <label for="driver_name">Add a driver:</label>
    <input type="text" id="driver_name" name="driver_name" maxlength="100" required />
    <button type="submit">Add</button>
  </form>

  <!-- Drivers Table -->
  <table>
    <thead>
      <tr>
        <th style="width:90px;">ID</th>
        <th>Driver</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($drivers) === 0): ?>
        <tr><td colspan="2"><em>No drivers yet.</em></td></tr>
      <?php else: ?>
        <?php foreach ($drivers as $d): ?>
          <tr>
            <td><?= (int)$d['id'] ?></td>
            <td><?= htmlspecialchars($d['name']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <h2 style="margin-top:2rem;">World Series (Fall 2025)</h2>
<p class="muted">Table: <strong>SCLR_World_Series_Fall_2025</strong> joined with <strong>tracks</strong></p>
<table>
  <thead>
    <tr>
      <th style="width:90px;">ID</th>
      <th>Round</th>
      <th>Track ID</th>
      <th>Track Name</th>
    </tr>
  </thead>
  <tbody>
    <?php if (count($seasonRows) === 0): ?>
      <tr><td colspan="4"><em>No rounds found.</em></td></tr>
    <?php else: ?>
      <?php foreach ($seasonRows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['round']) ?></td>
          <td><?= htmlspecialchars($r['track_id']) ?></td>
          <td><?= htmlspecialchars($r['track_name'] ?? 'Unknown') ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<h2 style="margin-top:2rem;">Points System</h2>
<p class="muted">Table: <strong>points_system</strong></p>
<table>
  <thead>
    <tr>
      <th style="width:90px;">ID</th>
      <th>Position</th>
      <th>Points</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$pointsRows): ?>
      <tr><td colspan="3"><em>No points rules found.</em></td></tr>
    <?php else: foreach ($pointsRows as $p): ?>
      <tr>
        <td><?= (int)$p['id'] ?></td>
        <td><?= htmlspecialchars($p['position']) ?></td>
        <td><?= htmlspecialchars($p['points']) ?></td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>


</body>
</html>
