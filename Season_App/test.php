<?php
session_start();
$_SESSION['user_id'] = 1;
echo "Logged in as user_id = " . $_SESSION['user_id'];
