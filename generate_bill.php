<?php
session_start();
include "config.php";

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','worker'])) {
    header("Location: login.php");
    exit;
}

/* ================= HELPERS ================= */
function get_minimum_charge($category) {
    switch ($category) {
        case 'commercial': return 100;
        case 'industrial': return 200;
        default: return 50;
    }
}

function calculate_bill($category, $units) {
    $min = get_minimum_charge($category);
    $amount = 0;

    if ($category === 'household') {
        if ($units <= 50) $amount = $units * 1.5;
        elseif ($units <= 100) $amount = (50*1.5) + (($units-50)*2);
        else $amount = (50*1.5) + (50*2) + (($units-100)*2.5);
    }
    elseif ($category === 'commercial') {
        if ($units <= 100) $amount = $units * 2;
        elseif ($units <= 500) $amount = (100*2) + (($units-100)*2.5);
        else $amount = (100*2) + (400*2.5) + (($units-500)*3);
    }
    else {
        if ($units <= 1000) $amount = $units * 2.5;
        elseif ($units <= 5000) $amount = (1000*2.5) + (($units-1000)*3);
        else $amount = (1000*2.5) + (4000*3) + (($units-5000)*3.5);
    }

    return max($min, $amount);
}

/* ================= LOGIC ================= */
$error = "";
$bill_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $number = $_POST['number'];
    $month  = $_POST['month'];
    $year   = $_POST['year'];

    /* CUSTOMER */
    $cust = mysqli_query($conn, "SELECT * FROM customer WHERE number='$number'");
    if (mysqli_num_rows($cust) == 0) {
        $error = "Customer not found";
    } else {

        $customer = mysqli_fetch_assoc($cust);
        $category = $customer['category'];

        /* CURRENT READING */
        $curr = mysqli_query($conn,
            "SELECT * FROM readings WHERE number='$number' AND month='$month' AND year='$year'"
        );

        if (mysqli_num_rows($curr) == 0) {
            $error = "No meter reading found for selected month/year";
        } else {

            $currRow = mysqli_fetch_assoc($curr);
            $current_reading = $currRow['reading'];

            /* PREVIOUS READING */
            $pm = ($month == 1) ? 12 : $month - 1;
            $py = ($month == 1) ? $year - 1 : $year;

            $prev = mysqli_query($conn,
                "SELECT reading FROM readings WHERE number='$number' AND month='$pm' AND year='$py'"
            );

            if (mysqli_num_rows($prev) > 0) {
                $prevRow = mysqli_fetch_assoc($prev);
                $units = max(0, $current_reading - $prevRow['reading']);
            } else {
                $units = $current_reading;
            }

            /* BILL CALCULATION */
            $energy_amount = calculate_bill($category, $units);

            /* PREVIOUS DUE (SIMPLIFIED) */
            $due = mysqli_query($conn,
                "SELECT bill_amount, acd_amount FROM bills WHERE customer_number='$number'"
            );
            $prev_due = 0;
            while ($d = mysqli_fetch_assoc($due)) {
                $prev_due += ($d['bill_amount'] + ($d['acd_amount'] ?? 0));
            }

            $gst  = $energy_amount * 0.18;
            $fine = $prev_due * 0.05;
            $total = $energy_amount + $gst + $fine + $prev_due;

            $due_date = date('Y-m-15', strtotime("+1 month"));
            $service_no = $year . str_pad($month,2,'0',STR_PAD_LEFT) . "-" . $number;

            $bill_data = [
                'customer' => $customer,
                'units' => $units,
                'energy' => $energy_amount,
                'gst' => $gst,
                'fine' => $fine,
                'prev_due' => $prev_due,
                'total' => $total,
                'due_date' => $due_date,
                'service_no' => $service_no
            ];

            /* SAVE BILL */
            if (isset($_POST['confirm'])) {
                $sql = "
                INSERT INTO bills (
                    customer_number,
                    service_no,
                    unique_service_no,
                    name,
                    bill_date,
                    due_date,
                    bill_amount,
                    acd_amount
                ) VALUES (
                    '$number',
                    '$service_no',
                    '$service_no',
                    '{$customer['name']}',
                    CURDATE(),
                    '$due_date',
                    '$energy_amount',
                    '$prev_due'
                )";

                if (mysqli_query($conn, $sql)) {
                    echo "<script>
                        alert('Bill generated successfully');
                        window.print();
                    </script>";
                } else {
                    $error = mysqli_error($conn);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Generate Bill</title>
<style>
/* ================= RESET ================= */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family: "Poppins", "Segoe UI", Arial, sans-serif;
}

/* ================= BODY ================= */
body{
    background: linear-gradient(135deg,#667eea,#764ba2);
    min-height:100vh;
    padding:30px 10px;
    color:#2c3e50;
}

/* ================= CONTAINER ================= */
.container{
    max-width:1000px;
    margin:auto;
}

/* ================= CARD ================= */
.card{
    background: #ffffff;
    padding:30px;
    border-radius:16px;
    margin-bottom:25px;
    box-shadow: 
        0 20px 40px rgba(0,0,0,0.15),
        inset 0 1px 0 rgba(255,255,255,0.6);
    animation: fadeUp .5s ease;
}

@keyframes fadeUp{
    from{opacity:0; transform:translateY(15px);}
    to{opacity:1; transform:translateY(0);}
}

/* ================= HEADINGS ================= */
h2{
    text-align:center;
    font-size:26px;
    font-weight:700;
    color:#4f46e5;
    margin-bottom:25px;
}

h3{
    font-size:22px;
    font-weight:600;
    color:#1e293b;
    margin-bottom:15px;
}

/* ================= FORM ================= */
label{
    display:block;
    margin-top:15px;
    font-weight:600;
    color:#475569;
}

input, select{
    width:100%;
    padding:12px 14px;
    margin-top:8px;
    border-radius:10px;
    border:1px solid #d1d5db;
    font-size:15px;
    transition: all .3s ease;
    background:#f9fafb;
}

input:focus, select:focus{
    outline:none;
    border-color:#6366f1;
    box-shadow:0 0 0 3px rgba(99,102,241,0.25);
    background:#fff;
}

/* ================= BUTTON ================= */
button{
    margin-top:25px;
    width:100%;
    padding:14px;
    font-size:16px;
    font-weight:600;
    border:none;
    border-radius:12px;
    cursor:pointer;
    background: linear-gradient(135deg,#6366f1,#4338ca);
    color:#fff;
    transition: all .3s ease;
}

button:hover{
    transform:translateY(-2px);
    box-shadow:0 12px 25px rgba(67,56,202,0.45);
}

/* ================= ERROR ================= */
.error{
    background: linear-gradient(135deg,#fee2e2,#fecaca);
    color:#7f1d1d;
    padding:15px;
    border-radius:12px;
    margin-bottom:20px;
    font-weight:500;
    border-left:6px solid #dc2626;
}

/* ================= BILL PREVIEW ================= */
.card p{
    font-size:15px;
    margin-bottom:8px;
    color:#334155;
}

.card p b{
    color:#0f172a;
}

/* ================= TOTAL ================= */
.card h2:last-of-type{
    margin-top:20px;
    background: linear-gradient(135deg,#22c55e,#16a34a);
    color:#fff;
    padding:15px;
    border-radius:12px;
    text-align:center;
    font-size:24px;
    box-shadow:0 10px 25px rgba(34,197,94,0.45);
}

/* ================= SAVE BUTTON ================= */
.card form button{
    background: linear-gradient(135deg,#f59e0b,#d97706);
}

.card form button:hover{
    box-shadow:0 12px 25px rgba(217,119,6,0.5);
}

/* ================= RESPONSIVE ================= */
@media(max-width:600px){
    h2{font-size:22px;}
    h3{font-size:18px;}
}
</style>

</head>
<body>

<div class="container">

<div class="card">
<h2>Generate Electricity Bill</h2>

<?php if ($error): ?>
<div class="error"><?= $error ?></div>
<?php endif; ?>

<form method="post">
<label>Meter Number</label>
<input name="number" required>

<label>Month</label>
<select name="month"><?php for($i=1;$i<=12;$i++) echo "<option value='$i'>$i</option>"; ?></select>

<label>Year</label>
<input name="year" value="<?= date('Y') ?>" required>

<br><br>
<button type="submit">Calculate</button>
</form>
</div>

<?php if ($bill_data): ?>
<div class="card">
<h3>Bill Preview</h3>
<p><b>Name:</b> <?= strtoupper($bill_data['customer']['name']) ?></p>
<p><b>Units:</b> <?= $bill_data['units'] ?> kWh</p>
<p><b>Energy:</b> ₹<?= number_format($bill_data['energy'],2) ?></p>
<p><b>GST:</b> ₹<?= number_format($bill_data['gst'],2) ?></p>
<p><b>Previous Due:</b> ₹<?= number_format($bill_data['prev_due'],2) ?></p>
<p><b>Fine:</b> ₹<?= number_format($bill_data['fine'],2) ?></p>
<h2>Total: ₹<?= number_format($bill_data['total'],2) ?></h2>

<form method="post">
<input type="hidden" name="number" value="<?= $number ?>">
<input type="hidden" name="month" value="<?= $month ?>">
<input type="hidden" name="year" value="<?= $year ?>">
<input type="hidden" name="confirm" value="1">
<button type="submit">Save & Print</button>
</form>
</div>
<?php endif; ?>

</div>
</body>
</html>
