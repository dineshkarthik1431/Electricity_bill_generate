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

/* ================= DELETE BILL (ONLY IF FULLY PAID) ================= */
if (isset($_GET['delete_bill'])) {
    $bill_id = (int)$_GET['delete_bill'];

    // total bill amount
    $bill_q = mysqli_query($conn, "SELECT bill_amount, acd_amount FROM bills WHERE id=$bill_id");
    $bill = mysqli_fetch_assoc($bill_q);

    if (!$bill) {
        $error_message = "❌ Bill not found!";
    } else {
        // total paid
        $pay_q = mysqli_query($conn, "SELECT SUM(amount_paid) AS paid FROM payments WHERE bill_id=$bill_id");
        $pay = mysqli_fetch_assoc($pay_q);
        $paid = $pay['paid'] ?? 0;

        $total = $bill['bill_amount'] + ($bill['acd_amount'] ?? 0);
        $due = $total - $paid;

        if ($due > 0) {
            $error_message = "❌ Cannot delete bill. Pending amount exists.";
        } else {
            mysqli_query($conn, "DELETE FROM payments WHERE bill_id=$bill_id");
            mysqli_query($conn, "DELETE FROM bills WHERE id=$bill_id");
            $success_message = "✅ Bill deleted successfully!";
        }
    }
}

/* ================= FETCH BILLS ================= */
$sql = "
    SELECT 
        b.id,
        b.customer_number,
        b.service_no,
        b.bill_date,
        b.bill_amount,
        b.acd_amount,
        c.name
    FROM bills b
    JOIN customer c ON c.number = b.customer_number
    ORDER BY b.bill_date DESC
";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>All Bills</title>
<style>
/* ================= ROOT ================= */
:root{
    --bg:#f1f5f9;
    --card:#ffffff;
    --primary:#2563eb;
    --primary-dark:#1e40af;
    --success:#16a34a;
    --danger:#dc2626;
    --warning:#f59e0b;
    --text:#0f172a;
    --muted:#64748b;
    --border:#e2e8f0;
}

/* ================= GLOBAL ================= */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:"Inter","Segoe UI",system-ui,-apple-system,sans-serif;
}

