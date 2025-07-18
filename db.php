<?php
function connectToDB() {
    $servername = "localhost";
    $username = "root";
    $password = ""; // XAMPP default; change if you set one
    $dbname = "SCLR"; // or whatever your database is actually called

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("❌ Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
?>
