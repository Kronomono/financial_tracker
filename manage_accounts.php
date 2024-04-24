<?php
ob_start(); // Start output buffering
session_start();

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}

// Include the database connection file
require_once 'includes/database-connection.php';

$userID = $_SESSION['userid'];

// Handle adding a new account
if (isset($_POST['addAccount'])) {
    $accountType = $_POST['accountType'];
    $accountBalance = $_POST['accountBalance'];
    $insertStmt = $pdo->prepare("INSERT INTO account (userID, accountType, accountBalance) VALUES (?, ?, ?)");
    $insertStmt->execute([$userID, $accountType, $accountBalance]);
    header('Location: manage_accounts.php');  // Refresh the page to show the new account
    exit();
}

// Handle deleting an account
if (isset($_GET['deleteAccount'])) {
    $accountID = $_GET['deleteAccount'];
    $deleteTransactions = $pdo->prepare("DELETE FROM transactions WHERE accountID = ?");
    $deleteTransactions->execute([$accountID]);
    $deleteAccount = $pdo->prepare("DELETE FROM account WHERE accountID = ? AND userID = ?");
    $deleteAccount->execute([$accountID, $userID]);
    header('Location: manage_accounts.php');
    exit();
}


// Fetch all accounts associated with the logged-in user
$accountsStmt = $pdo->prepare("SELECT accountID, accountType, accountBalance FROM account WHERE userID = ?");
$accountsStmt->execute([$userID]);
$accounts = $accountsStmt->fetchAll();

ob_end_flush(); // End output buffering and flush all output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts</title>
    <!-- CSS styling for the page -->
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Account Management</h1>
    <a href="dashboard.php">Back to Dashboard</a>
    <h2>Your Accounts</h2>
    <table>
        <thead>
            <tr>
                <th>Account Type</th>
                <th>Balance</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accounts as $account): ?>
            <tr>
                <!-- Display account details -->
                <td><a href="account_details.php?accountID=<?= $account['accountID'] ?>"><?= htmlspecialchars($account['accountType']) ?></a></td>
                <td>$<?= number_format(htmlspecialchars($account['accountBalance']), 2) ?></td>
                <td>
                    <!-- Links to view and delete the account -->
                    <a href="account_details.php?accountID=<?= $account['accountID'] ?>">View</a>
                    <a href="?deleteAccount=<?= $account['accountID'] ?>" onclick="return confirm('Are you sure you want to delete this account? This will remove all associated transactions.');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <!-- Form to add a new account -->
    <h2>Add New Account</h2>
    <form action="manage_accounts.php" method="post">
        <label for="accountType">Account Type:</label>
        <select name="accountType" id="accountType" required>
            <option value="Checking">Checking</option>
            <option value="Savings">Savings</option>
        </select>
        <label for="accountBalance">Initial Balance:</label>
        <input type="number" id="accountBalance" name="accountBalance" required>
        <button type="submit" name="addAccount">Add Account</button>
    </form>
</body>
</html>