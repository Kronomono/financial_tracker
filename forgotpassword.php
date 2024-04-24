<?php
session_start();
ob_start();
require_once 'includes/database-connection.php';

$error = '';
$question = '';
$userEmail = '';

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
    $sql = "SELECT answerText FROM security_answers WHERE userID = (SELECT userID FROM user WHERE userEmail = ?) AND questionID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userEmail, $questionID]);
    $result = $stmt->fetch();

    if ($result && $securityAnswer === $result['answerText']) {
        // Redirect to a password reset page or allow inline password reset
        header('Location: resetpassword.php'); // Assume resetpassword.php is your password reset page
        exit();
    } else {
        $error = "Incorrect answer. Please try again.";
    }
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <?php if ($error): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>
        <form action="forgotpassword.php" method="post" id="forgotForm">
            <div class="input-group">
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
        <div class="form-footer">
            <a href="index.php" style="text-decoration: underline;">Log in</a>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
