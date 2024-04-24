<?php
session_start();
require_once 'includes/database-connection.php';

$error = '';
$question = '';
$userEmail = '';
$passwordShown = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['userEmail']) && empty($_POST['securityAnswer'])) {
    $userEmail = trim($_POST['userEmail']);

    // Fetch the user's security question from the database
    $sql = "SELECT s.questionID, q.questionText FROM security_answers s
            JOIN security_questions q ON s.questionID = q.questionID
            WHERE s.userID = (SELECT userID FROM user WHERE userEmail = ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userEmail]);
    $result = $stmt->fetch();

    if ($result) {
        $_SESSION['userEmail'] = $userEmail;
        $_SESSION['questionID'] = $result['questionID'];
        $question = $result['questionText'];
    } else {
        $error = "No user found with that email or no security question set.";
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['securityAnswer'], $_SESSION['userEmail'], $_SESSION['questionID'])) {
    $userEmail = $_SESSION['userEmail'];
    $questionID = $_SESSION['questionID'];
    $securityAnswer = trim($_POST['securityAnswer']);

    // Verify the security answer
    $sql = "SELECT answerText, password FROM security_answers sa
            JOIN user u ON sa.userID = u.userID
            WHERE sa.userID = (SELECT userID FROM user WHERE userEmail = ?) AND sa.questionID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userEmail, $questionID]);
    $result = $stmt->fetch();

    if ($result && $securityAnswer === $result['answerText']) {
        // Show the password
        $passwordShown = "Your password is: " . $result['password'];
    } else {
        $error = "Incorrect answer. Please try again.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width: 300px;
            margin: auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .input-group {
            margin-bottom: 20px;
        }
        .form-footer {
            margin-top: 20px;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 10px;
        }
        p {
            margin: 10px 0;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <?php if ($error): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>
        <form action="forgotpassword.php" method="post" id="forgotForm">
            <div class="input-group">
                <!-- Ensure placeholder is always set and readonly attribute is managed correctly -->
                <input type="email" name="userEmail" id="userEmail" value="<?= htmlspecialchars($userEmail) ?>" required placeholder="Email" <?= $question ? 'readonly' : '' ?>>
            </div>
            <?php if ($question): ?>
            <div class="input-group">
                <p><strong>Security Question:</strong> <?= htmlspecialchars($question) ?></p>
                <input type="text" name="securityAnswer" id="securityAnswer" required placeholder="Your Answer">
            </div>
            <?php endif; ?>
            <div>
                <button type="submit" class="btn"><?= $question ? 'Submit Answer' : 'Get Question' ?></button>
            </div>
        </form>
        <?php if ($passwordShown): ?>
            <script>
                document.getElementById('forgotForm').style.display = 'none'; // Hide the form
                document.getElementById('securityQuestionText').style.display = 'none'; // Hide the question
            </script>
            <p style="color: green;"><?= $passwordShown ?></p>
        <?php endif; ?>
        <div class="form-footer">
            <a href="index.php" style="text-decoration: underline;">Log in</a>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>





