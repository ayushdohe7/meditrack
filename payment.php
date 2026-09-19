<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_any();
$role = current_role();
$pageTitle = "Payment";
$active = "payment";
$error = ""; $success = "";

if ($role === 'doctor') {
    $docId = $_SESSION['doctor_id'];

    // Doctor logs a new payment against an appointment
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $apptId = (int)$_POST['appointment_id'];
        $amount = (float)$_POST['amount'];
        $method = $_POST['method'] ?? 'Cash';
        $status = $_POST['status'] ?? 'Paid';

        $appt = $conn->query("SELECT patient_id FROM appointments WHERE id=$apptId AND doctor_id=$docId")->fetch_assoc();
        if ($appt) {
            $patId = $appt['patient_id'];
            $stmt = $conn->prepare("INSERT INTO payments (appointment_id, patient_id, doctor_id, amount, method, status) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("iiidss", $apptId, $patId, $docId, $amount, $method, $status);
            $stmt->execute();
            $success = "Payment recorded successfully.";
        } else {
            $error = "Invalid appointment selected.";
        }
    }

    $unpaidAppts = $conn->query("SELECT a.id, a.appointment_date, p.full_name FROM appointments a JOIN patients p ON p.id=a.patient_id
                                  WHERE a.doctor_id=$docId AND a.id NOT IN (SELECT appointment_id FROM payments) ORDER BY a.appointment_date DESC");

    $payments = $conn->query("SELECT pay.*, p.full_name FROM payments pay JOIN patients p ON p.id=pay.patient_id
                               WHERE pay.doctor_id=$docId ORDER BY pay.paid_on DESC LIMIT 50");

    $totalIncome = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE doctor_id=$docId AND status='Paid'")->fetch_assoc()['s'];
    $monthIncome = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE doctor_id=$docId AND status='Paid' AND MONTH(paid_on)=MONTH(CURDATE())")->fetch_assoc()['s'];
    $pendingCount = $conn->query("SELECT COUNT(*) c FROM payments WHERE doctor_id=$docId AND status='Pending'")->fetch_assoc()['c'];

} else {
    $patId = $_SESSION['patient_id'];

    if (isset($_GET['pay_id'])) {
        $pid = (int)$_GET['pay_id'];
        $conn->query("UPDATE payments SET status='Paid', paid_on=NOW() WHERE id=$pid AND patient_id=$patId");
        header("Location: payment.php");
        exit;
    }

    $payments = $conn->query("SELECT pay.*, d.full_name FROM payments pay JOIN doctors d ON d.id=pay.doctor_id
                               WHERE pay.patient_id=$patId ORDER BY pay.paid_on DESC LIMIT 50");
    $totalPaid = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE patient_id=$patId AND status='Paid'")->fetch_assoc()['s'];
    $totalDue  = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE patient_id=$patId AND status='Pending'")->fetch_assoc()['s'];
}

function payBadge($status) {
    $map = ['Paid'=>'badge-soft-success','Pending'=>'badge-soft-gold','Refunded'=>'badge-soft-danger'];
    $cls = $map[$status] ?? 'badge-soft-brand';
    return "<span class='badge-status $cls'>".h($status)."</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment - MediTrack</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/[email protected]/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <?php if ($success): ?><div class="alert alert-dark-custom py-2 px-3 mb-3"><i class="fa-solid fa-circle-check me-2 text-success"></i><?php echo h($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-dark-custom py-2 px-3 mb-3"><i class="fa-solid fa-circle-exclamation me-2 text-danger"></i><?php echo h($error); ?></div><?php endif; ?>

    <?php if ($role === 'doctor'): ?>
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <div class="card-panel stat-card">
            <div class="stat-icon" style="background:var(--success-soft);color:var(--success);"><i class="fa-solid fa-sack-dollar"></i></div>
            <div><h4>₹<?php echo number_format($totalIncome,0); ?></h4><p>Total Income</p></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card-panel stat-card">
            <div class="stat-icon" style="background:var(--brand-soft);color:var(--brand-2);"><i class="fa-solid fa-calendar-days"></i></div>
            <div><h4>₹<?php echo number_format($monthIncome,0); ?></h4><p>This Month's Income</p></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card-panel stat-card">
            <div class="stat-icon" style="background:var(--gold-soft);color:var(--gold);"><i class="fa-solid fa-hourglass-half"></i></div>
            <div><h4><?php echo (int)$pendingCount; ?></h4><p>Pending Payments</p></div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-4">
          <div class="card-panel p-3">
            <div class="panel-title">Record a Payment</div>
            <form method="POST">
              <div class="mb-3">
                <label class="form-label">Appointment</label>
                <select name="appointment_id" class="form-select" required>
                  <option value="">Select appointment...</option>
                  <?php if ($unpaidAppts): while ($ap = $unpaidAppts->fetch_assoc()): ?>
                    <option value="<?php echo $ap['id']; ?>"><?php echo h($ap['full_name']); ?> — <?php echo date('d M Y', strtotime($ap['appointment_date'])); ?></option>
                  <?php endwhile; endif; ?>
                </select>
              </div>
              <div class="mb-3"><label class="form-label">Amount (₹)</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
              <div class="mb-3"><label class="form-label">Method</label>
                <select name="method" class="form-select">
                  <option>Cash</option><option>Card</option><option>UPI</option><option>Insurance</option>
                </select>
              </div>
              <div class="mb-3"><label class="form-label">Status</label>
                <select name="status" class="form-select"><option>Paid</option><option>Pending</option></select>
              </div>
              <button class="btn btn-brand w-100">Save Payment</button>
            </form>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="card-panel p-3">
            <div class="panel-title">Payment History</div>
            <div class="table-responsive">
              <table class="table table-dark-custom">
                <thead><tr><th>Patient</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php if ($payments && $payments->num_rows>0): while ($pay = $payments->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo h($pay['full_name']); ?></td>
                    <td>₹<?php echo number_format($pay['amount'],2); ?></td>
                    <td><?php echo h($pay['method']); ?></td>
                    <td><?php echo payBadge($pay['status']); ?></td>
                    <td><?php echo date('d M Y', strtotime($pay['paid_on'])); ?></td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td colspan="5" class="text-center text-muted-c py-4">No payments recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    <?php else: ?>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <div class="card-panel stat-card">
            <div class="stat-icon" style="background:var(--success-soft);color:var(--success);"><i class="fa-solid fa-circle-check"></i></div>
            <div><h4>₹<?php echo number_format($totalPaid,0); ?></h4><p>Total Paid</p></div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card-panel stat-card">
            <div class="stat-icon" style="background:var(--gold-soft);color:var(--gold);"><i class="fa-solid fa-clock"></i></div>
            <div><h4>₹<?php echo number_format($totalDue,0); ?></h4><p>Pending Dues</p></div>
          </div>
        </div>
      </div>

      <div class="card-panel p-3">
        <div class="panel-title">My Payments</div>
        <div class="table-responsive">
          <table class="table table-dark-custom">
            <thead><tr><th>Doctor</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php if ($payments && $payments->num_rows>0): while ($pay = $payments->fetch_assoc()): ?>
              <tr>
                <td>Dr. <?php echo h($pay['full_name']); ?></td>
                <td>₹<?php echo number_format($pay['amount'],2); ?></td>
                <td><?php echo h($pay['method']); ?></td>
                <td><?php echo payBadge($pay['status']); ?></td>
                <td><?php echo date('d M Y', strtotime($pay['paid_on'])); ?></td>
                <td>
                  <?php if ($pay['status']==='Pending'): ?>
                    <a href="?pay_id=<?php echo $pay['id']; ?>" class="btn btn-brand btn-sm">Pay Now</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="6" class="text-center text-muted-c py-4">No payment records yet.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include 'includes/chatbot.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/[email protected]/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/chatbot.js"></script>
</body>
</html>
