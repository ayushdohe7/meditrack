<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_any();
$role = current_role();
$pageTitle = "Settings";
$active = "settings";
$error = ""; $success = "";

if ($role === 'doctor') {
    $id = $_SESSION['doctor_id'];
    $settings = $conn->query("SELECT * FROM settings WHERE doctor_id=$id")->fetch_assoc();
    if (!$settings) {
        $conn->query("INSERT INTO settings (doctor_id) VALUES ($id)");
        $settings = $conn->query("SELECT * FROM settings WHERE doctor_id=$id")->fetch_assoc();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['form']) && $_POST['form'] === 'prefs' && $role === 'doctor') {
        $notifyEmail = isset($_POST['notify_email']) ? 1 : 0;
        $notifySms = isset($_POST['notify_sms']) ? 1 : 0;
        $fee = (float)($_POST['consultation_fee'] ?? 0);
        $stmt = $conn->prepare("UPDATE settings SET notify_email=?, notify_sms=?, consultation_fee=? WHERE doctor_id=?");
        $stmt->bind_param("iidi", $notifyEmail, $notifySms, $fee, $id);
        $stmt->execute();
        $success = "Preferences saved.";
        $settings = $conn->query("SELECT * FROM settings WHERE doctor_id=$id")->fetch_assoc();
    }

    if (isset($_POST['form']) && $_POST['form'] === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $table = $role === 'doctor' ? 'doctors' : 'patients';
        $uid = $role === 'doctor' ? $_SESSION['doctor_id'] : $_SESSION['patient_id'];

        $row = $conn->query("SELECT password FROM $table WHERE id=$uid")->fetch_assoc();
        if (!password_verify($current, $row['password'])) {
            $error = "Current password is incorrect.";
        } elseif (strlen($new) < 6) {
            $error = "New password must be at least 6 characters.";
        } elseif ($new !== $confirm) {
            $error = "New passwords do not match.";
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE $table SET password=? WHERE id=?");
            $stmt->bind_param("si", $hash, $uid);
            $stmt->execute();
            $success = "Password changed successfully.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Settings - MediTrack</title>
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

    <div class="row g-3 justify-content-center">
      <?php if ($role === 'doctor'): ?>
      <div class="col-lg-6">
        <div class="card-panel p-4 form-panel">
          <h6 class="fw-bold mb-3"><i class="fa-solid fa-sliders me-2"></i>Preferences</h6>
          <form method="POST">
            <input type="hidden" name="form" value="prefs">
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" name="notify_email" id="ne" <?php echo $settings['notify_email']?'checked':''; ?>>
              <label class="form-check-label" for="ne">Email notifications for new appointments</label>
            </div>
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" name="notify_sms" id="ns" <?php echo $settings['notify_sms']?'checked':''; ?>>
              <label class="form-check-label" for="ns">SMS notifications</label>
            </div>
            <div class="mb-3">
              <label class="form-label">Default Consultation Fee (₹)</label>
              <input type="number" step="0.01" name="consultation_fee" class="form-control" value="<?php echo h($settings['consultation_fee']); ?>">
            </div>
            <button class="btn btn-brand w-100">Save Preferences</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <div class="col-lg-6">
        <div class="card-panel p-4 form-panel">
          <h6 class="fw-bold mb-3"><i class="fa-solid fa-lock me-2"></i>Change Password</h6>
          <form method="POST">
            <input type="hidden" name="form" value="password">
            <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required></div>
            <button class="btn btn-brand w-100">Update Password</button>
          </form>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card-panel p-4">
          <h6 class="fw-bold mb-3"><i class="fa-solid fa-circle-info me-2"></i>About</h6>
          <p class="text-muted-c mb-1" style="font-size:13px;">MediTrack Dashboard v1.0</p>
          <p class="text-muted-c mb-0" style="font-size:13px;">Theme: Dark (default). Built with HTML5, CSS3, Bootstrap 5, JavaScript and PHP + MySQL.</p>
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
