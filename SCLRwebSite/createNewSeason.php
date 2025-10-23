<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';


$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 1) {
    $season_name = trim($_POST['season_name'] ?? '');
    $track_order = $_POST['track_order'] ?? [];

    if ($season_name === '') $errors[] = "Please enter a season name.";
    if (count($track_order) < 1) $errors[] = "Select at least one track.";
    if (count($track_order) !== count(array_unique($track_order))) $errors[] = "Duplicate tracks not allowed.";

    if (empty($errors)) {
        $_SESSION['season.name'] = $season_name;
        $_SESSION['season.tracks'] = [];
        $round = 1;
        foreach ($track_order as $track_id) {
            $_SESSION['season.tracks'][] = ['round' => $round++, 'track_id' => (int)$track_id];
        }
        header("Location: createNewSeason_drivers.php");
exit;

    }
}

/** --- mysqli version --- */
$conn = connectToDB();
$sql = "SELECT id, course, layout FROM tracks ORDER BY course ASC";
$result = $conn->query($sql);
if (!$result) {
    die('Tracks query failed: ' . htmlspecialchars($conn->error));
}
$tracks = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create New Season - Step 1</title>
</head>
<body>
  <a href="index.php">Home</a>
  <h2>Step 1: Select Tracks & Order</h2>

  <?php if (!empty($errors)): ?>
    <ul style="color:red;">
      <?php foreach($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="POST">
    <label>Season Name:</label><br>
    <input type="text" name="season_name" value="<?= htmlspecialchars($_SESSION['season.name'] ?? '', ENT_QUOTES) ?>" required><br><br>

    <label>Select Track:</label><br>
    <select name="track_id" id="trackSelect">
      <?php foreach ($tracks as $track): ?>
        <option value="<?= (int)$track['id'] ?>"><?= htmlspecialchars($track['course'] . ' - ' . $track['layout'], ENT_QUOTES) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" onclick="addTrack()">Add Track</button>

    <h3>Selected Tracks</h3>
<ol id="selectedTracks"></ol>

<div id="hiddenContainer"></div>  <!-- holds the track_order[] inputs -->
<br>
<button type="submit">Next →</button>

  </form>

  <script>
  const select = document.getElementById('trackSelect');
  const list = document.getElementById('selectedTracks');
  const hiddenContainer = document.getElementById('hiddenContainer');

  function addTrack() {
    const id = select.value;
    const name = select.options[select.selectedIndex].text;

    // prevent duplicates
    if ([...list.children].some(li => li.dataset.id === id)) return;

    const li = document.createElement('li');
    li.textContent = name;
    li.dataset.id = id;
    li.onclick = () => { li.remove(); rebuildHidden(); };
    list.appendChild(li);
    rebuildHidden();
  }

  function rebuildHidden() {
    hiddenContainer.innerHTML = '';
    [...list.children].forEach(li => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'track_order[]';
      input.value = li.dataset.id;
      hiddenContainer.appendChild(input);
    });
  }

  document.querySelector('form').addEventListener('submit', rebuildHidden);
</script>

</body>
</html>


