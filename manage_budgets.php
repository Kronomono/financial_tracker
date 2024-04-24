<?php
ob_start(); // Start output buffering
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'includes/database-connection.php';

if (!isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}

$userID = $_SESSION['userid'];
$editing = false;
$editBudget = null;

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header('Location: index.php');
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['budgetID'])) {
    $editing = true;
    $editBudgetID = $_GET['budgetID'];
    $editStmt = $pdo->prepare("SELECT * FROM budget WHERE budgetID = ? AND userID = ?");
    $editStmt->execute([$editBudgetID, $userID]);
    $editBudget = $editStmt->fetch();
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['budgetID'])) {
    $budgetID = $_GET['budgetID'];
    $stmt = $pdo->prepare("DELETE FROM budget WHERE budgetID = ? AND userID = ?");
    if ($stmt->execute([$budgetID, $userID])) {
        $_SESSION['message'] = "Budget deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting budget.";
    }
    header("Location: manage_budgets.php");
    exit();
}

$categoryStmt = $pdo->prepare("SELECT categoryID, categoryName FROM category");
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_budget'])) {
    $categoryID = $_POST['categoryID'];
    $budgetLimit = $_POST['budgetLimit'];
    $budgetInterval = $_POST['budgetInterval'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    if (isset($_POST['budgetID'])) {  // Existing budget update
        $budgetID = $_POST['budgetID'];
        $updateStmt = $pdo->prepare("UPDATE budget SET categoryID = ?, budgetLimit = ?, budgetInterval = ?, startDate = ?, endDate = ? WHERE budgetID = ? AND userID = ?");
        $updateStmt->execute([$categoryID, $budgetLimit, $budgetInterval, $startDate, $endDate, $budgetID, $userID]);
    } else {  // New budget
        $stmt = $pdo->prepare("INSERT INTO budget (userID, categoryID, budgetLimit, budgetInterval, startDate, endDate) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userID, $categoryID, $budgetLimit, $budgetInterval, $startDate, $endDate]);
    }
    header("Location: manage_budgets.php");
    exit();
}

$budgetsStmt = $pdo->prepare("SELECT b.budgetID, c.categoryName, b.budgetLimit, b.budgetInterval, b.startDate, b.endDate FROM budget b INNER JOIN category c ON b.categoryID = c.categoryID WHERE b.userID = ?");
$budgetsStmt->execute([$userID]);
$budgets = $budgetsStmt->fetchAll();
ob_end_flush(); // End output buffering and flush all output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Budgets</title>
</head>
<body>
<h1>Manage Budgets</h1>
<a href="dashboard.php">Back to Dashboard</a>

<!-- Editing or Adding Form -->
<h2><?= $editing ? "Edit Budget" : "Add New Budget" ?></h2>
<form action="manage_budgets.php" method="post">
    <input type="hidden" name="budgetID" value="<?= $editing ? $editBudget['budgetID'] : '' ?>">
    <label for="categoryID">Category:</label>
    <select id="categoryID" name="categoryID" required>
        <?php foreach ($categories as $category): ?>
            <option value="<?= htmlspecialchars($category['categoryID']) ?>" <?= $editing && $category['categoryID'] == $editBudget['categoryID'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($category['categoryName']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <label for="budgetLimit">Budget Limit:</label>
    <input type="number" id="budgetLimit" name="budgetLimit" required value="<?= $editing ? $editBudget['budgetLimit'] : '' ?>">
    <label for="budgetInterval">Budget Interval:</label>
    <select id="budgetInterval" name="budgetInterval" required>
        <option value="Weekly" <?= $editing && $editBudget['budgetInterval'] == 'Weekly' ? 'selected' : '' ?>>Weekly</option>
        <option value="Monthly" <?= $editing && $editBudget['budgetInterval'] == 'Monthly' ? 'selected' : '' ?>>Monthly</option>
        <option value="Annual" <?= $editing && $editBudget['budgetInterval'] == 'Annual' ? 'selected' : '' ?>>Annual</option>
    </select>
    <label for="startDate">Start Date:</label>
    <input type="date" id="startDate" name="startDate" required value="<?= $editing ? $editBudget['startDate'] : '' ?>">
    <label for="endDate">End Date:</label>
    <input type="date" id="endDate" name="endDate" required value="<?= $editing ? $editBudget['endDate'] : '' ?>">
    <button type="submit" name="submit_budget"><?= $editing ? 'Update Budget' : 'Add Budget' ?></button>
</form>

<!-- Table to list all budgets -->
<h2>Your Budgets</h2>
<table>
    <thead>
        <tr>
            <th>Category</th>
            <th>Budget Limit</th>
            <th>Budget Interval</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($budgets as $budget): ?>
        <tr>
            <td><?= htmlspecialchars($budget['categoryName']) ?></td>
            <td>$<?= htmlspecialchars(number_format($budget['budgetLimit'], 2)) ?></td>
            <td><?= htmlspecialchars($budget['budgetInterval']) ?></td>
            <td><?= htmlspecialchars($budget['startDate']) ?></td>
            <td><?= htmlspecialchars($budget['endDate']) ?></td>
            <td>
                <a href="manage_budgets.php?action=edit&budgetID=<?= $budget['budgetID'] ?>">Edit</a> |
                <a href="manage_budgets.php?action=delete&budgetID=<?= $budget['budgetID'] ?>" onclick="return confirm('Are you sure you want to delete this budget?');">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>