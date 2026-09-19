<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_any();
$role = current_role();
$pageTitle = "Profile";
$active = "profile";
$error = ""; $success = "";

if ($role === 'doctor') {
    $id = $_SESSION['doctor_id'];
    $user = $conn->query("SELECT * FROM doctors WHERE id=$id")->fetch_assoc();
} else {
    $id = $_SESSION['patient_id'];
    $user = $conn->query("SELECT * FROM patients WHERE id=$id")->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $photoPath = $user['photo'];

    if (!empty($_FILES['photo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed)) {
            $folder = $role === 'doctor' ? 'uploads/doctors/' : 'uploads/patients/';
            $filename = $folder . $role . '_' . $id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $filename)) {
                $photoPath = $filename;
            }
        } else {
            $error = "Only JPG, PNG or WEBP images are allowed.";
        }
    }

    if (!$error) {
        if ($role === 'doctor') {
            $spec = trim($_POST['specialization'] ?? '');
            $qual = trim($_POST['qualification'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            $stmt = $conn->prepare("UPDATE doctors SET full_name=?, phone=?, specialization=?, qualification=?, bio=?, photo=? WHERE id=?");
            $stmt->bind_param("ssssssi", $name, $phone, $spec, $qual, $bio, $photoPath, $id);
        } else {
            $address = trim($_POST['address'] ?? '');
            $gender = $_POST['gender'] ?? 'Male';
            $stmt = $conn->prepare("UPDATE patients SET full_name=?, phone=?, address=?, gender=?, photo=? WHERE id=?");
            $stmt->bind_param("sssssi", $name, $phone, $address, $gender, $photoPath, $id);
        }
        if ($stmt->execute()) {
            $_SESSION[$role.'_name'] = $name;
            $_SESSION[$role.'_photo'] = $photoPath;
            $success = "Profile updated successfully.";
            $user = $role==='doctor' ? $conn->query("SELECT * FROM doctors WHERE id=$id")->fetch_assoc() : $conn->query("SELECT * FROM patients WHERE id=$id")->fetch_assoc();
        } else {
            $error = "Something went wrong updating your profile.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Profile - MediTrack</title>
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

    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card-panel p-4 form-panel">
          <form method="POST" enctype="multipart/form-data">
            <div class="d-flex align-items-center gap-3 mb-4">
              <?php if (!empty($user['photo']) && file_exists($user['photo'])): ?>
                <img src="/meditrack/<?php echo h($user['photo']); ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--brand);">
              <?php else: ?>
                <div class="avatar-fallback" style="width:80px;height:80px;font-size:28px;"><?php echo strtoupper(substr($user['full_name'],0,1)); ?></div>
              <?php endif; ?>
              <div>
                <label class="btn btn-sm" style="background:var(--bg-panel-2);border:1px solid var(--border);color:var(--text-main);">
                  <i class="fa-solid fa-camera me-1"></i> Change Photo
                  <input type="file" name="photo" accept="image/*" hidden>
                </label>
                <p class="text-muted-c mt-2 mb-0" style="font-size:12px;">JPG, PNG or WEBP. Max upload as per your PHP config.</p>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?php echo h($user['full_name']); ?>" required></div>
              <div class="col-md-6 mb-3"><label class="form-label">Email (read-only)</label><input type="email" class="form-control" value="<?php echo h($user['email']); ?>" disabled></div>
              <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?php echo h($user['phone']); ?>"></div>

              <?php if ($role === 'doctor'): ?>
                <div class="col-md-6 mb-3"><label class="form-label">Specialization</label><input type="text" name="specialization" class="form-control" value="<?php echo h($user['specialization']); ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" value="<?php echo h($user['qualification']); ?>"></div>
                <div class="col-12 mb-3"><label class="form-label">Bio</label><textarea name="bio" rows="3" class="form-control"><?php echo h($user['bio']); ?></textarea></div>
              <?php else: ?>
                <div class="col-md-6 mb-3"><label class="form-label">Gender</label>
                  <select name="gender" class="form-select">
                    <?php foreach (['Male','Female','Other'] as $g): ?>
                      <option <?php echo $user['gender']===$g?'selected':''; ?>><?php echo $g; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12 mb-3"><label class="form-label">Address</label><input type="text" name="address" class="form-control" value="<?php echo h($user['address']); ?>"></div>
              <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-1"></i> Save Changes</button>
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
