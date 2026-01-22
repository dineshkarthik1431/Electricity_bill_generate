<?php
session_start();
include "config.php";

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$success_message = "";
$error_message = "";

/* ================= DELETE CUSTOMER ================= */
if (isset($_GET['delete'])) {
    $customer_number = $_GET['delete'];

    // check if ANY bill has pending due
    $bills = mysqli_query(
        $conn,
        "SELECT id, bill_amount, acd_amount 
         FROM bills 
         WHERE customer_number = '$customer_number'"
    );

    $has_unpaid = false;

    while ($b = mysqli_fetch_assoc($bills)) {
        $bill_id = $b['id'];
        $total = $b['bill_amount'] + ($b['acd_amount'] ?? 0);

        $pay_q = mysqli_query(
            $conn,
            "SELECT SUM(amount_paid) AS paid 
             FROM payments 
             WHERE bill_id = $bill_id"
        );
        $pay = mysqli_fetch_assoc($pay_q);
        $paid = $pay['paid'] ?? 0;

        if (($total - $paid) > 0) {
            $has_unpaid = true;
            break;
        }
    }

    if ($has_unpaid) {
        $error_message = "❌ Cannot delete customer. Unpaid bills exist.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "DELETE FROM users WHERE number = '$customer_number'");
            mysqli_query($conn, "DELETE FROM readings WHERE number = '$customer_number'");
            mysqli_query($conn, "DELETE FROM bills WHERE customer_number = '$customer_number'");
            mysqli_query($conn, "DELETE FROM customer WHERE number = '$customer_number'");
            mysqli_commit($conn);
            $success_message = "✅ Customer deleted successfully.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = "❌ Error deleting customer.";
        }
    }
}

/* ================= FETCH CUSTOMERS ================= */
$customers = mysqli_query($conn, "SELECT * FROM customer ORDER BY number");
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Customers</title>

<style>
body{font-family:Arial;background:#f4f6f8;margin:0}
.header{background:#1e293b;color:#fff;padding:15px 30px;display:flex;justify-content:space-between;align-items:center}
.nav a{color:#fff;margin-left:15px;text-decoration:none}
.container{max-width:1300px;margin:30px auto;padding:0 20px}
.card{background:#fff;padding:25px;border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,.1)}
table{width:100%;border-collapse:collapse;margin-top:20px}
th,td{padding:12px;border-bottom:1px solid #ddd}
th{background:#f1f5f9}
.badge{padding:4px 10px;border-radius:12px;font-size:12px;font-weight:bold}
.household{background:#dcfce7;color:#166534}
.commercial{background:#e0f2fe;color:#075985}
.industrial{background:#fee2e2;color:#991b1b}
.btn{padding:6px 10px;border-radius:4px;color:#fff;border:none;cursor:pointer}
.view{background:#0ea5e9}
.edit{background:#2563eb}
.del{background:#dc2626}
.msg{padding:12px;margin-bottom:15px;border-radius:4px}
.success{background:#dcfce7}
.error{background:#fee2e2}
.actions{display:flex;gap:6px}
</style>

<script>
function confirmDelete(num,name){
    if(confirm("Delete customer "+name+" (Meter "+num+") ?")){
        window.location='view_customers.php?delete='+num;
    }
}
</script>

</head>
<body>

<div class="header">
    <div>👥 Manage Customers</div>
    <div class="nav">
        Welcome, <?= $_SESSION['username'] ?>
        <a href="admin.php">Dashboard</a>
        <a href="view_bills.php">Bills</a>
        <a href="logout.php">Logout</a>

  

    </div>
</div>

<div class="container">
<div class="card">

<h2>All Customers</h2>

<?php if($success_message): ?><div class="msg success"><?= $success_message ?></div><?php endif; ?>
<?php if($error_message): ?><div class="msg error"><?= $error_message ?></div><?php endif; ?>

<table>
<thead>
<tr>
    <th>Meter No</th>
    <th>Name</th>
    <th>Phone</th>
    <th>Email</th>
    <th>Category</th>
    <th>Address</th>
    <th>Reg Date</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>

<?php while($row = mysqli_fetch_assoc($customers)): ?>
<tr>
    <td><?= $row['number'] ?></td>
    <td style="font-weight:bold"><?= strtoupper($row['name']) ?></td>
    <td><?= $row['phone'] ?></td>
    <td><?= $row['email'] ?? '' ?></td>
    <td><span class="badge <?= $row['category'] ?>"><?= ucfirst($row['category']) ?></span></td>
    <td><?= $row['address'] ?></td>
    <td><?= $row['reg_date'] ?></td>
    <td class="actions">
        <a class="btn view" href="view_bills.php?customer=<?= $row['number'] ?>">Bills</a>
        <a class="btn edit" href="edit_customers.php?id=<?= $row['number'] ?>">Edit</a>
        <button class="btn del" onclick="confirmDelete('<?= $row['number'] ?>','<?= addslashes($row['name']) ?>')">Delete</button>
    </td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

</div>
</div>

</body>
</html>
