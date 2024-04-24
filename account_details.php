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
    <title>Account Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
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
    </style>
</head>
<body>
    <h1>Account Transactions</h1>
    <a href="manage_accounts.php">Back to Accounts</a>

    <h2>Current Balance: $<?= number_format($currentBalance, 2) ?></h2>

    <h2>Transactions Search</h2>
    <form method="post" action="">
        <input type="text" name="search" placeholder="Search transactions...">
        <button type="submit">Search</button>
    </form>

    <h2>Add New Transaction</h2>
    <form method="post" action="">
        <input type="text" name="description" placeholder="Description">
        <input type="number" name="amount" placeholder="Amount">
        <input type="date" name="date" placeholder="Date">
        <select name="type">
            <option value="Expense">Expense</option>
            <option value="Income">Income</option>
        </select>
        <select name="category">
            <?php
            $categoriesStmt = $pdo->prepare("SELECT categoryID, categoryName FROM category");
            $categoriesStmt->execute();
            $categories = $categoriesStmt->fetchAll();
            foreach ($categories as $category) {
                echo '<option value="' . $category['categoryID'] . '">' . htmlspecialchars($category['categoryName']) . '</option>';
            }
            ?>
        </select>
        <button type="submit" name="add">Add Transaction</button>
    </form>

    <h2>Transactions</h2>
    <table>
        <thead>
            <tr>
                <th><a href="?accountID=<?= $accountID ?>&sort=categoryName&order=<?= $order == 'DESC' ? 'ASC' : 'DESC' ?>">Category</a></th>
                <th><a href="?accountID=<?= $accountID ?>&sort=transactionAmount&order=<?= $order == 'DESC' ? 'ASC' : 'DESC' ?>">Amount</a></th>
                <th><a href="?accountID=<?= $accountID ?>&sort=transactionDate&order=<?= $order == 'DESC' ? 'ASC' : 'DESC' ?>">Date</a></th>
                <th><a href="?accountID=<?= $accountID ?>&sort=transactionType&order=<?= $order == 'DESC' ? 'ASC' : 'DESC' ?>">Type</a></th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
            <tr>
                <td><?= htmlspecialchars($transaction['categoryName']) ?></td>
                <td>$<?= ($transaction['transactionType'] === 'Expense') ? number_format(abs(htmlspecialchars($transaction['transactionAmount'])), 2) : number_format(htmlspecialchars($transaction['transactionAmount']), 2) ?></td>
                <td><?= htmlspecialchars($transaction['transactionDate']) ?></td>
                <td><?= htmlspecialchars($transaction['transactionType']) ?></td>
                <td><?= htmlspecialchars($transaction['transactionDescription']) ?></td>
                <td>
                    <a href="?accountID=<?= $accountID ?>&delete=<?= $transaction['transactionID'] ?>" onclick="return confirm('Are you sure you want to delete this transaction?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>