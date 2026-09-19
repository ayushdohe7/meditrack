<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_any();
$role = current_role();
$pageTitle = $role === 'patient' ? "Book Appointment" : "Appointment Page";
$active = "appointment_page";

$error = ""; $success = "";

if ($role === 'patient') {
    $doctors = $conn->query("SELECT id, full_name, specialization FROM doctors WHERE status='active' ORDER BY full_name ASC");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $patId = $_SESSION['patient_id'];
        $docId = (int)($_POST['doctor_id'] ?? 0);
        $date  = $_POST['appointment_date'] ?? '';
        $time  = $_POST['appointment_time'] ?? '';
        $reason = trim($_POST['diagnosis'] ?? 'General Checkup');

        if (!$docId || !$date || !$time) {
            $error = "Please select doctor, date and time.";
        } else {
            $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, diagnosis, appointment_date, appointment_time, status) VALUES (?,?,?,?,?, 'Pending')");
            $stmt->bind_param("iisss", $patId, $docId, $reason, $date, $time);
            if ($stmt->execute()) {
                $success = "Appointment request sent! You'll be notified once the doctor approves it.";
            } else {
                $error = "Could not book appointment. Please try again.";
            }
        }
    }
} else {
    // Doctor manually creating/scheduling an appointment for an existing patient
    $docId = $_SESSION['doctor_id'];
    $patients = $conn->query("SELECT id, full_name, email FROM patients ORDER BY full_name ASC");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $patId = (int)($_POST['patient_id'] ?? 0);
        $date  = $_POST['appointment_date'] ?? '';
        $time  = $_POST['appointment_time'] ?? '';
        $reason = trim($_POST['diagnosis'] ?? 'General Checkup');

        if (!$patId || !$date || !$time) {
            $error = "Please select patient, date and time.";
        } else {
            $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, diagnosis, appointment_date, appointment_time, status) VALUES (?,?,?,?,?, 'Approved')");
            $stmt->bind_param("iisss", $patId, $docId, $reason, $date, $time);
            if ($stmt->execute()) {
                $success = "Appointment scheduled successfully.";
            } else {
                $error = "Could not schedule appointment. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo h($pageTitle); ?> - MediTrack</title>
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

    <div class="row justify-content-center">
      <div class="col-lg-7">
        <div class="card-panel p-4 form-panel">
          <h5 class="fw-bold mb-1"><?php echo $role==='patient' ? 'Book a New Appointment' : 'Schedule an Appointment'; ?></h5>
          <p class="text-muted-c mb-4"><?php echo $role==='patient' ? 'Pick a doctor and preferred time. We will confirm shortly.' : 'Create an appointment on behalf of a patient.'; ?></p>

          <?php if ($error): ?><div class="alert alert-dark-custom py-2 px-3 mb-3"><i class="fa-solid fa-circle-exclamation me-2 text-danger"></i><?php echo h($error); ?></div><?php endif; ?>
          <?php if ($success): ?><div class="alert alert-dark-custom py-2 px-3 mb-3"><i class="fa-solid fa-circle-check me-2 text-success"></i><?php echo h($success); ?></div><?php endif; ?>

          <form method="POST">
            <div class="mb-3">
              <?php if ($role === 'patient'): ?>
                <label class="form-label">Select Doctor</label>
                <select name="doctor_id" class="form-select" required>
                  <option value="">Choose a doctor...</option>
                  <?php while ($d = $doctors->fetch_assoc()): ?>
                    <option value="<?php echo $d['id']; ?>">Dr. <?php echo h($d['full_name']); ?> — <?php echo h($d['specialization']); ?></option>
                  <?php endwhile; ?>
                </select>
              <?php else: ?>
                <label class="form-label">Select Patient</label>
                <select name="patient_id" class="form-select" required>
                  <option value="">Choose a patient...</option>
                  <?php while ($p = $patients->fetch_assoc()): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo h($p['full_name']); ?> — <?php echo h($p['email']); ?></option>
                  <?php endwhile; ?>
                </select>
              <?php endif; ?>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Date</label>
                <input type="date" name="appointment_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Time</label>
                <input type="time" name="appointment_time" class="form-control" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Reason / Diagnosis</label>
              <input type="text" name="diagnosis" class="form-control" placeholder="e.g. Health Checkup, Fever, Follow-up">
            </div>
            <button type="submit" class="btn btn-brand w-100"><i class="fa-solid fa-calendar-plus me-1"></i> <?php echo $role==='patient' ? 'Send Request' : 'Schedule Appointment'; ?></button>
          </form>
        </div>
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
