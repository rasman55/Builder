<?php
session_start();
require_once "config/database.php";
require_once "includes/auth.php";

if (!isAuthenticated() || $_SESSION["user_type"] !== "staff") {
    header("Location: login.php?type=staff");
    exit();
}

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Staff Dashboard - Goba Hospital</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container dashboard">
        <h1>Staff Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($currentUser["first_name"]); ?>!</p>
        <div class="dashboard-nav">
            <button onclick="logout()">Logout</button>
        </div>
        <div class="coming-soon">
            <h2>Staff Features Coming Soon</h2>
            <p>Patient assistance and record management tools will be available here.</p>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function logout() {
            if (confirm("Are you sure you want to logout?")) {
                $.post("includes/auth.php", { action: "logout" }, function() {
                    window.location.href = "index.html";
                });
            }
        }
    </script>
</body>
</html>
