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
</body>
</html>
