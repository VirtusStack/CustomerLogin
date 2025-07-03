<?php
session_start();

// connect to database
$conn = new mysqli("localhost", "root", "", "customer_db");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
 }

$user_id = $_SESSION['user_id'];
$error = ''; 
$success = '';

// Fetch current user info
$result = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();

if (isset($_POST['update'])) {
 $name = trim($_POST['name']);
 $newPhoto = $user['profile_photo'];

// Handle profile photo upload
if (!empty($_FILES['profile_photo']['name'])) {
   // delete old photo
   if (!empty($user['profile_photo']) && file_exists($user['profile_photo'])) {
       unlink($user['profile_photo']);
     }

    $targetDir = "uploads/";
    $timestamp = date("Ymd_His");
    $imageName = basename($_FILES["profile_photo"]["name"]);
    $uniqueName = uniqid("user_" . $user_id . "_" . $timestamp ."_");
    $targetFile = $targetDir . $uniqueName . $imageName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
  if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $targetFile)) {
      $newPhoto = $targetFile;
  } else {
     $error = "Error uploading file.";
 }
} else {
   $error = "Only JPG, PNG, and GIF files are allowed.";
 }
}

if (!$error) {
$stmt = $conn->prepare("UPDATE users SET name = ?, profile_photo = ?  WHERE id = ?");
$stmt->bind_param("ssi", $name, $newPhoto, $user_id);
if ($stmt->execute()) {
$_SESSION['name'] = $name;
$success = "Profile updated successfully!";
$user['name'] = $name;
$user['profile_photo'] = $newPhoto;
} else {
  $error = "Updated failed.";
}
}
}	
?>
<!DOCTYPE html>
<html lang="en">
<head> 
<meta charset="UTF-8">    
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100">
<div class="card p-4" style="max-width: 400px; width: 100%;">
<h3 class="text-center">Edit Profile</h3>
<?php if ($error): ?>
  <div class= "alert alert-danger"><?= $error ?></div>
<?php elseif ($success): ?>
  <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
<div class="mb-3">
<input type="text" name="name"  class="form-control mb-3" value="<?= htmlspecialchars($user['name']) ?>" required>
</div>
<div class="mb-3">
<label> Profile Photo </label><br>
<?php if ($user['profile_photo']): ?>
   <img src="<?= $user['profile_photo'] ?>" width="100"><br><br>
<?php endif; ?>
<input type="file" name="profile_photo" class="form-control mb-3">
</div>
<button type="submit" name="update" class="btn btn-primary "> Update </button>
<a href="dashboard.php" class="btn btn-link d-block mt-2 text-center"> Back to Dashboard </a>
</form>
</div>
</body>
</html>
