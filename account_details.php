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

$accountID = $_GET['accountID'] ?? null; // Get the accountID from the URL

function updateAccountBalance($pdo, $accountID, $amount, $type) {
    // Check the current balance
    $balanceStmt = $pdo->prepare("SELECT balance FROM accounts WHERE accountID = ?");
    $balanceStmt->execute([$accountID]);
    $balance = $balanceStmt->fetchColumn();

    // Update the balance based on transaction type
    if ($type == 'Income') {
        $newBalance = $balance + $amount;
    } else { // Assume 'Expense'
        $newBalance = $balance - $amount;
    }

    // Update the account with the new balance
    $updateStmt = $pdo->prepare("UPDATE accounts SET balance = ? WHERE accountID = ?");
    $updateStmt->execute([$newBalance, $accountID]);
}

// Handle POST requests for adding transactions
if (isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO transactions (transactionDescription, transactionAmount, transactionDate, transactionType, accountID, categoryID) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['description'], $_POST['amount'], $_POST['date'], $_POST['type'], $accountID, $_POST['category']]);

    // Update account balance
    updateAccountBalance($pdo, $accountID, $_POST['amount'], $_POST['type']);

    header('Location: account_details.php?accountID=' . $accountID);
    exit();
}


// WOP: Handle POST requests for updating transactions
if (isset($_POST['update'])) {
    $stmt = $pdo->prepare("UPDATE transactions SET transactionDescription = ?, transactionAmount = ?, transactionDate = ?, transactionType = ?, categoryID = ? WHERE transactionID = ?");
    $stmt->execute([$_POST['description'], $_POST['amount'], $_POST['date'], $_POST['type'], $_POST['transactionID']]);
    header('Location: account_details.php?accountID=' . $accountID);
    exit();
}

// Handle deletion of a transaction
if (isset($_GET['delete'])) {
    $transactionID = $_GET['delete'];
    
    // Fetch the transaction details to update the balance correctly
    $transStmt = $pdo->prepare("SELECT transactionAmount, transactionType FROM transactions WHERE transactionID = ?");
    $transStmt->execute([$transactionID]);
    $transaction = $transStmt->fetch();
    
    // Delete the transaction
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE transactionID = ?");
    $stmt->execute([$transactionID]);

    // Update account balance
    if ($transaction) {
        // Reverse the amount based on type
        $adjustedAmount = $transaction['transactionType'] == 'Income' ? -$transaction['transactionAmount'] : $transaction['transactionAmount'];
        updateAccountBalance($pdo, $accountID, $adjustedAmount, $transaction['transactionType']);
    }

    // Redirect to clear the 'delete' parameter from the URL
    header('Location: account_details.php?accountID=' . $accountID);
    exit();
}


// Fetch the account details for the selected account
$search = $_POST['search'] ?? '';
$sort = $_GET['sort'] ?? 'transactionDate';
$order = $_GET['order'] ?? 'DESC';

// Fetch transactions for the selected account including the transaction type
$transactionsStmt = $pdo->prepare("SELECT t.transactionID, t.transactionDescription, t.transactionAmount, t.transactionDate, t.transactionType, c.categoryName FROM transactions t LEFT JOIN category c ON t.categoryID = c.categoryID WHERE t.accountID = ? AND (t.transactionDescription LIKE ? OR t.transactionAmount LIKE ?) ORDER BY $sort $order");
$transactionsStmt->execute([$accountID, '%' . $search . '%', '%' . $search . '%']);
$transactions = $transactionsStmt->fetchAll();

ob_end_flush(); // End output buffering and flush all output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Details</title>
    <!-- CSS styling for the page -->
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
    
    
    <!-- Search Form -->
    <h2>Transactions Search</h2>
    <form method="post" action="">
        <input type="text" name="search" placeholder="Search transactions...">
        <button type="submit">Search</button>
    </form>

    <!-- Transaction Form for Adding New Transactions -->
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
            // Fetch all categories for the category dropdown
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

    <!-- Transaction table for displaying Transactions -->
    <h2>Transactions</h2>
    <table>
        <thead>
            <tr>
                <!-- Sorting links to the table headers -->
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
                <td>$<?= number_format(htmlspecialchars($transaction['transactionAmount']), 2) ?></td>
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