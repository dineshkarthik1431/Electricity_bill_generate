<?php
session_start();
include "config.php";

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$bill_id = (int)($_GET['id'] ?? 0);

/* ================= FETCH BILL ================= */
$sql = "
    SELECT 
        b.id,
        b.customer_number,
        b.service_no,
        b.bill_date,
        b.due_date,
        b.bill_amount,
        b.acd_amount,
        c.name,
        c.address,
        c.phone,
        c.category
    FROM bills b
    JOIN customer c ON c.number = b.customer_number
    WHERE b.id = $bill_id
";

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 0) {
    die("Bill not found");
}

$bill = mysqli_fetch_assoc($result);

/* ================= PAYMENTS ================= */
$pay_q = mysqli_query(
    $conn,
    "SELECT SUM(amount_paid) AS paid FROM payments WHERE bill_id = $bill_id"
);
$pay = mysqli_fetch_assoc($pay_q);
$paid = $pay['paid'] ?? 0;

/* ================= TOTALS ================= */
$total = $bill['bill_amount'] + ($bill['acd_amount'] ?? 0);
$due   = $total - $paid;
$status = ($due <= 0) ? 'PAID' : 'PENDING';

$monthYear = date('F Y', strtotime($bill['bill_date']));
?>

<!DOCTYPE html>
<html>
<head>
<title>Print Bill #<?= $bill_id ?></title>

<style>
/* ================= PRINT RULES ================= */
@media print {
    body { background:#fff }
    .no-print { display:none }
    .bill { box-shadow:none; margin:0; width:100% }
}

/* ================= GLOBAL ================= */
*{
    box-sizing:border-box;
    font-family:"Inter","Segoe UI",system-ui,-apple-system,sans-serif;
}

body{
    background:linear-gradient(135deg,#eef2ff,#f8fafc);
    padding:30px;
    color:#0f172a;
}

/* ================= BILL CONTAINER ================= */
.bill{
    width:840px;
    margin:auto;
    background:#ffffff;
    padding:40px;
    border-radius:18px;
    box-shadow:
        0 30px 80px rgba(0,0,0,.15),
        inset 0 1px 0 rgba(255,255,255,.7);
}

/* ================= HEADER ================= */
.header{
    text-align:center;
    padding-bottom:25px;
    margin-bottom:35px;
    border-bottom:4px solid #1e293b;
}

.header h1{
    font-size:32px;
    letter-spacing:1px;
    margin-bottom:10px;
    color:#0f172a;
}

.header p{
    font-size:14px;
    color:#475569;
    font-weight:600;
}

/* ================= SECTIONS ================= */
.section{
    margin-bottom:32px;
}

.section h3{
    font-size:18px;
    margin-bottom:14px;
    color:#1e293b;
    border-left:6px solid #2563eb;
    padding-left:10px;
}

/* ================= ROWS ================= */
.row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:10px 0;
    border-bottom:1px dashed #e2e8f0;
}

.label{
    font-weight:600;
    color:#475569;
}

.row span:last-child{
    font-weight:700;
    color:#0f172a;
}

/* ================= BADGES ================= */
.badge{
    padding:6px 18px;
    border-radius:999px;
    font-size:13px;
    font-weight:800;
    letter-spacing:.05em;
}

.paid{
    background:linear-gradient(135deg,#dcfce7,#86efac);
    color:#14532d;
}

.pending{
    background:linear-gradient(135deg,#fee2e2,#fca5a5);
    color:#7f1d1d;
}

/* ================= TOTAL ================= */
.total{
    margin-top:30px;
    padding:22px;
    text-align:center;
    font-size:26px;
    font-weight:900;
    border-radius:14px;
    color:#fff;
    background:linear-gradient(135deg,#0f172a,#1e293b);
    box-shadow:0 20px 45px rgba(0,0,0,.35);
    letter-spacing:.5px;
}

/* ================= NOTE ================= */
.section p{
    font-size:13px;
    color:#475569;
    line-height:1.7;
}

/* ================= ACTION BUTTONS ================= */
.no-print{
    text-align:center;
    margin-top:30px;
}

.btn{
    padding:14px 28px;
    margin:0 8px;
    border:none;
    border-radius:999px;
    font-size:14px;
    font-weight:800;
    cursor:pointer;
    color:#fff;
    background:linear-gradient(135deg,#2563eb,#1e40af);
    box-shadow:0 12px 30px rgba(37,99,235,.45);
    transition:all .25s ease;
}

.btn:hover{
    transform:translateY(-2px);
    box-shadow:0 18px 45px rgba(37,99,235,.6);
}

/* ================= FOOTER FEEL ================= */
.bill::after{
    content:"Official Computer Generated Bill";
    display:block;
    text-align:center;
    margin-top:35px;
    font-size:12px;
    color:#94a3b8;
    letter-spacing:.12em;
}
</style>

</head>

<body>

<div class="bill">

<div class="header">
    <h1>ELECTRICITY BILL</h1>
    <p><strong>Service No:</strong> <?= $bill['service_no'] ?></p>
    <p><strong>Bill ID:</strong> #<?= $bill_id ?> | <?= $monthYear ?></p>
</div>

<div class="section">
    <h3>Customer Details</h3>
    <div class="row"><span class="label">Meter No</span><span><?= $bill['customer_number'] ?></span></div>
    <div class="row"><span class="label">Name</span><span><?= strtoupper($bill['name']) ?></span></div>
    <div class="row"><span class="label">Address</span><span><?= $bill['address'] ?></span></div>
    <div class="row"><span class="label">Phone</span><span><?= $bill['phone'] ?></span></div>
    <div class="row"><span class="label">Category</span><span><?= ucfirst($bill['category']) ?></span></div>
</div>

<div class="section">
    <h3>Bill Details</h3>
    <div class="row"><span class="label">Bill Date</span><span><?= date('d M Y', strtotime($bill['bill_date'])) ?></span></div>
    <div class="row"><span class="label">Due Date</span><span><?= date('d M Y', strtotime($bill['due_date'])) ?></span></div>
    <div class="row"><span class="label">Status</span>
        <span class="badge <?= strtolower($status) ?>"><?= $status ?></span>
    </div>
</div>

<div class="section">
    <h3>Amount Summary</h3>
    <div class="row"><span class="label">Energy Charges</span><span>₹<?= number_format($bill['bill_amount'],2) ?></span></div>
    <div class="row"><span class="label">Previous Due</span><span>₹<?= number_format($bill['acd_amount'],2) ?></span></div>
    <div class="row"><span class="label">Paid</span><span>₹<?= number_format($paid,2) ?></span></div>
</div>

<div class="total">
    TOTAL PAYABLE: ₹<?= number_format($due,2) ?>
</div>

<div class="section">
    <p><strong>Note:</strong> This is a computer generated bill. No signature required.</p>
</div>

</div>

<div class="no-print" style="text-align:center;margin-top:25px">
    <button class="btn" onclick="window.print()">🖨️ Print</button>
    <button class="btn" onclick="window.history.back()">⬅ Back</button>
</div>

</body>
</html>
