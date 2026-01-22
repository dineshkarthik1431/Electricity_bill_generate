<?php
session_start();
include "config.php";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin.php");
    } elseif ($_SESSION['role'] == 'worker') {
        header("Location: worker.php");
    } else {
        header("Location: customer.php");
    }
    exit;
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Simple credentials check (for testing)
    if ($username == 'admin' && $password == 'admin123') {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        header("Location: admin.php");
        exit;
    }
    elseif ($username == 'worker' && $password == 'worker123') {
        $_SESSION['user_id'] = 2;
        $_SESSION['username'] = 'worker';
        $_SESSION['role'] = 'worker';
        header("Location: worker.php");
        exit;
    }
    else {
        // Check if it's a customer
        $sql = "SELECT u.*, c.name, c.number FROM users u 
                JOIN customer c ON u.number = c.number 
                WHERE u.username = '$username' AND u.password = '$password' 
                AND u.role = 'customer'";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['number'] = $row['number'];
            $_SESSION['name'] = $row['name'];
            header("Location: customer.php");
            exit;
        } else {
            $error = "Invalid username or password! Try: admin/admin123 or worker/worker123";
        }
    }
}

// If no users exist, create default ones
$check = mysqli_query($conn, "SELECT * FROM users");
if (mysqli_num_rows($check) == 0) {
    // Create default users
    mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES 
        ('admin', 'admin123', 'admin'),
        ('worker', 'worker123', 'worker')");
    
    // Create a test customer with user account
    mysqli_query($conn, "INSERT INTO customer (number, name, phone, address, category, reg_date) VALUES 
        (1001, 'Test Customer', '9876543210', '123 Test Street', 'household', CURDATE())");
    
    mysqli_query($conn, "INSERT INTO users (username, password, role, number) VALUES 
        ('customer1', 'customer123', 'customer', 1001)");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Electricity System</title>
    <style>
    * {
        box-sizing: border-box;
        font-family: "Segoe UI", Roboto, Arial, sans-serif;
    }

    body {
        margin: 0;
        height: 100vh;
        background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .login-box {
        background: #ffffff;
        width: 380px;
        padding: 35px 30px;
        border-radius: 12px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
        animation: fadeIn 0.6s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    h2 {
        margin: 0 0 25px;
        text-align: center;
        color: #2c3e50;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .input-group {
        margin-bottom: 18px;
    }

    input[type="text"],
    input[type="password"] {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 15px;
        transition: border 0.3s, box-shadow 0.3s;
    }

    input:focus {
        outline: none;
        border-color: #2c5364;
        box-shadow: 0 0 0 2px rgba(44, 83, 100, 0.15);
    }

    .btn {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #1f4037, #2c5364);
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
    }

    .error {
        background: #fdecea;
        color: #a94442;
        padding: 10px;
        border-radius: 5px;
        font-size: 14px;
        margin-bottom: 15px;
        text-align: center;
        border-left: 4px solid #dc3545;
    }

    .test-creds {
        margin-top: 25px;
        padding: 15px;
        background: #f7f9fb;
        border-radius: 6px;
        font-size: 13px;
        color: #333;
        border: 1px solid #e1e5ea;
    }

    .test-creds h4 {
        margin: 0 0 10px;
        font-size: 14px;
        color: #555;
    }

    .test-creds p {
        margin: 6px 0;
    }

    .role {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 6px;
        text-transform: uppercase;
    }

    .admin {
        background: #e74c3c;
        color: #fff;
    }

    .worker {
        background: #f1c40f;
        color: #2c3e50;
    }

    .customer {
        background: #2ecc71;
        color: #fff;
    }

    @media (max-width: 420px) {
        .login-box {
            width: 90%;
            padding: 30px 20px;
        }
    }
</style>

</head>
<body>
    <div class="login-box">
        <h2>🔌 Electricity Billing System</h2>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="input-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
         
        </form>
        
        <div class="test-creds">
            <h4>Test Credentials:</h4>
            <p>
                <strong>Admin:</strong> admin / admin123 
                <span class="role admin">Admin</span>
            </p>
            <p>
                <strong>Worker:</strong> worker / worker123 
                <span class="role worker">Worker</span>
            </p>
            <p>
                <strong>Customer:</strong> customer1 / customer123 
                <span class="role customer">Customer</span>
            </p>
            <p style="margin-top: 10px; color: #666; font-size: 12px;">
                Need more customers? Register them in Admin panel.

            </p>
        </div>
    </div>
</body>
</html>