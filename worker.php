<?php



session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'worker') {
    header("Location: login.php");
    exit;
}
?>

<?php
// Handle reading submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {

    $number  = $_POST['number'];
    $month   = $_POST['month'];
    $year    = $_POST['year'];
    $reading = $_POST['reading'];

    // Check if customer exists
    $check = mysqli_query($conn, "SELECT number FROM customer WHERE number = '$number'");

    if (mysqli_num_rows($check) == 0) {
        $error = "Customer not found!";
    } else {

        // Insert reading
        $sql = "INSERT INTO readings (number, month, year, reading, read_date)
                VALUES ('$number', '$month', '$year', '$reading', CURDATE())";

        if (mysqli_query($conn, $sql)) {
            $success = "Reading saved successfully!";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Worker Dashboard</title>
    <style>
        /* ================== GLOBAL ================== */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #eef2f7, #f8fafc);
    color: #1f2937;
}

/* ================== HEADER ================== */
.header {
    background: linear-gradient(135deg, #0f766e, #16a34a);
    color: #ffffff;
    padding: 18px 36px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
}

.logo {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 0.6px;
}

.nav a {
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    margin-left: 18px;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav a:hover,
.nav a.active {
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
}

/* ================== CONTAINER ================== */
.container {
    max-width: 1300px;
    margin: 40px auto;
    padding: 0 24px;
}

/* ================== CARDS ================== */
.card {
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(14px);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 32px;
    box-shadow:
        0 20px 40px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 rgba(255,255,255,0.7);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow:
        0 28px 60px rgba(0, 0, 0, 0.12),
        inset 0 1px 0 rgba(255,255,255,0.7);
}

/* ================== MESSAGES ================== */
.message {
    padding: 16px 20px;
    border-radius: 14px;
    margin-bottom: 24px;
    font-size: 14px;
    font-weight: 500;
}

.success {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #065f46;
}

.error {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #7f1d1d;
}

/* ================== FORM ================== */
.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
}

input,
select {
    width: 100%;
    padding: 13px 16px;
    border-radius: 14px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    font-size: 14px;
    transition: all 0.25s ease;
}

input:focus,
select:focus {
    outline: none;
    background: #ffffff;
    border-color: #22c55e;
    box-shadow: 0 0 0 4px rgba(34,197,94,0.25);
}

/* ================== BUTTONS ================== */
.btn {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #ffffff;
    padding: 14px 30px;
    border: none;
    border-radius: 999px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.4px;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(34,197,94,0.45);
}

/* ================== TABLE ================== */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 12px;
    margin-top: 24px;
}

thead th {
    text-align: left;
    padding: 14px 16px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #64748b;
}

tbody tr {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 10px 22px rgba(0,0,0,0.06);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

tbody tr:hover {
    transform: scale(1.01);
    box-shadow: 0 16px 36px rgba(0,0,0,0.1);
}

tbody td {
    padding: 16px;
    font-size: 14px;
    color: #1f2937;
}

/* ================== RESPONSIVE ================== */
@media (max-width: 768px) {
    .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .nav a {
        margin-left: 0;
        margin-right: 10px;
    }

    .card {
        padding: 22px;
    }
}

    </style>
</head>
<body>
    <div class="header">
        <div class="logo">👷 Worker Panel</div>
        <div class="nav">
            <span>Welcome, <?php echo $_SESSION['username']; ?></span>
            <a href="worker.php">Dashboard</a>
            <a href="generate_bill.php">Generate Bills</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <h2>Worker Dashboard</h2>
        
        <?php if(isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>📝 Add Meter Reading</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Meter Number *</label>
                    <input type="number" name="number" required min="1000" max="99999">
                </div>
                
                <div class="form-group">
                    <label>Month *</label>
                    <select name="month" required>
                        <option value="">Select Month</option>
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo date('F', mktime(0,0,0,$i,1)); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Year *</label>
                    <input type="number" name="year" value="<?php echo date('Y'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Current Reading (kWh) *</label>
                    <input type="number" step="0.01" name="reading" min="0" required>
                </div>
                
                <button type="submit" name="submit" class="btn">Submit Reading</button>
            </form>
        </div>
        
        <div class="card">
            <h3>📊 Recent Readings</h3>
            <?php
            $sql = "SELECT r.*, c.name FROM readings r 
                    JOIN customer c ON r.number = c.number 
                    ORDER BY r.read_date DESC LIMIT 15";
            $result = mysqli_query($conn, $sql);
            
            if (mysqli_num_rows($result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Meter No.</th>
                            <th>Customer</th>
                            <th>Month/Year</th>
                            <th>Reading</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['read_date']; ?></td>
                            <td><?php echo $row['number']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $row['month'] . '/' . $row['year']; ?></td>
                            <td><?php echo $row['reading']; ?> kWh</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No readings found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>