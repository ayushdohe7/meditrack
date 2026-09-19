<?php
require_once 'config/db.php';
if (!empty($_SESSION['doctor_id']) || !empty($_SESSION['patient_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MediTrack - Welcome</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/[email protected]/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card" style="max-width:820px;">
    <div class="auth-side">
      <div class="logo-dot mb-4" style="width:50px;height:50px;font-size:20px;">MT</div>
      <h2>MediTrack</h2>
      <p class="opacity-75">A complete, dynamic hospital &amp; clinic management dashboard — appointments, patients, payments, and an AI assistant, all in one dark themed workspace.</p>
      <ul class="list-unstyled mt-4 opacity-75">
        <li class="mb-2"><i class="fa-solid fa-circle-check me-2"></i>Doctor &amp; Patient portals</li>
        <li class="mb-2"><i class="fa-solid fa-circle-check me-2"></i>Live appointment tracking</li>
        <li class="mb-2"><i class="fa-solid fa-circle-check me-2"></i>Built-in AI chatbot</li>
      </ul>
    </div>
    <div class="auth-form d-flex flex-column justify-content-center">
      <h4 class="fw-bold mb-1">Welcome 👋</h4>
      <p class="text-muted-c mb-4">Choose how you'd like to continue</p>

      <a href="auth/doctor_login.php" class="btn btn-brand w-100 mb-3 py-3 text-start px-4">
        <i class="fa-solid fa-user-doctor me-2"></i> Continue as Doctor
      </a>
      <a href="auth/patient_login.php" class="btn w-100 mb-3 py-3 text-start px-4" style="background:var(--bg-panel-2);color:var(--text-main);border:1px solid var(--border);border-radius:10px;">
        <i class="fa-solid fa-user me-2"></i> Continue as Patient
      </a>

      <hr style="border-color:var(--border);">
      <p class="text-center text-muted-c mb-0" style="font-size:13px;">
        New here? <a href="auth/doctor_register.php" class="text-decoration-underline" style="color:var(--brand-2);">Register as Doctor</a>
        &nbsp;|&nbsp;
        <a href="auth/patient_register.php" class="text-decoration-underline" style="color:var(--brand-2);">Register as Patient</a>
      </p>
    </div>
  </div>
</div>
</body>
</html>
