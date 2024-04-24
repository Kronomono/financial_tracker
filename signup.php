<?php
// Start the session and output buffering
session_start();
ob_start();

// Include the database connection file
require_once 'includes/database-connection.php';

$error = '';  // Variable to store error messages
$success = '';  // Variable to store success message

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['firstName'], $_POST['lastName'], $_POST['userEmail'], $_POST['phoneNumber'], $_POST['password'], $_POST['securityQuestion'], $_POST['securityAnswer'])) {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $userEmail = trim($_POST['userEmail']);
    $phoneNumber = trim($_POST['phoneNumber']);
    $password = trim($_POST['password']);
    $securityQuestion = trim($_POST['securityQuestion']);
    $securityAnswer = trim($_POST['securityAnswer']);

    // SQL to check if the email already exists
    $sql = "SELECT userID FROM user WHERE userEmail = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userEmail]);

    if ($stmt->rowCount() > 0) {
        $error = 'Email already exists.';
    } else {
        // Insert new user into the database including security question and answer
        $sql = "INSERT INTO user (firstName, lastName, userEmail, phoneNumber, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $inserted = $stmt->execute([$firstName, $lastName, $userEmail, $phoneNumber, $password]);

        if ($inserted) {
            // Assuming user_id is automatically generated from an AUTO_INCREMENT column
            $userID = $pdo->lastInsertId();
            $sql = "INSERT INTO security_answers (userID, questionID, answerText) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userID, $securityQuestion, $securityAnswer]);

            $success = 'User registered successfully!';
            // Optionally redirect to login or dashboard page
            // header('Location: index.php');
        } else {
            $error = 'Failed to register user. Please try again.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Sign Up</h2>
        <?php
        if ($error != '') {
            echo '<p style="color:red;">' . $error . '</p>';
        }
        if ($success != '') {
            echo '<p style="color:green;">' . $success . '</p>';
        }
        ?>
        <form action="signup.php" method="post">
            <div class="input-group">
                <input type="text" name="firstName" id="firstName" required placeholder="First Name">
                <label for="firstName">First Name:</label>
            </div>
            <div class="input-group">
                <input type="text" name="lastName" id="lastName" required placeholder="Last Name">
                <label for="lastName">Last Name:</label>
            </div>
            <div class="input-group">
                <input type="email" name="userEmail" id="userEmail" required placeholder="Email">
                <label for="userEmail">Email:</label>
            </div>
            <div class="input-group">
                <input type="text" name="phoneNumber" id="phoneNumber" placeholder="Phone Number">
                <label for="phoneNumber">Phone Number:</label>
            </div>
            <div class="input-group">
                <input type="password" name="password" id="password" required placeholder="Password">
                <label for="password">Password:</label>
            </div>
            <div class="input-group">
                <label for="securityQuestion">Security Question:</label>
                <select name="securityQuestion" id="securityQuestion" required>
                    <option value="">Select a security question</option>
                    <option value="1">What is your mother's maiden name?</option>
                    <option value="2">What was the name of your first pet?</option>
                    <option value="3">What was the make of your first car?</option>
                    <option value="4">What is the name of the town where you were born?</option>
                    <option value="5">What is your favorite movie?</option>
                    <option value="6">What was the name of your elementary school?</option>
                    <option value="7">In what city did you meet your spouse/significant ...</option>
                    <option value="8">What is your favorite color?</option>
                    <option value="9">What street did you live on in third grade?</option>
                    <option value="10">What was your childhood nickname?</option>

                </select>

            </div>
            <div class="input-group">
                <input type="text" name="securityAnswer" id="securityAnswer" required placeholder="Answer">
                <label for="securityAnswer">Your Answer:</label>
            </div>
            <div>
                <button type="submit" class="btn">Register</button>
            </div>
            <div class="form-footer">
                <p>Already have an account? <a href="index.php" style="text-decoration: underline;">Login</a></p>
            </div>
        </form>
    </div>
    <script src="script.js"></script>
</body>
</html>
