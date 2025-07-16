<?php
session_start();
if (!($_SESSION['logged_in'] ?? false)) {
    header("Location: login.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCLR</title>
</head>
<body>
    <h1>SCLR</h1>
<!-- Driver Entry Form -->
    <form action="add_driver.php" method="post">
    <label for="driver_name">Driver Name:</label>
    <input type="text" id="driver_name" name="driver_name" required>
    <button type="submit">Add Driver</button>
</form>

</body>
</html>

