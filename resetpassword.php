<?php
ob_start();
session_start();
require_once 'includes/database-connection.php';

$error = '';
$success = '';

if (!isset($_SESSION['userEmail'])) {
    // Redirect user if they haven't passed through the forgot password flow
    header('Location: forgotpassword.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['newPassword'], $_POST['confirmPassword'])) {
    $newPassword = trim($_POST['newPassword']);
    $confirmPassword = trim($_POST['confirmPassword']);

    if ($newPassword === $confirmPassword) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE user SET password = ? WHERE userEmail = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$passwordHash, $_SESSION['userEmail']])) {
            $success = "Your password has been updated successfully.";
            unset($_SESSION['userEmail']); // Clear the session email after reset
        } else {
            $error = "Failed to update your password. Please try again.";
        }
    } else {
        $error = "Passwords do not match.";
    }
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if ($error): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color: green;"><?= $success ?></p>
        <?php endif; ?>
        <form action="resetpassword.php" method="post">
            <div class="input-group">
                <input type="password" name="newPassword" id="newPassword" required placeholder="New Password">
                <label for="newPassword">New Password:</label>
            </div>
            <div class="input-group">
                <input type="password" name="confirmPassword" id="confirmPassword" required placeholder="Confirm New Password">
                <label for="confirmPassword">Confirm New Password:</label>
            </div>
            <div>
                <button type="submit" class="btn">Reset Password</button>
            </div>
        </form>
        <div class="form-footer">
            <a href="index.php" style="text-decoration: underline;">Log in</a>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
