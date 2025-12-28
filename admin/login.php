<?php
session_start();

/* ================= CONFIG ================= */
$ADMIN_PASSWORD = "admin123"; // change this later

/* ================= AUTO REDIRECT ================= */
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

/* ================= HANDLE LOGIN ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_POST['password']) || trim($_POST['password']) === "") {
        $error = "Please enter password";
    } else {
        $password = trim($_POST['password']);

        if ($password === $ADMIN_PASSWORD) {
            $_SESSION['is_admin'] = true;
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "❌ Invalid password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login - HomeThali</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
    margin: 0;
    height: 100vh;
    background: linear-gradient(135deg, #84D160, #4CAF50);
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: Arial, sans-serif;
}

.login-box {
    background: white;
    width: 320px;
    padding: 30px;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 15px 35px rgba(0,0,0,0.3);
}

.login-box h2 {
    margin-top: 0;
    color: #4CAF50;
}

.login-box input {
    width: 100%;
    padding: 14px;
    margin: 15px 0;
    font-size: 16px;
    border-radius: 10px;
    border: 1.5px solid #ccc;
}

.login-box input:focus {
    border-color: #4CAF50;
    outline: none;
}

.login-box button {
    width: 100%;
    padding: 14px;
    font-size: 18px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
}

.login-box button:active {
    transform: scale(0.97);
}

.error {
    color: red;
    font-size: 14px;
    margin-bottom: 10px;
}
</style>
</head>

<body>

<div class="login-box">
    <h2>Admin Login</h2>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <!-- ✅ IMPORTANT: name="password" -->
    <form method="POST">
        <input type="password" name="password" placeholder="Enter admin password" required>
        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>
