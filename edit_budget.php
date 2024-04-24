<?php
// Start the session
session_start();
ob_start();

// Include the database connection file
require_once 'includes/database-connection.php';

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';
$userID = $_SESSION['userid'];
$budgetID = $_GET['budgetID'] ?? null;

if (!$budgetID) {
    header("Location: manage_budgets.php");
    exit();
}

// Fetch the budget to edit
$stmt = $pdo->prepare("SELECT * FROM budget WHERE budgetID = ? AND userID = ?");
$stmt->execute([$budgetID, $userID]);
$budget = $stmt->fetch();

if (!$budget) {
    header("Location: manage_budgets.php");
    exit();
}

// Fetch categories for the dropdown
$categoryStmt = $pdo->prepare("SELECT categoryID, categoryName FROM category");
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll();

// Handle the form submission for updating a budget
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_budget'])) {
    // Retrieve form data
    $categoryID = $_POST['categoryID'];
    $budgetLimit = $_POST['budgetLimit'];
    $budgetInterval = $_POST['budgetInterval'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];

    // Update the database
    $updateStmt = $pdo->prepare("UPDATE budget SET categoryID = ?, budgetLimit = ?, budgetInterval = ?, startDate = ?, endDate = ? WHERE budgetID = ? AND userID = ?");
    if ($updateStmt->execute([$categoryID, $budgetLimit, $budgetInterval, $startDate, $endDate, $budgetID, $userID])) {
        $success = 'Budget updated successfully!';
    } else {
        $error = 'Failed to update budget.';
    }
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Budget</title>
    <!-- Your CSS file link -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<h1>Edit Budget</h1>
    <a href="manage_budgets.php">Back to Budgets</a>
    
    <?php if ($error): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color: green;"><?= $success ?></p>
    <?php endif; ?>

    <!-- Form to edit an existing budget -->
    <h2>Edit Budget</h2>
    <form action="edit_budget.php?budgetID=<?= $budgetID ?>" method="post">
        <label for="categoryID">Category:</label>
        <select id="categoryID" name="categoryID" required>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['categoryID'] ?>" <?= $budget['categoryID'] == $category['categoryID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['categoryName']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="budgetLimit">Budget Limit:</label>
        <input type="number" id="budgetLimit" name="budgetLimit" value="<?= $budget['budgetLimit'] ?>" required>

        <label for="budgetInterval">Budget Interval:</label>
        <select id="budgetInterval" name="budgetInterval" required>
            <option value="Weekly" <?= $budget['budgetInterval'] == 'Weekly' ? 'selected' : '' ?>>Weekly</option>
            <option value="Monthly" <?= $budget['budgetInterval'] == 'Monthly' ? 'selected' : '' ?>>Monthly</option>
            <option value="Annual" <?= $budget['budgetInterval'] == 'Annual' ? 'selected' : '' ?>>Annual</option>
        </select>

        <label for="startDate">Start Date:</label>
        <input type="date" id="startDate" name="startDate" value="<?= $budget['startDate'] ?>" required>

        <label for="endDate">End Date:</label>
        <input type="date" id="endDate" name="endDate" value="<?= $budget['endDate'] ?>" required>

        <input type="hidden" name="budgetID" value="<?= $budgetID ?>">
        <button type="submit" name="update_budget">Update Budget</button>
    </form>
</body>
</html>