<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_any();
$role = current_role();
$pageTitle = "Dashboard";
$active = "dashboard";

if ($role === 'doctor') {
    $docId = $_SESSION['doctor_id'];

    $totalPatients = $conn->query("SELECT COUNT(DISTINCT patient_id) c FROM appointments WHERE doctor_id=$docId")->fetch_assoc()['c'];
    $todayPatients  = $conn->query("SELECT COUNT(DISTINCT patient_id) c FROM appointments WHERE doctor_id=$docId AND appointment_date=CURDATE()")->fetch_assoc()['c'];
    $todayAppts     = $conn->query("SELECT COUNT(*) c FROM appointments WHERE doctor_id=$docId AND appointment_date=CURDATE()")->fetch_assoc()['c'];
    $monthAppts     = $conn->query("SELECT COUNT(*) c FROM appointments WHERE doctor_id=$docId AND MONTH(appointment_date)=MONTH(CURDATE()) AND YEAR(appointment_date)=YEAR(CURDATE())")->fetch_assoc()['c'];
    $monthIncome    = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE doctor_id=$docId AND status='Paid' AND MONTH(paid_on)=MONTH(CURDATE()) AND YEAR(paid_on)=YEAR(CURDATE())")->fetch_assoc()['s'];

    $newPatients = $conn->query("SELECT COUNT(DISTINCT a.patient_id) c FROM appointments a JOIN patients p ON p.id=a.patient_id WHERE a.doctor_id=$docId AND MONTH(p.created_at)=MONTH(CURDATE()) AND YEAR(p.created_at)=YEAR(CURDATE())")->fetch_assoc()['c'];
    $oldPatients = max(0, $totalPatients - $newPatients);

    $todayList = $conn->query("SELECT a.*, p.full_name, p.photo FROM appointments a JOIN patients p ON p.id=a.patient_id
                                WHERE a.doctor_id=$docId AND a.appointment_date=CURDATE() ORDER BY a.appointment_time ASC LIMIT 6");

    $nextPatient = $conn->query("SELECT a.*, p.* , a.id as appt_id FROM appointments a JOIN patients p ON p.id=a.patient_id
                                  WHERE a.doctor_id=$docId AND a.appointment_date>=CURDATE() AND a.status IN ('Pending','Approved','OnGoing')
                                  ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 1")->fetch_assoc();

    $requestList = $conn->query("SELECT a.*, p.full_name, p.photo FROM appointments a JOIN patients p ON p.id=a.patient_id
                                  WHERE a.doctor_id=$docId AND a.status='Pending' ORDER BY a.created_at DESC LIMIT 4");
} else {
    $patId = $_SESSION['patient_id'];

    $totalAppts   = $conn->query("SELECT COUNT(*) c FROM appointments WHERE patient_id=$patId")->fetch_assoc()['c'];
    $upcoming     = $conn->query("SELECT COUNT(*) c FROM appointments WHERE patient_id=$patId AND appointment_date>=CURDATE() AND status IN ('Pending','Approved','OnGoing')")->fetch_assoc()['c'];
    $monthAppts   = $conn->query("SELECT COUNT(*) c FROM appointments WHERE patient_id=$patId AND MONTH(appointment_date)=MONTH(CURDATE()) AND YEAR(appointment_date)=YEAR(CURDATE())")->fetch_assoc()['c'];
    $monthPaid    = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE patient_id=$patId AND status='Paid' AND MONTH(paid_on)=MONTH(CURDATE()) AND YEAR(paid_on)=YEAR(CURDATE())")->fetch_assoc()['s'];

    $completed = $conn->query("SELECT COUNT(*) c FROM appointments WHERE patient_id=$patId AND status='Completed'")->fetch_assoc()['c'];
    $pendingCount = max(0, $totalAppts - $completed);

    $todayList = $conn->query("SELECT a.*, d.full_name, d.photo, d.specialization FROM appointments a JOIN doctors d ON d.id=a.doctor_id
                                WHERE a.patient_id=$patId AND a.appointment_date=CURDATE() ORDER BY a.appointment_time ASC LIMIT 6");

    $nextPatient = $conn->query("SELECT a.*, d.full_name, d.specialization, d.phone, d.photo FROM appointments a JOIN doctors d ON d.id=a.doctor_id
                                  WHERE a.patient_id=$patId AND a.appointment_date>=CURDATE() AND a.status IN ('Pending','Approved','OnGoing')
                                  ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 1")->fetch_assoc();

    $requestList = $conn->query("SELECT a.*, d.full_name, d.photo FROM appointments a JOIN doctors d ON d.id=a.doctor_id
                                  WHERE a.patient_id=$patId AND a.status='Pending' ORDER BY a.created_at DESC LIMIT 4");
}

function statusBadge($status) {
    $map = [
        'Pending'   => 'badge-soft-gold',
        'Approved'  => 'badge-soft-brand',
        'OnGoing'   => 'badge-soft-brand',
        'Completed' => 'badge-soft-success',
        'Cancelled' => 'badge-soft-danger',
    ];
    $cls = $map[$status] ?? 'badge-soft-brand';
    return "<span class='badge-status $cls'>" . h($status) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard - MediTrack</title>
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

    <!-- Stat Cards -->
    <div class="row g-3 mb-3">
      <?php if ($role === 'doctor'): ?>
        <div class="col-md-3 col-sm-6">
          <div class="card-panel stat-card">
            <div class="stat-icon" style="background:var(--brand-soft);color:var(--brand-2);"><i class="fa-solid fa-user-injured"></i></div>
            <div><h4><?php echo (int)$totalPatients; ?></h4><p>Total Patients</p></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card-panel stat-card">
            <div class="stat-icon" style="background:var(--gold-soft);color:var(--gold);"><i class="fa-solid fa-calendar-day"></i></div>
            <div><h4><?php echo (int)$todayAppts; ?></h4><p>Today's Appointments</p></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card-panel stat-card">
            <div class="stat-icon" style="background:var(--success-soft);color:var(--success);"><i class="fa-solid fa-calendar-check"></i></div>
            <div><h4><?php echo (int)$monthAppts; ?></h4><p>Monthly Total Appointments</p></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card-panel stat-card">
            <div class="stat-icon" style="background:var(--danger-soft);color:var(--danger);"><i class="fa-solid fa-sack-dollar"></i></div>
            <div><h4>₹<?php echo number_format($monthIncome,0); ?></h4><p>Monthly Total Income</p></div>
          </div>
        </div>
      <?php else: ?>
        <div class="col-md-3 col-sm-6">
          <div class="card-panel stat-card">
            <div class="stat-icon" style="background:var(--brand-soft);color:var(--brand-2);"><i class="fa-solid fa-calendar"></i></div>
            <div><h4><?php echo (int)$totalAppts; ?></h4><p>Total Appointments</p></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card-panel stat-card">
            <div class="stat-icon" style="background:var(--gold-soft);color:var(--gold);"><i class="fa-solid fa-hourglass-half"></i></div>
            <div><h4><?php echo (int)$upcoming; ?></h4><p>Upcoming Appointments</p></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card-panel stat-card">
            <div class="stat-icon" style="background:var(--success-soft);color:var(--success);"><i class="fa-solid fa-calendar-check"></i></div>
            <div><h4><?php echo (int)$monthAppts; ?></h4><p>This Month's Appointments</p></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card-panel stat-card">
            <div class="stat-icon" style="background:var(--danger-soft);color:var(--danger);"><i class="fa-solid fa-sack-dollar"></i></div>
            <div><h4>₹<?php echo number_format($monthPaid,0); ?></h4><p>Paid This Month</p></div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Row 2 -->
    <div class="row g-3 mb-3">
      <div class="col-lg-4">
        <div class="card-panel p-3 h-100">
          <div class="panel-title"><?php echo $role==='doctor' ? 'Patients Summary' : 'Appointments Summary'; ?></div>
          <canvas id="summaryChart" height="200"></canvas>
          <div class="d-flex justify-content-around mt-3" style="font-size:12px;">
            <?php if ($role === 'doctor'): ?>
              <span><i class="fa-solid fa-circle me-1" style="color:var(--gold);"></i>New (<?php echo $newPatients; ?>)</span>
              <span><i class="fa-solid fa-circle me-1" style="color:var(--brand);"></i>Returning (<?php echo $oldPatients; ?>)</span>
            <?php else: ?>
              <span><i class="fa-solid fa-circle me-1" style="color:var(--gold);"></i>Pending (<?php echo $pendingCount; ?>)</span>
              <span><i class="fa-solid fa-circle me-1" style="color:var(--success);"></i>Completed (<?php echo $completed; ?>)</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card-panel p-3 h-100">
          <div class="d-flex justify-content-between align-items-center">
            <div class="panel-title mb-0">Today's Appointment</div>
            <a href="appointments.php" class="text-muted-c" style="font-size:12px;">See All</a>
          </div>
          <?php if ($todayList && $todayList->num_rows > 0): while ($row = $todayList->fetch_assoc()): ?>
            <div class="list-row">
              <?php $pic = $role==='doctor' ? ($row['photo'] ?? '') : ($row['photo'] ?? ''); ?>
              <?php if (!empty($pic) && file_exists($pic)): ?>
                <img src="/meditrack/<?php echo h($pic); ?>" class="avatar-sm">
              <?php else: ?>
                <div class="avatar-fallback"><?php echo strtoupper(substr($row['full_name'],0,1)); ?></div>
              <?php endif; ?>
              <div class="flex-grow-1">
                <div class="fw-semibold" style="font-size:13px;"><?php echo h($row['full_name']); ?></div>
                <div class="text-muted-c" style="font-size:11.5px;"><?php echo h($row['diagnosis']); ?></div>
              </div>
              <div class="text-end">
                <div style="font-size:12px;"><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></div>
                <?php echo statusBadge($row['status']); ?>
              </div>
            </div>
          <?php endwhile; else: ?>
            <p class="text-muted-c mt-3">No appointments scheduled for today.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card-panel p-3 h-100">
          <div class="panel-title">Next Patient Details</div>
          <?php if ($nextPatient): ?>
            <div class="d-flex align-items-center gap-2 mb-3">
              <div class="avatar-fallback" style="width:44px;height:44px;">
                <?php echo strtoupper(substr($nextPatient['full_name'],0,1)); ?>
              </div>
              <div>
                <div class="fw-bold"><?php echo h($nextPatient['full_name']); ?></div>
                <div class="text-muted-c" style="font-size:12px;"><?php echo h($nextPatient['diagnosis']); ?></div>
              </div>
            </div>
            <div class="row" style="font-size:12.5px;">
              <div class="col-6 mb-2"><span class="text-muted-c d-block">Date</span><?php echo date('d M Y', strtotime($nextPatient['appointment_date'])); ?></div>
              <div class="col-6 mb-2"><span class="text-muted-c d-block">Time</span><?php echo date('h:i A', strtotime($nextPatient['appointment_time'])); ?></div>
              <?php if ($role==='doctor'): ?>
                <div class="col-6 mb-2"><span class="text-muted-c d-block">Phone</span><?php echo h($nextPatient['phone'] ?? '-'); ?></div>
                <div class="col-6 mb-2"><span class="text-muted-c d-block">Gender</span><?php echo h($nextPatient['gender'] ?? '-'); ?></div>
              <?php else: ?>
                <div class="col-6 mb-2"><span class="text-muted-c d-block">Doctor</span>Dr. <?php echo h($nextPatient['full_name']); ?></div>
                <div class="col-6 mb-2"><span class="text-muted-c d-block">Speciality</span><?php echo h($nextPatient['specialization'] ?? '-'); ?></div>
              <?php endif; ?>
            </div>
            <?php echo statusBadge($nextPatient['status']); ?>
          <?php else: ?>
            <p class="text-muted-c">No upcoming appointments.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Row 3 -->
    <div class="row g-3">
      <div class="col-lg-8">
        <div class="card-panel p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div class="panel-title mb-0">Appointment Requests</div>
            <a href="<?php echo $role==='doctor' ? 'appointment_request.php' : 'appointments.php'; ?>" class="text-muted-c" style="font-size:12px;">See All</a>
          </div>
          <?php if ($requestList && $requestList->num_rows > 0): while ($row = $requestList->fetch_assoc()): ?>
            <div class="list-row">
              <div class="avatar-fallback"><?php echo strtoupper(substr($row['full_name'],0,1)); ?></div>
              <div class="flex-grow-1">
                <div class="fw-semibold" style="font-size:13px;"><?php echo h($row['full_name']); ?></div>
                <div class="text-muted-c" style="font-size:11.5px;"><?php echo h($row['diagnosis']); ?> · <?php echo date('d M, h:i A', strtotime($row['appointment_date'].' '.$row['appointment_time'])); ?></div>
              </div>
              <?php if ($role === 'doctor'): ?>
              <div class="d-flex gap-2">
                <a href="appointment_request.php?action=approve&id=<?php echo $row['id']; ?>" class="btn-icon-sm approve"><i class="fa-solid fa-check"></i></a>
                <a href="appointment_request.php?action=reject&id=<?php echo $row['id']; ?>" class="btn-icon-sm reject" onclick="return confirm('Reject this request?');"><i class="fa-solid fa-xmark"></i></a>
              </div>
              <?php else: ?>
                <?php echo statusBadge($row['status']); ?>
              <?php endif; ?>
            </div>
          <?php endwhile; else: ?>
            <p class="text-muted-c mt-3">No pending requests.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card-panel p-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="panel-title mb-0">Calendar</div>
            <span class="text-muted-c" style="font-size:12px;"><?php echo date('F Y'); ?></span>
          </div>
          <div class="mini-calendar" id="miniCalendar"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/chatbot.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/[email protected]/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/[email protected]"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/chatbot.js"></script>
<script>
// Donut chart
const ctx = document.getElementById('summaryChart');
new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: [<?php echo $role==='doctor' ? "'New Patients','Returning Patients'" : "'Pending','Completed'"; ?>],
    datasets: [{
      data: [<?php echo $role==='doctor' ? "$newPatients,$oldPatients" : "$pendingCount,$completed"; ?>],
      backgroundColor: ['#f5a524', '#5b6cf9'],
      borderWidth: 0,
      cutout: '70%'
    }]
  },
  options: { plugins: { legend: { display:false } } }
});

// Mini calendar
(function(){
  const el = document.getElementById('miniCalendar');
  const now = new Date();
  const year = now.getFullYear(), month = now.getMonth();
  const first = new Date(year, month, 1);
  const startDay = first.getDay();
  const daysInMonth = new Date(year, month+1, 0).getDate();
  const daysInPrevMonth = new Date(year, month, 0).getDate();
  let html = '<table><thead><tr>';
  ['Su','Mo','Tu','We','Th','Fr','Sa'].forEach(d => html += `<th>${d}</th>`);
  html += '</tr></thead><tbody><tr>';
  for (let i=0;i<startDay;i++) html += `<td class="muted">${daysInPrevMonth-startDay+i+1}</td>`;
  let col = startDay;
  for (let d=1; d<=daysInMonth; d++) {
    if (col === 7) { html += '</tr><tr>'; col = 0; }
    const isToday = d === now.getDate();
    html += `<td class="${isToday?'today':''}">${d}</td>`;
    col++;
  }
  let next = 1;
  while (col < 7) { html += `<td class="muted">${next++}</td>`; col++; }
  html += '</tr></tbody></table>';
  el.innerHTML = html;
})();
</script>
</body>
</html>
