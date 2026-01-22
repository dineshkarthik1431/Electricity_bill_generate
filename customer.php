<?php
session_start();
include "config.php";

/* ===== AUTH ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}

$customer_number = $_SESSION['number'];

/* ===== CUSTOMER ===== */
$cq = mysqli_query($conn, "SELECT * FROM customer WHERE number='$customer_number'");
if (mysqli_num_rows($cq) == 0) die("Customer not found");
$customer = mysqli_fetch_assoc($cq);

/* ===== BILLS ===== */
$bills_q = mysqli_query(
    $conn,
    "SELECT * FROM bills 
     WHERE customer_number='$customer_number' 
     ORDER BY bill_date DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
<title>Customer Dashboard</title>

<style>
/* ===============================
   PREMIUM ELECTRICITY BILL UI
   =============================== */

/* Global Reset */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

/* Body */
body{
    font-family:'Inter','Segoe UI',Arial,sans-serif;
    background:
        radial-gradient(circle at top left,#e0e7ff,#f8fafc 40%);
    color:#0f172a;
}

/* Header */
.header{
    background:linear-gradient(135deg,#1d4ed8,#2563eb,#1e40af);
    color:#fff;
    padding:20px 36px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 15px 40px rgba(30,64,175,.4);
}
.header h1{
    font-size:22px;
    letter-spacing:.5px;
}
.header a{
    color:#e0e7ff;
    text-decoration:none;
    margin-left:18px;
    font-weight:500;
}
.header a:hover{
    color:#fff;
}

/* Layout */
.container{
    max-width:1200px;
    margin:40px auto;
    padding:0 22px;
}

/* Cards */
.card{
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(14px);
    border-radius:18px;
    padding:30px;
    margin-bottom:35px;
    box-shadow:
        0 25px 60px rgba(15,23,42,.12),
        inset 0 1px 0 rgba(255,255,255,.6);
    transition:.3s ease;
}
.card:hover{
    transform:translateY(-4px);
}

/* Headings */
h2,h3{
    margin-bottom:22px;
    font-weight:700;
    color:#020617;
}

/* Profile Info Grid */
.profile-info{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
    gap:20px;
}
.info-item{
    background:linear-gradient(135deg,#f8fafc,#eef2ff);
    border-radius:14px;
    padding:18px;
    box-shadow:0 8px 18px rgba(0,0,0,.06);
}
.info-label{
    font-size:13px;
    color:#64748b;
    margin-bottom:6px;
}
.info-value{
    font-size:17px;
    font-weight:700;
    color:#0f172a;
}

/* Summary Cards */
.summary-cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:18px;
}
.summary-card{
    padding:22px;
    border-radius:16px;
    color:#fff;
    text-align:center;
    box-shadow:0 18px 40px rgba(0,0,0,.2);
}
.total-billed{
    background:linear-gradient(135deg,#6366f1,#4338ca);
}
.total-paid{
    background:linear-gradient(135deg,#22c55e,#15803d);
}
.total-due{
    background:linear-gradient(135deg,#ef4444,#991b1b);
}
.summary-label{
    font-size:14px;
    opacity:.9;
}
.summary-value{
    font-size:28px;
    font-weight:800;
    margin-top:6px;
}

/* Table */
table{
    width:100%;
    border-collapse:separate;
    border-spacing:0 14px;
}
thead th{
    font-size:13px;
    text-transform:uppercase;
    color:#64748b;
    padding:0 16px;
}
tbody tr{
    background:#fff;
    box-shadow:0 12px 28px rgba(15,23,42,.12);
    border-radius:16px;
    transition:.25s ease;
}
tbody tr:hover{
    transform:scale(1.01);
}
td{
    padding:18px 16px;
    font-size:14px;
}
td:first-child{
    border-radius:16px 0 0 16px;
}
td:last-child{
    border-radius:0 16px 16px 0;
}

/* Status Badges */
.status-badge{
    padding:6px 16px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
    letter-spacing:.4px;
}
.pending{
    background:#fee2e2;
    color:#991b1b;
}
.partially_paid{
    background:#dbeafe;
    color:#1e40af;
}
.paid{
    background:#dcfce7;
    color:#166534;
}

/* Buttons */
.btn{
    padding:9px 18px;
    border-radius:999px;
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:#fff;
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    display:inline-block;
    transition:.25s ease;
    box-shadow:0 6px 16px rgba(37,99,235,.4);
}
.btn:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 22px rgba(37,99,235,.55);
}
.btn-pay{
    background:linear-gradient(135deg,#22c55e,#15803d);
    box-shadow:0 6px 16px rgba(34,197,94,.45);
}
.btn-pay:hover{
    box-shadow:0 10px 24px rgba(34,197,94,.6);
}

/* Alerts */
.alert{
    padding:16px;
    border-radius:12px;
    margin:18px 0;
    font-size:14px;
}
.alert-warning{
    background:#fff7ed;
    color:#9a3412;
    border-left:6px solid #f97316;
}
.alert-info{
    background:#ecfeff;
    color:#155e75;
    border-left:6px solid #06b6d4;
}

/* Footer Note */
.note{
    text-align:center;
    font-size:13px;
    color:#64748b;
    margin-top:30px;
}

</style>
</head>

<body>

<div class="header">
    <strong>Customer Dashboard</strong>
    <a href="logout.php" style="color:white;text-decoration:none;">Logout</a>
     
</div>

<div class="container">

<!-- PROFILE -->
<div class="card">
<h2>My Profile</h2>
<p><b>Meter:</b> <?= $customer['number'] ?></p>
<p><b>Name:</b> <?= strtoupper($customer['name']) ?></p>
<p><b>Phone:</b> <?= $customer['phone'] ?></p>
<p><b>Category:</b> <?= ucfirst($customer['category']) ?></p>
</div>

<!-- BILLS -->
<div class="card">
<h3>My Bills</h3>

<?php if (mysqli_num_rows($bills_q) > 0): ?>
<table>
<thead>
<tr>
    <th>Bill ID</th>
    <th>Month</th>
    <th>Total</th>
    <th>Paid</th>
    <th>Due</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php while ($bill = mysqli_fetch_assoc($bills_q)):
$total = $bill['bill_amount'] + ($bill['acd_amount'] ?? 0);
$paid  = $bill['paid_amount'] ?? 0;
$due   = $total - $paid;



    /* ===== TOTAL ===== */
    $total = $bill['bill_amount'] + ($bill['acd_amount'] ?? 0);

    /* ===== PAID ===== */
    $pq = mysqli_query(
        $conn,
        "SELECT SUM(amount_paid) AS paid 
         FROM payments 
         WHERE bill_id='{$bill['id']}'"
    );
    $p = mysqli_fetch_assoc($pq);
    $paid = $p['paid'] ?? 0;

    $due = $total - $paid;
    $status = ($due <= 0) ? 'paid' : 'pending';

    $month = date('F Y', strtotime($bill['bill_date']));
?>

<tr>
    <td>#<?= $bill['id'] ?></td>
    <td><?= $month ?></td>
    <td>₹<?= number_format($total,2) ?></td>
    <td style="color:#16a34a">₹<?= number_format($paid,2) ?></td>
    <td style="color:#dc2626">₹<?= number_format($due,2) ?></td>
    <td><span class="status <?= $status ?>"><?= strtoupper($status) ?></span></td>
   <td>
    <a href="print_bill.php?id=<?php echo $bill['id']; ?>" 
       class="btn btn-view" target="_blank">
        View
    </a>

   <?php if ($status !== 'paid'): ?>

        <a href="make_payment.php?id=<?php echo $bill['id']; ?>" 
           class="btn btn-pay">
            Pay Now
        </a>
    <?php endif; ?>
</td>

</tr>

<?php endwhile; ?>
</tbody>
</table>

<?php else: ?>
<p>No bills found.</p>
<?php endif; ?>

</div>

</div>
</body>
</html>
