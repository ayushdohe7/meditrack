<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_any();
$role = current_role();
$pageTitle = "Appointments";
$active = "appointments";

// Handle quick actions
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($role === 'doctor' && $_GET['action'] === 'complete') {
        $conn->query("UPDATE appointments SET status='Completed' WHERE id=$id AND doctor_id={$_SESSION['doctor_id']}");
    } elseif ($role === 'doctor' && $_GET['action'] === 'ongoing') {
        $conn->query("UPDATE appointments SET status='OnGoing' WHERE id=$id AND doctor_id={$_SESSION['doctor_id']}");
    } elseif ($_GET['action'] === 'cancel') {
        if ($role === 'doctor') {
            $conn->query("UPDATE appointments SET status='Cancelled' WHERE id=$id AND doctor_id={$_SESSION['doctor_id']}");
        } else {
            $conn->query("UPDATE appointments SET status='Cancelled' WHERE id=$id AND patient_id={$_SESSION['patient_id']}");
        }
    }
    header("Location: appointments.php");
    exit;
}

$filter = $_GET['status'] ?? 'All';
$where = "";
if ($filter !== 'All') {
    $filterSafe = $conn->real_escape_string($filter);
    $where = " AND a.status='$filterSafe' ";
}

if ($role === 'doctor') {
    $docId = $_SESSION['doctor_id'];
    $sql = "SELECT a.*, p.full_name, p.phone, p.gender FROM appointments a JOIN patients p ON p.id=a.patient_id
            WHERE a.doctor_id=$docId $where ORDER BY a.appointment_date DESC, a.appointment_time DESC";
} else {
    $patId = $_SESSION['patient_id'];
    $sql = "SELECT a.*, d.full_name, d.specialization FROM appointments a JOIN doctors d ON d.id=a.doctor_id
            WHERE a.patient_id=$patId $where ORDER BY a.appointment_date DESC, a.appointment_time DESC";
}
$appointments = $conn->query($sql);

function statusBadge2($status) {
    $map = ['Pending'=>'badge-soft-gold','Approved'=>'badge-soft-brand','OnGoing'=>'badge-soft-brand','Completed'=>'badge-soft-success','Cancelled'=>'badge-soft-danger'];
    $cls = $map[$status] ?? 'badge-soft-brand';
    return "<span class='badge-status $cls'>".h($status)."</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointments - MediTrack</title>
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

    <div class="card-panel p-3">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="btn-group">
          <?php foreach (['All','Pending','Approved','OnGoing','Completed','Cancelled'] as $s): ?>
            <a href="?status=<?php echo $s; ?>" class="btn btn-sm <?php echo $filter===$s ? 'btn-brand' : ''; ?>" style="<?php echo $filter!==$s ? 'background:var(--bg-panel-2);color:var(--text-main);border:1px solid var(--border);' : ''; ?>"><?php echo $s; ?></a>
          <?php endforeach; ?>
        </div>
        <a href="appointment_page.php" class="btn btn-brand btn-sm"><i class="fa-solid fa-plus me-1"></i>New Appointment</a>
      </div>

      <div class="table-responsive">
        <table class="table table-dark-custom align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th><?php echo $role==='doctor' ? 'Patient' : 'Doctor'; ?></th>
              <th>Reason</th>
              <th>Date</th>
              <th>Time</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($appointments && $appointments->num_rows > 0): $i=1; while ($row = $appointments->fetch_assoc()): ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar-fallback"><?php echo strtoupper(substr($row['full_name'],0,1)); ?></div>
                  <div>
                    <div class="fw-semibold"><?php echo h($row['full_name']); ?></div>
                    <?php if ($role==='doctor'): ?><div class="text-muted-c" style="font-size:11px;"><?php echo h($row['phone']); ?></div><?php else: ?><div class="text-muted-c" style="font-size:11px;"><?php echo h($row['specialization']); ?></div><?php endif; ?>
                  </div>
                </div>
              </td>
              <td><?php echo h($row['diagnosis']); ?></td>
              <td><?php echo date('d M Y', strtotime($row['appointment_date'])); ?></td>
              <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
              <td><?php echo statusBadge2($row['status']); ?></td>
              <td class="text-end">
                <div class="d-flex gap-2 justify-content-end">
                  <?php if ($role==='doctor' && $row['status']==='Approved'): ?>
                    <a href="?action=ongoing&id=<?php echo $row['id']; ?>" class="btn-icon-sm approve" title="Start"><i class="fa-solid fa-play"></i></a>
                  <?php endif; ?>
                  <?php if ($role==='doctor' && in_array($row['status'], ['Approved','OnGoing'])): ?>
                    <a href="?action=complete&id=<?php echo $row['id']; ?>" class="btn-icon-sm approve" title="Mark Completed"><i class="fa-solid fa-check"></i></a>
                  <?php endif; ?>
                  <?php if (!in_array($row['status'], ['Completed','Cancelled'])): ?>
                    <a href="?action=cancel&id=<?php echo $row['id']; ?>" class="btn-icon-sm reject" title="Cancel" onclick="return confirm('Cancel this appointment?');"><i class="fa-solid fa-ban"></i></a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="7" class="text-center text-muted-c py-4">No appointments found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/chatbot.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/[email protected]/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/chatbot.js"></script>
</body>
</html>
