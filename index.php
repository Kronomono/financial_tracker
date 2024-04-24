<?php
ob_start();       // Start output buffering
session_start();  // Start the session at the very beginning

// Check if the user is already logged in
if (isset($_SESSION['userid'])) {
    header('Location: dashboard.php');  // Redirect to user dashboard if already logged in
    exit();
}

// Include the database connection file
require_once 'includes/database-connection.php';

$error = '';  // Variable to store error messages

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['userEmail'], $_POST['password'])) {
    $userEmail = trim($_POST['userEmail']);
    $password = trim($_POST['password']);

    // SQL to check the existence of the user
    $sql = "SELECT userID, userEmail, password FROM user WHERE userEmail = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userEmail]);  // Execute the query

    // Check if the user exists
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch();

        // Verify the password against the hashed password in the database
        if (password_verify($password, $user['password'])) {
            $_SESSION['userid'] = $user['userID'];
            $_SESSION['userEmail'] = $user['userEmail'];
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid password.';
        }
    } else {
        $error = 'Invalid email.';
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .btn {
            margin-top: 10px; /* Adds vertical spacing above each button */
        }
        .form-footer {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="form-title">Login</h2>
        <?php if ($error != '') echo '<p style="color:red;">' . $error . '</p>'; ?>
        <form action="index.php" method="post" id="signIn">
            <div class="input-group">
                <input type="email" name="userEmail" id="userEmail" required placeholder="Email">
                <label for="userEmail">Email:</label>
            </div>
            <div class="input-group">
                <input type="password" name="password" id="password" required placeholder="Password">
                <label for="password">Password:</label>
            </div>
            <div>
                <button type="submit" class="btn">Login</button>
            </div>
            <div>
                <a href="signup.php" class="btn" id="signUpButton">Sign Up</a>
            </div>
            <div class="form-footer">
                <a href="forgotpassword.php" style="text-decoration: underline;">Forgot Password?</a>
            </div>
        </form>
    </div>
    <script src="script.js"></script>
</body>
</html>


