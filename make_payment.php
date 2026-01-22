<?php
session_start();
include "config.php";

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}

$bill_id = (int)($_GET['id'] ?? 0);
$error = "";
$success = "";

/* ================= FETCH BILL ================= */
$sql = "
    SELECT 
        b.id,
        b.customer_number,
        b.bill_amount,
        b.acd_amount,
        b.bill_date,
        b.due_date,
        c.name
    FROM bills b
    JOIN customer c ON c.number = b.customer_number
    WHERE b.id = $bill_id
";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 0) {
    die("Invalid Bill ID");
}

$bill = mysqli_fetch_assoc($result);

/* ================= TOTAL / PAID / DUE ================= */
$total = $bill['bill_amount'] + ($bill['acd_amount'] ?? 0);

$pay_q = mysqli_query(
    $conn,
    "SELECT SUM(amount_paid) AS paid 
     FROM payments 
     WHERE bill_id = $bill_id AND status = 'completed'"
);
$paid = mysqli_fetch_assoc($pay_q)['paid'] ?? 0;
$due  = $total - $paid;

/* ================= HANDLE PAYMENT ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount = (float)$_POST['amount'];
    $method = $_POST['payment_method'];
    $txn    = trim($_POST['transaction_id']);

    if ($amount <= 0) {
        $error = "Invalid payment amount";
    } elseif ($amount > $due) {
        $error = "Payment exceeds due amount";
    } else {

        $insert = "
            INSERT INTO payments (
                bill_id, number, amount_paid, payment_date,
                payment_method, transaction_id, status
            ) VALUES (
                '$bill_id',
                '{$bill['customer_number']}',
                '$amount',
                CURDATE(),
                '$method',
                '$txn',
                'completed'
            )
        ";

        if (mysqli_query($conn, $insert)) {
            $success = "Payment of ₹" . number_format($amount,2) . " successful";
            header("refresh:1;url=customer.php");
        } else {
            $error = mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Pay Bill</title>

<style>
body{
    font-family:Segoe UI,Arial;
    background:#f1f5f9;
}
.container{
    max-width:700px;
    margin:40px auto;
}
.card{
    background:#fff;
    padding:30px;
    border-radius:14px;
    box-shadow:0 15px 40px rgba(0,0,0,.1);
}
h2{
    margin-bottom:20px;
    color:#0f172a;
}
.row{
    display:flex;
    justify-content:space-between;
    margin-bottom:12px;
}
.label{
    font-weight:600;
    color:#475569;
}
.value{
    font-weight:700;
}
.total{
    background:#0f172a;
    color:#fff;
    padding:18px;
    text-align:center;
    font-size:20px;
    border-radius:10px;
    margin:20px 0;
}
input,select{
    width:100%;
    padding:12px;
    margin-top:6px;
    border-radius:8px;
    border:1px solid #cbd5e1;
}
button{
    margin-top:20px;
    width:100%;
    padding:14px;
    border:none;
    border-radius:999px;
    font-size:16px;
    font-weight:700;
    background:linear-gradient(135deg,#22c55e,#16a34a);
    color:#fff;
    cursor:pointer;
}
button:hover{
    opacity:.9;
}
.msg{
    padding:14px;
    border-radius:10px;
    margin-bottom:20px;
}
.error{
    background:#fee2e2;
    color:#7f1d1d;
}
.success{
    background:#dcfce7;
    color:#14532d;
}
</style>
</head>

<body>

<div class="container">
<div class="card">

<h2>Pay Electricity Bill</h2>

<?php if($error): ?>
<div class="msg error"><?= $error ?></div>
<?php endif; ?>

<?php if($success): ?>
<div class="msg success"><?= $success ?></div>
<?php endif; ?>

<div class="row">
    <span class="label">Customer</span>
    <span class="value"><?= strtoupper($bill['name']) ?></span>
</div>

<div class="row">
    <span class="label">Meter Number</span>
    <span class="value"><?= $bill['customer_number'] ?></span>
</div>

<div class="row">
    <span class="label">Bill Date</span>
    <span class="value"><?= date('d M Y',strtotime($bill['bill_date'])) ?></span>
</div>

<div class="row">
    <span class="label">Due Date</span>
    <span class="value"><?= date('d M Y',strtotime($bill['due_date'])) ?></span>
</div>

<div class="total">
    DUE AMOUNT : ₹<?= number_format($due,2) ?>
</div>

<?php if($due > 0): ?>
<form method="post">

<label>Payment Amount</label>
<input type="number" name="amount" step="0.01" max="<?= $due ?>" required>

<label>Payment Method</label>
<select name="payment_method" required>
    <option value="">Select</option>
    <option value="cash">Cash</option>
    <option value="upi">UPI</option>
    <option value="card">Card</option>
    <option value="net_banking">Net Banking</option>
</select>

<label>Transaction ID (optional)</label>
<input type="text" name="transaction_id">

<button type="submit">PAY NOW</button>

</form>
<?php else: ?>
<div class="msg success">This bill is already fully paid.</div>
<?php endif; ?>

</div>
</div>

</body>
</html>
