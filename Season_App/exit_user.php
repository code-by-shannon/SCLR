<?php
session_start();
session_unset();
session_destroy();

header("Location: /sclr/Season_App/choose_user.php");
exit;
