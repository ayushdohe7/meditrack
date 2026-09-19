<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_doctor();
$docId = $_SESSION['doctor_id'];
$pageTitle = "Appointment Requests";
$active = "appointment_request";

if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'approve') {
        $conn->query("UPDATE appointments SET status='Approved' WHERE id=$id AND doctor_id=$docId");
    } elseif ($_GET['action'] === 'reject') {
        $conn->query("UPDATE appointments SET status='Cancelled' WHERE id=$id AND doctor_id=$docId");
    }
    header("Location: appointment_request.php");
    exit;
}

$requests = $conn->query("SELECT a.*, p.full_name, p.phone, p.gender, p.email FROM appointments a
                           JOIN patients p ON p.id = a.patient_id
                           WHERE a.doctor_id=$docId AND a.status='Pending'
                           ORDER BY a.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointment Requests - MediTrack</title>
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

    <div class="row g-3">
      <?php if ($requests && $requests->num_rows > 0): while ($r = $requests->fetch_assoc()): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card-panel p-3 h-100">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div class="avatar-fallback" style="width:46px;height:46px;"><?php echo strtoupper(substr($r['full_name'],0,1)); ?></div>
              <div>
                <div class="fw-bold"><?php echo h($r['full_name']); ?></div>
                <div class="text-muted-c" style="font-size:12px;"><?php echo h($r['email']); ?></div>
              </div>
            </div>
            <div class="mb-2" style="font-size:13px;">
              <div class="text-muted-c">Reason</div>
              <div><?php echo h($r['diagnosis']); ?></div>
            </div>
            <div class="row mb-3" style="font-size:12.5px;">
              <div class="col-6"><span class="text-muted-c d-block">Date</span><?php echo date('d M Y', strtotime($r['appointment_date'])); ?></div>
              <div class="col-6"><span class="text-muted-c d-block">Time</span><?php echo date('h:i A', strtotime($r['appointment_time'])); ?></div>
              <div class="col-6 mt-2"><span class="text-muted-c d-block">Phone</span><?php echo h($r['phone'] ?: '-'); ?></div>
              <div class="col-6 mt-2"><span class="text-muted-c d-block">Gender</span><?php echo h($r['gender'] ?: '-'); ?></div>
            </div>
            <div class="d-flex gap-2">
              <a href="?action=approve&id=<?php echo $r['id']; ?>" class="btn btn-brand btn-sm flex-grow-1"><i class="fa-solid fa-check me-1"></i>Approve</a>
              <a href="?action=reject&id=<?php echo $r['id']; ?>" class="btn btn-sm flex-grow-1" style="background:var(--bg-panel-2);color:var(--danger);border:1px solid var(--border);" onclick="return confirm('Reject this request?');"><i class="fa-solid fa-xmark me-1"></i>Reject</a>
            </div>
          </div>
        </div>
      <?php endwhile; else: ?>
        <div class="col-12">
          <div class="card-panel p-5 text-center text-muted-c">
            <i class="fa-solid fa-inbox mb-2" style="font-size:32px;"></i>
            <p class="mb-0">No pending appointment requests right now.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'includes/chatbot.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/[email protected]/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/chatbot.js"></script>
</body>
</html>
