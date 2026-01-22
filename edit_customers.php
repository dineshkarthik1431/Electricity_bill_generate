<?php
session_start();
include "config.php";

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

/* ================= GET CUSTOMER ID ================= */
$customer_number = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$customer = null;
$success = "";
$error = "";

if (!$customer_number) {
    $error = "Invalid customer ID!";
} else {
    $res = mysqli_query(
        $conn,
        "SELECT * FROM customer WHERE number = $customer_number"
    );

    if (mysqli_num_rows($res) === 1) {
        $customer = mysqli_fetch_assoc($res);
    } else {
        $error = "Customer not found!";
    }
}

/* ================= UPDATE CUSTOMER ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $number   = (int)$_POST['number'];
    $name     = trim($_POST['name']);
    $phone    = trim($_POST['phone']);
    $email    = trim($_POST['email']);
    $address  = trim($_POST['address']);
    $category = $_POST['category'];

    if ($name === "" || $phone === "" || $address === "" || $category === "") {
        $error = "All required fields must be filled!";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Phone number must be exactly 10 digits!";
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE customer 
             SET name=?, phone=?, email=?, address=?, category=? 
             WHERE number=?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sssssi",
            $name,
            $phone,
            $email,
            $address,
            $category,
            $number
        );

        if (mysqli_stmt_execute($stmt)) {
            $success = "✅ Customer updated successfully!";
            $res = mysqli_query($conn, "SELECT * FROM customer WHERE number = $number");
            $customer = mysqli_fetch_assoc($res);
        } else {
            $error = "Update failed!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Customer</title>

<style>
body{
    font-family:Arial,Helvetica,sans-serif;
    background:#f1f5f9;
    margin:0
}
.header{
    background:#1e293b;
    color:#fff;
    padding:16px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center
}
.header a{
    color:#fff;
    text-decoration:none;
    margin-left:15px;
    font-weight:bold
}
.container{
    max-width:700px;
    margin:40px auto
}
.card{
    background:#fff;
    padding:28px;
    border-radius:10px;
    box-shadow:0 12px 30px rgba(0,0,0,.12)
}
h2{
    margin-top:0;
    color:#1e293b
}
label{
    font-weight:bold;
    margin-top:16px;
    display:block
}
input,textarea,select{
    width:100%;
    padding:12px;
    margin-top:6px;
    border-radius:6px;
    border:1px solid #cbd5e1;
    font-size:14px
}
textarea{min-height:90px}
.btn{
    padding:12px 22px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-weight:bold;
    margin-top:20px
}
.save{background:#16a34a;color:#fff}
.back{background:#64748b;color:#fff;text-decoration:none}
.msg{
    padding:14px;
    border-radius:6px;
    margin-bottom:18px
}
.success{background:#dcfce7;color:#166534}
.error{background:#fee2e2;color:#991b1b}
</style>
</head>

<body>

<div class="header">
    <div>✏️ Edit Customer</div>
    <div>
        <a href="admin.php">Dashboard</a>
        <a href="view_customers.php">Customers</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">

<?php if ($customer): ?>
<div class="card">

<h2>Meter Number: <?= $customer['number'] ?></h2>

<?php if ($success): ?><div class="msg success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="msg error"><?= $error ?></div><?php endif; ?>

<form method="post">

<input type="hidden" name="number" value="<?= $customer['number'] ?>">

<label>Customer Name *</label>
<input type="text" name="name" required value="<?= htmlspecialchars($customer['name']) ?>">

<label>Phone (10 digits) *</label>
<input type="text" name="phone" maxlength="10" required value="<?= htmlspecialchars($customer['phone']) ?>">

<label>Email</label>
<input type="email" name="email" value="<?= htmlspecialchars($customer['email'] ?? '') ?>">

<label>Address *</label>
<textarea name="address" required><?= htmlspecialchars($customer['address']) ?></textarea>

<label>Category *</label>
<select name="category" required>
    <option value="household" <?= $customer['category']=='household'?'selected':'' ?>>Household</option>
    <option value="commercial" <?= $customer['category']=='commercial'?'selected':'' ?>>Commercial</option>
    <option value="industrial" <?= $customer['category']=='industrial'?'selected':'' ?>>Industrial</option>
</select>

<button class="btn save">💾 Update Customer</button>
<a href="view_customers.php" class="btn back">⬅ Back</a>

</form>
</div>

<?php else: ?>
<div class="card">
    <div class="msg error"><?= $error ?></div>
    <a href="view_customers.php" class="btn back">⬅ Back</a>
    
</div>
<?php endif; ?>

</div>

</body>
</html>
