<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once'./db.php';

$conn = connectToDB();


$mode = $_GET['mode'] ?? 'existing';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');

    if ($username === '') {
        $error = 'Username required.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Existing user found
            $_SESSION['user_id'] = $user['id'];
            header("Location: index.php");
            exit;
        }

        if ($mode === 'new') {
            // Create new user
            $stmt = $conn->prepare("INSERT INTO users (username) VALUES (?)");
            $stmt->bind_param("s", $username);
            $stmt->execute();

            $_SESSION['user_id'] = $conn->insert_id;
            header("Location: index.php");
            exit;
        }

        $error = 'User not found. Try again or create a new league.';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SCLR – Choose User</title>
</head>
<body>

<h1><?= ($mode === 'new') ? 'Create Your League' : 'Continue Your League' ?></h1>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
    <label>
        Username:
        <input type="text" name="username" required>
    </label>
    <br><br>
    <button type="submit">Continue</button>
</form>

</body>
</html>


