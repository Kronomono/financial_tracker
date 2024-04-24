<?php
session_start();
ob_start();
require_once 'includes/database-connection.php';

if (!isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}

$userID = $_SESSION['userid'];
$budgetID = $_GET['budgetID'] ?? null;

if ($budgetID) {
    $stmt = $pdo->prepare("DELETE FROM budget WHERE budgetID = ? AND userID = ?");
    if ($stmt->execute([$budgetID, $userID])) {
        // Successfully deleted the budget
        $_SESSION['message'] = "Budget deleted successfully.";
    } else {
        // Error occurred
        $_SESSION['error'] = "Error deleting budget.";
    }
}

header("Location: manage_budgets.php");
exit();
ob_end_flush();
?>