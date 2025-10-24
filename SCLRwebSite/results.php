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

/***** QUERY: get track names instead of IDs *****/
/*
   This assumes:
   - your table with race data is called `SCLR_world_series_fall_2025`
   - your track table is called `tracks`
   - `tracks` has columns `id` and `name`
*/
$sql = "
  SELECT DISTINCT t.id AS track_id, t.course AS track_name
  FROM SCLR_world_series_fall_2025 s
  JOIN tracks t ON s.track_id = t.id
  ORDER BY t.course ASC
";
$result = $conn->query($sql);
if (!$result) {
    die('Query failed: ' . htmlspecialchars($conn->error));
}

/***** RENDER HTML *****/
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Tracks for WS Fall 2025</title>
</head>
<body>
  <h1>Choose a Track</h1>

  <form method="post" action="#">
    <?php if ($result->num_rows === 0): ?>
      <p>No tracks found.</p>
    <?php else: ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <div>
          <label>
            <input type="radio" name="track_ids[]" value="<?php echo $row['track_id']; ?>">
            <?php echo htmlspecialchars($row['track_name']); ?>
          </label>
        </div>
      <?php endwhile; ?>
      <button type="submit">Submit</button>
    <?php endif; ?>
  </form>
</body>
</html>
<?php
/***** CLEANUP *****/
$result->free();
$conn->close();
