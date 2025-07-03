<?php
session_start();

// connect to database
$conn = new mysqli("localhost", "root", "", "customer_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// handle registration
$error = '';
if (isset($_POST['signup']))  {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $profilePhotoPath = '';

// Upload file if provided
if (!empty($_FILES['profile_photo']['name'])) {
    $uploadDir = "uploads/";
    $timestamp = date("Ymd_His");
    $filename = $timestamp . "_" . uniqid() . "_" . basename($_FILES["profile_photo"]["name"]);
    $targetFile = $uploadDir . $filename;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
  if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $targetFile)) {
      $profilePhotoPath = $targetFile;
  } else {
     $error = "Failed to upload profile photo.";
 }
} else {
   $error = "Only JPG, PNG, and GIF files are allowed.";
 }
}

if (empty($error)) {
// check if email already exists
$check = $conn->prepare("SELECT * FROM users WHERE email= ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $error = "Email already registered!";
 } else {
   $stmt = $conn->prepare("INSERT INTO users (name, email, password, profile_photo) VALUES (?, ?, ?, ?)");
   $stmt->bind_param("ssss", $name, $email, $password, $profilePhotoPath);
   
if ($stmt->execute()) {
 $_SESSION['user'] = $name;
   header("Location: login.php");
   exit;
        } else {
      $error = "Registration failed. Try again.";
        } 
      }     
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head> 
<meta charset="UTF-8">    
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Auth Page</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
html, body {
height: 100%;
margin: 0;
}
body {
display: flex;
justify-content: center;
align-items: center;
}
.card {
width: 100%;
max-width: 400px;
min-height: 450px;
padding: 30px;
background: #fff;
border-radius: 15px;
box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
}
</style>
</head>
<body>
<div class= "card">
<h3 class="text-center mb-4">Sign Up</h3>
<?php if (!empty($error)) echo"<div class='alert alert-danger'>$error</div>"; ?>

<form method ="post" enctype="multipart/form-data">
<input type="text" name="name" class="form-control mb-3" placeholder="Name" required> 

<input type="email" name="email" class="form-control mb-3" placeholder="Email" required> 
<div class="input-group mb-3">
<input type="password" name="password" id="password" class="form-control mb-3" placeholder="Password" required>
<button class="btn btn-outline-secondary" type="button" id="togglePassword" style="height: 100%;"><i class="bi bi-eye"></i></button>
</div>

<label> Profile Photo </label>
<input type="file" name="profile_photo" class="form-control mb-3" accept="image/*">
<button type="submit" name="signup" class="btn btn-primary w-100 mt-4">Sign Up</button>
<div class="text-center mt-4">
<a href="login.php" class="btn btn-link"> Already have an account? Login</a>
</div>
</form>
</div>
<script>
const togglePassword = document.querySelector('#togglePassword');
const password = document.querySelector('#password');

togglePassword.addEventListener('click', function ()  {
  // Toggle the type attribute
  const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
  password.setAttribute('type', type);
 // Toggle icon class
 this.querySelector('i').classList.toggle('bi-eye');
 this.querySelector('i').classList.toggle('bi-eye-slash');
 });
</script>
</body> 
</html>


