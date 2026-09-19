<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_any();
$role = current_role();
$pageTitle = "Patient Details";
$active = "patient_details";
$success = ""; $error = "";

if ($role === 'doctor') {
    $docId = $_SESSION['doctor_id'];

    // Save edits (doctor updating a patient's medical record)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pid = (int)$_POST['patient_id'];
        $bg = trim($_POST['blood_group'] ?? '');
        $ht = $_POST['height_cm'] !== '' ? (float)$_POST['height_cm'] : null;
        $wt = $_POST['weight_kg'] !== '' ? (float)$_POST['weight_kg'] : null;
        $allergies = trim($_POST['allergies'] ?? '');
        $history = trim($_POST['medical_history'] ?? '');

        $stmt = $conn->prepare("UPDATE patients SET blood_group=?, height_cm=?, weight_kg=?, allergies=?, medical_history=? WHERE id=?");
        $stmt->bind_param("sddssi", $bg, $ht, $wt, $allergies, $history, $pid);
        $stmt->execute();
        $success = "Patient record updated successfully.";
    }

    $search = trim($_GET['q'] ?? '');
    $searchSql = $search !== '' ? " AND p.full_name LIKE '%" . $conn->real_escape_string($search) . "%' " : "";

    $patients = $conn->query("SELECT DISTINCT p.* FROM patients p JOIN appointments a ON a.patient_id = p.id
                               WHERE a.doctor_id=$docId $searchSql ORDER BY p.full_name ASC");
} else {
    $patId = $_SESSION['patient_id'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $bg = trim($_POST['blood_group'] ?? '');
        $ht = $_POST['height_cm'] !== '' ? (float)$_POST['height_cm'] : null;
        $wt = $_POST['weight_kg'] !== '' ? (float)$_POST['weight_kg'] : null;
        $allergies = trim($_POST['allergies'] ?? '');
        $history = trim($_POST['medical_history'] ?? '');
        $address = trim($_POST['address'] ?? '');

        $stmt = $conn->prepare("UPDATE patients SET blood_group=?, height_cm=?, weight_kg=?, allergies=?, medical_history=?, address=? WHERE id=?");
        $stmt->bind_param("sddsssi", $bg, $ht, $wt, $allergies, $history, $address, $patId);
        $stmt->execute();
        $success = "Your medical details have been saved.";
    }
    $me = $conn->query("SELECT * FROM patients WHERE id=$patId")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patient Details - MediTrack</title>
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

    <?php if ($role === 'doctor'): ?>
      <div class="card-panel p-3 mb-3">
        <form method="GET" class="d-flex gap-2">
          <input type="text" name="q" class="form-control" placeholder="Search patient by name..." value="<?php echo h($search); ?>">
          <button class="btn btn-brand"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
      </div>

      <div class="row g-3">
        <?php if ($patients && $patients->num_rows > 0): while ($p = $patients->fetch_assoc()): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card-panel p-3 h-100">
              <div class="d-flex align-items-center gap-2 mb-2">
                <div class="avatar-fallback" style="width:46px;height:46px;"><?php echo strtoupper(substr($p['full_name'],0,1)); ?></div>
                <div>
                  <div class="fw-bold"><?php echo h($p['full_name']); ?></div>
                  <div class="text-muted-c" style="font-size:12px;"><?php echo h($p['email']); ?></div>
                </div>
              </div>
              <div class="row mb-2" style="font-size:12.5px;">
                <div class="col-6"><span class="text-muted-c d-block">Blood Group</span><?php echo h($p['blood_group'] ?: '-'); ?></div>
                <div class="col-6"><span class="text-muted-c d-block">Gender</span><?php echo h($p['gender'] ?: '-'); ?></div>
                <div class="col-6 mt-1"><span class="text-muted-c d-block">Height</span><?php echo $p['height_cm'] ? $p['height_cm'].' cm' : '-'; ?></div>
                <div class="col-6 mt-1"><span class="text-muted-c d-block">Weight</span><?php echo $p['weight_kg'] ? $p['weight_kg'].' kg' : '-'; ?></div>
              </div>
              <button class="btn btn-sm w-100" style="background:var(--bg-panel-2);border:1px solid var(--border);color:var(--text-main);" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $p['id']; ?>">
                <i class="fa-solid fa-pen-to-square me-1"></i> View / Edit Record
              </button>
            </div>
          </div>

          <!-- Modal -->
          <div class="modal fade" id="editModal<?php echo $p['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content" style="background:var(--bg-panel);color:var(--text-main);border:1px solid var(--border);">
                <form method="POST">
                  <input type="hidden" name="patient_id" value="<?php echo $p['id']; ?>">
                  <div class="modal-header" style="border-color:var(--border);">
                    <h6 class="modal-title fw-bold"><?php echo h($p['full_name']); ?> — Medical Record</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="row">
                      <div class="col-6 mb-3"><label class="form-label">Blood Group</label><input type="text" name="blood_group" class="form-control" value="<?php echo h($p['blood_group']); ?>"></div>
                      <div class="col-6 mb-3"><label class="form-label">Height (cm)</label><input type="number" step="0.1" name="height_cm" class="form-control" value="<?php echo h($p['height_cm']); ?>"></div>
                      <div class="col-6 mb-3"><label class="form-label">Weight (kg)</label><input type="number" step="0.1" name="weight_kg" class="form-control" value="<?php echo h($p['weight_kg']); ?>"></div>
                      <div class="col-6 mb-3"><label class="form-label">Allergies</label><input type="text" name="allergies" class="form-control" value="<?php echo h($p['allergies']); ?>"></div>
                      <div class="col-12 mb-2"><label class="form-label">Medical History / Notes</label><textarea name="medical_history" rows="3" class="form-control"><?php echo h($p['medical_history']); ?></textarea></div>
                    </div>
                  </div>
                  <div class="modal-footer" style="border-color:var(--border);">
                    <button type="submit" class="btn btn-brand w-100">Save Record</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php endwhile; else: ?>
          <div class="col-12"><div class="card-panel p-5 text-center text-muted-c">No patients found.</div></div>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <div class="row justify-content-center">
        <div class="col-lg-7">
          <div class="card-panel p-4 form-panel">
            <h5 class="fw-bold mb-3">My Medical Details</h5>
            <form method="POST">
              <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Blood Group</label><input type="text" name="blood_group" class="form-control" value="<?php echo h($me['blood_group']); ?>" placeholder="O+"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Address</label><input type="text" name="address" class="form-control" value="<?php echo h($me['address']); ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Height (cm)</label><input type="number" step="0.1" name="height_cm" class="form-control" value="<?php echo h($me['height_cm']); ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Weight (kg)</label><input type="number" step="0.1" name="weight_kg" class="form-control" value="<?php echo h($me['weight_kg']); ?>"></div>
                <div class="col-12 mb-3"><label class="form-label">Allergies</label><input type="text" name="allergies" class="form-control" value="<?php echo h($me['allergies']); ?>" placeholder="e.g. Penicillin, Dust"></div>
                <div class="col-12 mb-3"><label class="form-label">Medical History</label><textarea name="medical_history" rows="4" class="form-control" placeholder="Past conditions, surgeries, medications..."><?php echo h($me['medical_history']); ?></textarea></div>
              </div>
              <button type="submit" class="btn btn-brand w-100"><i class="fa-solid fa-floppy-disk me-1"></i> Save Details</button>
            </form>
          </div>
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
