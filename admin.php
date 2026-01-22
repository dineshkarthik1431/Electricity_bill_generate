
<?php
session_start();

// SIMPLE AUTH CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
include "config.php";

/*Connect to database
$conn = mysqli_connect("localhost", "root", "", "electricity_bill");
if (!$conn) {
    die("Database connection failed");
}*/

$success = "";
$error = "";
$new_username = "";
$new_password = "";

// Handle customer registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $number = $_POST['number'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $category = $_POST['category'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Validate inputs
    if (empty($number) || empty($name) || empty($phone) || empty($address) || empty($category) || empty($username) || empty($password)) {
        $error = "Please fill all required fields!";
    } elseif (!is_numeric($number) || $number < 1000 || $number > 99999) {
        $error = "Meter number must be between 1000 and 99999!";
    } else {
        // Check if meter number already exists
        $check_meter = mysqli_query($conn, "SELECT number FROM customer WHERE number = '$number'");
        if (mysqli_num_rows($check_meter) > 0) {
            $error = "Meter number $number already exists!";
        } else {
            // Check if username already exists
            $check_user = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
            if (mysqli_num_rows($check_user) > 0) {
                $error = "Username '$username' already exists! Choose a different username.";
            } else {
                // Start transaction
                mysqli_begin_transaction($conn);
                
                try {
                    // Insert customer
                    $sql1 = "INSERT INTO customer (number, name, phone, address, email, category, reg_date) 
                            VALUES ('$number', '$name', '$phone', '$address', '$email', '$category', CURDATE())";
                    
                    if (!mysqli_query($conn, $sql1)) {
                        throw new Exception("Customer insert failed: " . mysqli_error($conn));
                    }
                    
                    // Insert user account for customer
                    $sql2 = "INSERT INTO users (username, password, role, number) 
                            VALUES ('$username', '$password', 'customer', '$number')";
                    
                    if (!mysqli_query($conn, $sql2)) {
                        throw new Exception("User account creation failed: " . mysqli_error($conn));
                    }
                    
                    mysqli_commit($conn);
                    $success = "✅ Customer registered successfully!<br>";
                    $success .= "Username: <strong>$username</strong><br>";
                    $success .= "Password: <strong>$password</strong><br>";
                    $success .= "Customer can now login with these credentials.";
                    
                    // Store for autofill
                    $new_username = $username;
                    $new_password = $password;
                    
                    // Clear form fields
                    echo '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            document.querySelector("input[name=\"number\"]").value = "";
                            document.querySelector("input[name=\"name\"]").value = "";
                            document.querySelector("input[name=\"phone\"]").value = "";
                            document.querySelector("textarea[name=\"address\"]").value = "";
                            document.querySelector("input[name=\"email\"]").value = "";
                            document.querySelector("input[name=\"username\"]").value = "";
                            document.querySelector("input[name=\"password\"]").value = "";
                        });
                    </script>';
                    
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = "❌ Error: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
/* ================= RESET ================= */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Poppins", "Segoe UI", sans-serif;
}

body {
    background: linear-gradient(135deg, #fdfbfb, #ebedee);
    color: #2d3436;
}

/* ================= HEADER ================= */
.header {
    background: linear-gradient(135deg, #ff6a00, #ee0979);
    padding: 18px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 10px 30px rgba(238,9,121,0.4);
}

.logo {
    font-size: 24px;
    font-weight: 700;
    color: #fff;
    letter-spacing: 1px;
}

.nav a {
    color: #fff;
    text-decoration: none;
    margin-left: 12px;
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 500;
    background: rgba(255,255,255,0.15);
    transition: all 0.3s ease;
}

.nav a:hover,
.nav a.active {
    background: #ffffff;
    color: #ee0979;
    box-shadow: 0 6px 18px rgba(0,0,0,0.25);
}

/* ================= CONTAINER ================= */
.container {
    max-width: 1300px;
    margin: 35px auto;
    padding: 0 25px;
}

/* ================= CARDS ================= */
.card {
    background: #ffffff;
    border-radius: 22px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow:
        0 15px 35px rgba(0,0,0,0.12),
        inset 0 0 0 1px rgba(255,255,255,0.4);
    position: relative;
    overflow: hidden;
}

.card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    height: 6px;
    width: 100%;
    background: linear-gradient(90deg, #ff6a00, #ee0979, #00c6ff);
}

h2, h3 {
    margin-bottom: 20px;
    font-weight: 700;
    color: #2d3436;
}

/* ================= FORMS ================= */
label {
    font-size: 13px;
    font-weight: 600;
    color: #636e72;
    margin-bottom: 6px;
    display: block;
}

input, select, textarea {
    width: 100%;
    padding: 14px 16px;
    border-radius: 14px;
    border: none;
    background: #f1f2f6;
    font-size: 14px;
    margin-top: 6px;
    box-shadow: inset 4px 4px 10px rgba(0,0,0,0.08),
                inset -4px -4px 10px rgba(255,255,255,0.9);
    transition: all 0.3s ease;
}

input:focus,
select:focus,
textarea:focus {
    outline: none;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(238,9,121,0.35);
}

textarea {
    min-height: 100px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 22px;
    margin-bottom: 22px;
}

/* ================= BUTTONS ================= */
button,
.action-btn,
.quick-link {
    border: none;
    cursor: pointer;
    border-radius: 40px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
}

button {
    padding: 14px 32px;
    background: linear-gradient(135deg, #00c6ff, #0072ff);
    color: #fff;
    box-shadow: 0 10px 25px rgba(0,114,255,0.45);
}

button:hover {
    transform: translateY(-3px) scale(1.03);
    box-shadow: 0 18px 35px rgba(0,114,255,0.55);
}

.btn-success {
    background: linear-gradient(135deg, #00b09b, #96c93d);
    box-shadow: 0 10px 25px rgba(0,176,155,0.45);
}

/* ================= TABLE ================= */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 14px;
    font-size: 14px;
}

thead th {
    padding: 14px;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.7px;
    color: #636e72;
}

tbody tr {
    background: linear-gradient(135deg, #fdfbfb, #ebedee);
    border-radius: 16px;
    box-shadow: 0 8px 18px rgba(0,0,0,0.12);
}

tbody td {
    padding: 14px;
}

tbody tr:hover {
    transform: scale(1.015);
}

/* ================= ALERTS ================= */
.message {
    padding: 18px 22px;
    border-radius: 18px;
    font-size: 14px;
    margin-bottom: 24px;
}

.success {
    background: linear-gradient(135deg, #a8ff78, #78ffd6);
    color: #065f46;
}

.error {
    background: linear-gradient(135deg, #ff9a9e, #fad0c4);
    color: #7f1d1d;
}

/* ================= STATS ================= */
.stat-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 25px;
    margin-bottom: 35px;
}

.stat-card {
    background: linear-gradient(135deg, #ffffff, #f1f2f6);
    padding: 30px;
    border-radius: 22px;
    text-align: center;
    box-shadow: 0 14px 35px rgba(0,0,0,0.15);
}

.stat-value {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 13px;
    color: #636e72;
}

.household { color: #00b894; }
.commercial { color: #0984e3; }
.industrial { color: #d63031; }

/* ================= QUICK LINKS ================= */
.quick-links {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-top: 35px;
}

.quick-link {
    padding: 15px 32px;
    background: linear-gradient(135deg, #ff6a00, #ee0979);
    color: #fff;
    text-decoration: none;
    box-shadow: 0 10px 25px rgba(238,9,121,0.45);
}

.quick-link:hover {
    transform: translateY(-3px) scale(1.04);
}

/* ================= RESPONSIVE ================= */
@media (max-width: 768px) {
    .nav a {
        padding: 6px 12px;
        font-size: 13px;
    }
}
</style>



    <script>
        function copyCredentials() {
            const username = document.querySelector('input[name="username"]').value;
            const password = document.querySelector('input[name="password"]').value;
            
            if (username && password) {
                const text = `Username: ${username}\nPassword: ${password}`;
                navigator.clipboard.writeText(text).then(() => {
                    alert('Credentials copied to clipboard!');
                });
            }
        }
    </script>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">⚡ Admin Dashboard</div>
        <div class="nav">
            <span style="color: #95a5a6;">Welcome, <?php echo $_SESSION['username']; ?></span>
            <a href="admin.php" style="background: #3498db;">Dashboard</a>
            <a href="view_customers.php">Customers</a>
            <a href="view_bills.php">Bills</a>
            <a href="generate_bill.php">Generate Bill</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
        
    <div class="container">
        <!-- Statistics -->
        <div class="stat-cards">
            <?php
            $total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM customer"))['count'];
            $total_bills = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM bills"))['count'];
            $total_readings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM readings"))['count'];
            $total_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM payments"))['count'];
            ?>
            
            <div class="stat-card">
                <div class="stat-label">Total Customers</div>
                <div class="stat-value"><?php echo $total_customers; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Total Bills</div>
                <div class="stat-value"><?php echo $total_bills; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Meter Readings</div>
                <div class="stat-value"><?php echo $total_readings; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Payments</div>
                <div class="stat-value"><?php echo $total_payments; ?></div>
            </div>
        </div>
        
        <!-- Add New Customer -->
        <div class="card">
            <h2>Add New Customer</h2>
            
            <?php if($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="message success"><?php echo $success; ?></div>
                <?php if($new_username && $new_password): ?>
                <div class="credentials-box">
                    <strong>New Customer Credentials:</strong><br>
                    <strong>Username:</strong> <?php echo $new_username; ?><br>
                    <strong>Password:</strong> <?php echo $new_password; ?><br>
                    <small>Customer can login at: <?php echo $_SERVER['HTTP_HOST']; ?>/login.php</small>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-row">
                   <div class="form-group">
    <label class="required">Meter Number</label>
    <input 
        type="text"
        name="number"
        placeholder="e.g., 10001"
        required
        pattern="[0-9]{5}"
        maxlength="5"
        inputmode="numeric"
        title="Meter number must be exactly 5 digits"
    >
    <small style="color: #666;">Must be exactly 5 digits</small>
</div>

                    
                   <div class="form-group">
    <label class="required">Customer Name</label>
    <input 
        type="text"
        name="name"
        placeholder="Full Name"
        required
        pattern="[A-Za-z ]{3,32}"
        minlength="3"
        maxlength="32"
        title="Name must contain only letters and spaces (3–32 characters)"
        oninput="this.value = this.value.toUpperCase()"
    >
    <small style="color: #666;">Only letters allowed (3–32 characters). Printed in UPPERCASE.</small>
</div>

                </div>
                
                <div class="form-row">
    <div class="form-group">
        <label class="required">Phone Number</label>
        <input 
            type="text"
            name="phone"
            placeholder="10-digit mobile number"
            required
            pattern="[0-9]{10}"
            maxlength="10"
            inputmode="numeric"
            title="Please enter exactly 10 digits"
        >
    </div>
</div>

                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="customer@example.com">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="required">Address</label>
                    <textarea name="address" required placeholder="Full address with area, city, pincode"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Category</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <option value="household">Household</option>
                            <option value="commercial">Commercial</option>
                            <option value="industrial">Industrial</option><br>
                        </select>
                        <small style="color: #666;">Affects billing rates and minimum charges</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Username</label>
                        <input type="text" name="username" required placeholder="Login username">
                        <br>
                        <br>
                        <small style="color: #666;">For customer login</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Password</label>
                        <input type="text" name="password" required placeholder="Login password"><br>
                        <br>
                        <small style="color: #666;">For customer login</small>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; align-items: center; margin-top: 20px;">
                    <button type="submit" name="register" class="btn-success">➕ Add Customer</button>
                    <button type="button" onclick="copyCredentials()" class="btn" style="background: #6c757d;">
                        📋 Copy Credentials
                    </button>
                    <button type="reset" class="btn" style="background: #ffc107; color: #212529;">
                        🗑️ Clear Form
                    </button>
                </div>
            </form>
        </div>
        
        <!-- All Customers -->
        <div class="card">
            <h2>All Customers (<?php echo $total_customers; ?>)</h2>
            
            <?php
            $result = mysqli_query($conn, "SELECT * FROM customer ORDER BY number DESC");
            if (mysqli_num_rows($result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Meter No.</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Category</th>
                            <th>Address</th>
                            <th>Reg. Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['number']; ?></td>
                            <td class="customer-name-uppercase"><?php echo htmlspecialchars(strtoupper($row['name'])); ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td>
                                <span style="display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 12px; 
                                      background: <?php echo $row['category'] == 'household' ? '#d4edda' : 
                                                          ($row['category'] == 'commercial' ? '#cce5ff' : '#f8d7da'); ?>;">
                                    <?php echo ucfirst($row['category']); ?>
                                </span>
                            </td>
                            <td title="<?php echo htmlspecialchars($row['address']); ?>">
                                <?php echo strlen($row['address']) > 30 ? substr($row['address'], 0, 30) . '...' : $row['address']; ?>
                            </td>
                            <td><?php echo $row['reg_date']; ?></td>
                            <td>
                               <div class="action-buttons">

                               <style>/* Button container */
.action-buttons{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

/* Base button style */
.action-btn{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:8px 18px;
    font-size:13px;
    font-weight:600;
    border-radius:999px;
    text-decoration:none;
    color:#fff;
    transition:all .25s ease;
    box-shadow:0 8px 20px rgba(0,0,0,.15);
}

/* EDIT button (blue) */
.btn-edit{
    background:linear-gradient(135deg,#38bdf8,#2563eb);
}

.btn-edit:hover{
    transform:translateY(-2px);
    background:linear-gradient(135deg,#0ea5e9,#1e40af);
    box-shadow:0 12px 28px rgba(37,99,235,.45);
}

/* READING button (green / energy style) */
.btn-reading{
    background:linear-gradient(135deg,#4ade80,#16a34a);
}

.btn-reading:hover{
    transform:translateY(-2px);
    background:linear-gradient(135deg,#22c55e,#15803d);
    box-shadow:0 12px 28px rgba(22,163,74,.45);
}

/* Click effect */
.action-btn:active{
    transform:scale(.96);
}
</style>
    <a href="edit_customers.php?id=<?php echo $row['number']; ?>" 
       class="action-btn btn-edit">
        ✏ Edit
    </a>

    <a href="add_reading.php?number=<?php echo $row['number']; ?>" 
       class="action-btn btn-reading">
        ⚡ Reading
    </a>
</div>

                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <h3>No customers found</h3>
                    <p>Add your first customer using the form above.</p>
                </div>
            <?php endif; ?>
            
            <!-- Category Summary -->
            <?php
            $category_result = mysqli_query($conn, "SELECT category, COUNT(*) as count FROM customer GROUP BY category");
            if (mysqli_num_rows($category_result) > 0): ?>
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Customer Categories:</strong>
                    <?php while($cat = mysqli_fetch_assoc($category_result)): ?>
                        <span style="margin-left: 15px;">
                            <span class="stat-value <?php echo $cat['category']; ?>"><?php echo $cat['count']; ?></span>
                            <?php echo ucfirst($cat['category']); ?>
                        </span>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Links -->
        <div class="quick-links">
            <a href="generate_bill.php" class="quick-link">🧾 Generate New Bill</a>
            <a href="add_reading.php" class="quick-link">📝 Add Meter Reading</a>
            <a href="view_bills.php" class="quick-link">📄 View All Bills</a>
        </div>
    </div>
</body>
</html>