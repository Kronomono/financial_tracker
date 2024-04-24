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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2 {
            color: #444;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #e9e9e9;
        }
        form {
            background-color: white;
            padding: 20px;
            border: 1px solid #ddd;
            margin-top: 20px;
        }
        label {
            margin-top: 10px;
            display: block;
            font-weight: bold;
        }
        input[type="number"], input[type="date"], select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box; /* Adds padding and border to element's total width and height */
        }
        button {
            background-color: #5c67f2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #5058e5;
        }
        a {
            color: #5c67f2;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