body{
    background:linear-gradient(135deg,#eef2ff,#f8fafc);
    color:var(--text);
}

/* ================= HEADER ================= */
.header{
    background:linear-gradient(135deg,#0f172a,#1e293b);
    color:#fff;
    padding:18px 34px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 10px 30px rgba(0,0,0,.35);
    position:sticky;
    top:0;
    z-index:100;
}

.header a{
    color:#c7d2fe;
    text-decoration:none;
    font-weight:600;
    margin-left:10px;
}

.header a:hover{
    color:#fff;
}

/* ================= LAYOUT ================= */
.container{
    max-width:1500px;
    margin:35px auto;
    padding:0 24px;
}

.card{
    background:var(--card);
    padding:28px;
    border-radius:16px;
    box-shadow:
        0 25px 60px rgba(0,0,0,.08),
        inset 0 1px 0 rgba(255,255,255,.7);
}

/* ================= HEADINGS ================= */
h2{
    font-size:22px;
    font-weight:700;
    margin-bottom:20px;
    color:#0f172a;
}

/* ================= MESSAGES ================= */
.msg{
    padding:14px 18px;
    border-radius:12px;
    margin-bottom:18px;
    font-weight:600;
}

.success{
    background:linear-gradient(135deg,#dcfce7,#bbf7d0);
    color:#065f46;
}

.error{
    background:linear-gradient(135deg,#fee2e2,#fecaca);
    color:#7f1d1d;
}

/* ================= TABLE ================= */
table{
    width:100%;
    border-collapse:separate;
    border-spacing:0 12px;
    margin-top:10px;
}

thead th{
    text-align:left;
    padding:14px 16px;
    font-size:12px;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:var(--muted);
}

tbody tr{
    background:#fff;
    border-radius:14px;
    box-shadow:0 10px 25px rgba(0,0,0,.06);
    transition:transform .25s ease, box-shadow .25s ease;
}

tbody tr:hover{
    transform:translateY(-3px);
    box-shadow:0 18px 45px rgba(0,0,0,.12);
}

tbody td{
    padding:16px;
    border-top:1px solid var(--border);
    border-bottom:1px solid var(--border);
}

tbody td:first-child{
    border-left:1px solid var(--border);
    border-radius:14px 0 0 14px;
}

tbody td:last-child{
    border-right:1px solid var(--border);
    border-radius:0 14px 14px 0;
}

/* ================= BADGES ================= */
.badge{
    padding:6px 16px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
    letter-spacing:.04em;
    display:inline-block;
}

.paid{
    background:linear-gradient(135deg,#dcfce7,#86efac);
    color:#14532d;
}

.pending{
    background:linear-gradient(135deg,#fee2e2,#fca5a5);
    color:#7f1d1d;
}

/* ================= BUTTONS ================= */
.btn{
    padding:9px 14px;
    border-radius:10px;
    font-size:13px;
    font-weight:700;
    text-decoration:none;
    display:inline-block;
    transition:all .25s ease;
    border:none;
}

.view{
    background:linear-gradient(135deg,#38bdf8,#0ea5e9);
    color:#fff;
}

.view:hover{
    box-shadow:0 12px 28px rgba(14,165,233,.5);
    transform:translateY(-2px);
}

.del{
    background:linear-gradient(135deg,#ef4444,#b91c1c);
    color:#fff;
}

.del:hover{
    box-shadow:0 12px 28px rgba(239,68,68,.5);
    transform:translateY(-2px);
}

/* ================= RESPONSIVE ================= */
@media(max-width:900px){
    table,thead,tbody,tr,td,th{
        font-size:13px;
    }
    .header{
        flex-direction:column;
        gap:10px;
        text-align:center;
    }
}
</style>

</head>

<body>

<div class="header">
    <div>🧾 All Bills</div>
    <div>
        Welcome, <?= $_SESSION['username'] ?> |
        <a href="admin.php" style="color:white">Dashboard</a> |
        <a href="logout.php" style="color:white">Logout</a>
    </div>
</div>

<div class="container">
<div class="card">

<h2>Electricity Bills</h2>

<?php if($success_message): ?><div class="msg success"><?= $success_message ?></div><?php endif; ?>
<?php if($error_message): ?><div class="msg error"><?= $error_message ?></div><?php endif; ?>

<table>
<thead>
<tr>
    <th>ID</th>
    <th>Customer</th>
    <th>Meter</th>
    <th>Month / Year</th>
    <th>Units</th>
    <th>Total</th>
    <th>Paid</th>
    <th>Due</th>
    <th>Status</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>

<?php while($row = mysqli_fetch_assoc($result)): 

    $bill_id = $row['id'];
    $total = $row['bill_amount'] + ($row['acd_amount'] ?? 0);

    // paid
    $pay_q = mysqli_query($conn, "SELECT SUM(amount_paid) AS paid FROM payments WHERE bill_id=$bill_id");
    $pay = mysqli_fetch_assoc($pay_q);
    $paid = $pay['paid'] ?? 0;

    $due = $total - $paid;
    $status = ($due <= 0) ? 'paid' : 'pending';

    $monthYear = date('M Y', strtotime($row['bill_date']));
?>

<tr>
    <td>#<?= $bill_id ?></td>
    <td><?= strtoupper($row['name']) ?></td>
    <td><?= $row['customer_number'] ?></td>
    <td><?= $monthYear ?></td>
    <td>—</td>
    <td>₹<?= number_format($total,2) ?></td>
    <td>₹<?= number_format($paid,2) ?></td>
    <td>₹<?= number_format($due,2) ?></td>
    <td><span class="badge <?= $status ?>"><?= ucfirst($status) ?></span></td>
    <td>
        <a class="btn view" href="print_bill.php?id=<?= $bill_id ?>">View</a>
        <?php if($due <= 0): ?>
            <a class="btn del" href="?delete_bill=<?= $bill_id ?>" onclick="return confirm('Delete this paid bill?')">Delete</a>
        <?php endif; ?>
    </td>
</tr>

<?php endwhile; ?>

</tbody>
</table>

</div>
</div>

</body>
</html>
