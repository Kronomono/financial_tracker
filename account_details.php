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
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO transactions (transactionDescription, transactionAmount, transactionDate, transactionType, accountID, categoryID) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['description'], $_POST['amount'], $_POST['date'], $_POST['type'], $accountID, $_POST['category']]);

        // Update account balance
        updateAccountBalance($pdo, $accountID, $_POST['amount'], $_POST['type']);

        $pdo->commit();
        header('Location: account_details.php?accountID=' . $accountID);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Failed to add transaction: " . $e->getMessage());
        // Handle error appropriately
    }
}

// Handle deletion of a transaction
if (isset($_GET['delete'])) {
    $transactionID = $_GET['delete'];
    
    try {
        $pdo->beginTransaction();

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

        $pdo->commit();
        header('Location: account_details.php?accountID=' . $accountID);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Failed to delete transaction: " . $e->getMessage());
        // Handle error appropriately
    }
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
