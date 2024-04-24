<?php

ob_start(); // Start output buffering
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}

// Include the database connection file
require_once 'includes/database-connection.php';

$accountID = $_GET['accountID'] ?? null; // Get the accountID from the URL
// Function to add a new transaction
function addTransaction($accountID, $description, $amount, $date, $type, $category) {
    global $pdo;

    $transactionAmount = ($type === 'Expense') ? -$amount : $amount;

    $stmt = $pdo->prepare("INSERT INTO transactions (transactionDescription, transactionAmount, transactionDate, transactionType, accountID, categoryID) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$description, $transactionAmount, $date, $type, $accountID, $category]);

    // Update account balance
    updateAccountBalance($accountID, $transactionAmount);

    // Redirect to account details page
    header('Location: account_details.php?accountID=' . $accountID);
    exit();
}

// Function to update account balance
function updateAccountBalance($accountID, $transactionAmount) {
    global $pdo;

    // Fetch the initial balance from the account table
    $balanceStmt = $pdo->prepare("SELECT accountBalance FROM account WHERE accountID = ?");
    $balanceStmt->execute([$accountID]);
    $initialBalance = $balanceStmt->fetchColumn() ?: 0; // If null, default to 0

    // Calculate new balance
    $newBalance = $initialBalance + $transactionAmount;

    // Update the account balance in the account table
    $updateStmt = $pdo->prepare("UPDATE account SET accountBalance = ? WHERE accountID = ?");
    $updateStmt->execute([$newBalance, $accountID]);
}

// Function to delete a transaction
function deleteTransaction($accountID, $transactionID) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT transactionAmount, transactionType FROM transactions WHERE transactionID = ?");
    $stmt->execute([$transactionID]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $transactionAmount = $result['transactionAmount'];
    $transactionType = $result['transactionType'];

    // Update account balance
    updateAccountBalance($accountID, ($transactionType === 'Expense') ? abs($transactionAmount) : -$transactionAmount);

    // Delete the transaction
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE transactionID = ?");
    $stmt->execute([$transactionID]);

    // Redirect to account details page
    header('Location: account_details.php?accountID=' . $accountID);
    exit();
}
// Fetch transactions for the selected account
function fetchTransactions($accountID, $search = '', $sort = 'transactionDate', $order = 'DESC') {
    global $pdo;

    $transactionsStmt = $pdo->prepare("SELECT t.transactionID, t.transactionDescription, t.transactionAmount, t.transactionDate, t.transactionType, c.categoryName FROM transactions t LEFT JOIN category c ON t.categoryID = c.categoryID WHERE t.accountID = ? AND (t.transactionDescription LIKE ? OR t.transactionAmount LIKE ?) ORDER BY $sort $order");
    $transactionsStmt->execute([$accountID, '%' . $search . '%', '%' . $search . '%']);
    return $transactionsStmt->fetchAll();
}

// Fetch the current balance from the account table
function fetchCurrentBalance($accountID) {
    global $pdo;

    $balanceStmt = $pdo->prepare("SELECT accountBalance FROM account WHERE accountID = ?");
    $balanceStmt->execute([$accountID]);
    return $balanceStmt->fetchColumn();
}

if (isset($_POST['add'])) {
    addTransaction($_GET['accountID'], $_POST['description'], $_POST['amount'], $_POST['date'], $_POST['type'], $_POST['category']);
}

if (isset($_GET['delete'])) {
    deleteTransaction($_GET['accountID'], $_GET['delete']);
}


// Handle POST requests for updating transactions
if (isset($_POST['update'])) {
    $stmt = $pdo->prepare("UPDATE transactions SET transactionDescription = ?, transactionAmount = ?, transactionDate = ?, transactionType = ?, categoryID = ? WHERE transactionID = ?");
    $stmt->execute([$_POST['description'], $_POST['amount'], $_POST['date'], $_POST['type'], $_POST['categoryID'], $_POST['transactionID']]);



    header('Location: account_details.php?accountID=' . $accountID);
    exit();
}



// Fetch the account details for the selected account
$search = $_POST['search'] ?? '';
$sort = $_GET['sort'] ?? 'transactionDate';
$order = $_GET['order'] ?? 'DESC';

$transactions = fetchTransactions($accountID, $search, $sort, $order);
$currentBalance = fetchCurrentBalance($accountID);

ob_end_flush(); // End output buffering and flush all output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Budgets</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
            color: #333;
        }
        h1, h2 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        form {
            background-color: #fff;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #ccc;
        }
        label, input, select, button {
            display: block;
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }
        button {
            background-color: #5cb85c;
            color: white;
            border: none;
            padding: 10px 20px;
            margin-top: 10px;
            cursor: pointer;
        }
        button:hover {
            background-color: #4cae4c;
        }
        a {
            color: #337ab7;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
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
