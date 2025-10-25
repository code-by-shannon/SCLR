<?php
/***** CONFIG *****/
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "SCLR";

/***** CONNECT *****/
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) { die("DB connection failed: " . htmlspecialchars($conn->connect_error)); }

/***** Track param (e.g. ?track_id=52). Default to most recent in results. *****/
$trackId = (int)($_GET['track_id'] ?? 0);
if ($trackId === 0) {
  $rs = $conn->query("SELECT DISTINCT track_id FROM SCLR_WS_Fall_2025_Results ORDER BY track_id DESC LIMIT 1");
  if ($rs && $rs->num_rows) $trackId = (int)$rs->fetch_assoc()['track_id'];
  $rs && $rs->free();
}

/***** Track name *****/
$trackName = '';
if ($trackId > 0) {
  $stmt = $conn->prepare("SELECT course FROM tracks WHERE id = ?");
  $stmt->bind_param("i", $trackId);
  $stmt->execute();
  $stmt->bind_result($trackName);
  $stmt->fetch();
  $stmt->close();
}

/***** Results (driver names + points) *****/
$data = [];
if ($trackId > 0) {
  $sql = "
    SELECT d.name AS driver_name, r.points
    FROM SCLR_WS_Fall_2025_Results r
    JOIN Drivers d ON d.id = r.driver_id
    WHERE r.track_id = ?
    ORDER BY r.points DESC, d.name ASC
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $trackId);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) $data[] = $row;
  $stmt->close();
}

/***** Track switcher list (optional) *****/
$allTracks = $conn->query("
  SELECT DISTINCT r.track_id, t.course
  FROM SCLR_WS_Fall_2025_Results r
  JOIN tracks t ON t.id = r.track_id
  ORDER BY t.course ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars("SCLR Round 1 Results" . ($trackName ? " — $trackName" : "")); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root { --bg:#0b0c10; --card:#111317; --ink:#e6e9ef; --muted:#aab2bf; --hl:#2bb673; --line:#22252b; }
    * { box-sizing: border-box; }
    body { margin:0; padding:32px; font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
           color:var(--ink); background:linear-gradient(180deg,#0b0c10 0%,#0f1115 100%); }
    .wrap { max-width:900px; margin:0 auto; }
    .header { display:flex; flex-wrap:wrap; align-items:baseline; gap:12px; margin-bottom:20px; }
    h1 { margin:0; font-size:28px; letter-spacing:.2px; }
    .sub { color:var(--muted); font-size:14px; }
    .tag { display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px;
           background:rgba(43,182,115,.12); color:var(--hl); border:1px solid rgba(43,182,115,.35); }
    .card { background:var(--card); border:1px solid var(--line); border-radius:12px; overflow:hidden;
            box-shadow:0 8px 24px rgba(0,0,0,.35); }
    .toolbar { display:flex; gap:12px; align-items:center; padding:12px 16px; border-bottom:1px solid var(--line); }
    select,.btn { background:#0f1218; color:var(--ink); border:1px solid var(--line);
                  padding:8px 10px; border-radius:8px; font-size:14px; }
    .btn { cursor:pointer; }
    table { width:100%; border-collapse:collapse; }
    thead th { text-align:left; font-weight:600; padding:14px 16px; font-size:13px; color:var(--muted);
               background:#12151b; border-bottom:1px solid var(--line); }
    tbody td { padding:14px 16px; border-bottom:1px solid var(--line); font-size:15px; }
    tbody tr:hover { background:#0f1319; }
    .rank { width:64px; font-variant-numeric:tabular-nums; color:var(--muted); }
    .driver { font-weight:600; }
    .points { text-align:right; font-variant-numeric:tabular-nums; }
    .dnf { color:#ff7676; font-weight:600; }
    .empty { color:var(--muted); padding:20px; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>SCLR Round 1 Results</h1>
    <?php if ($trackName): ?>
      <span class="sub">Track: <span class="tag"><?php echo htmlspecialchars($trackName); ?></span></span>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="toolbar">
      <form method="get" action="">
        <label for="track_id">Switch track:</label>
        <select id="track_id" name="track_id">
          <?php if ($allTracks && $allTracks->num_rows): while ($t = $allTracks->fetch_assoc()): ?>
            <option value="<?php echo (int)$t['track_id']; ?>" <?php echo ((int)$t['track_id']===$trackId?'selected':''); ?>>
              <?php echo htmlspecialchars($t['course']); ?>
            </option>
          <?php endwhile; endif; ?>
        </select>
        <button class="btn" type="submit">View</button>
      </form>
    </div>

    <?php if (!$data): ?>
      <div class="empty">No results found yet for this track.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th class="rank">Pos</th>
            <th>Driver</th>
            <th class="points">Points</th>
          </tr>
        </thead>
        <tbody>
          <?php $rank = 1; foreach ($data as $row): ?>
            <tr>
              <td class="rank"><?php echo $rank++; ?></td>
              <td class="driver">
                <?php echo htmlspecialchars($row['driver_name']); ?>
                <?php if ((int)$row['points'] === 0): ?><span class="dnf"> (DNF)</span><?php endif; ?>
              </td>
              <td class="points"><?php echo (int)$row['points']; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
<?php
$allTracks && $allTracks->free();
$conn->close();

