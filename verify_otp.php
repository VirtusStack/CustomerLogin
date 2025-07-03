<?php 
session_start();

// connect to database
$conn = new mysqli("localhost", "root", "", "customer_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['otp']) || !isset($_SESSION['pending_user']))  {
    header("Location: login.php");
    exit;
}

$error = '';
$show_otp = isset($_GET['show']);
$otp = $_SESSION['otp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     $entered_otp = trim($_POST['otp']);

  if ($entered_otp == $otp) {

// Store trusted device
$stmt = $conn->prepare("INSERT INTO trusted_devices (user_id, device_hash) VALUES (?, ?)");
$stmt->bind_param("is", $_SESSION['pending_user'], $_SESSION['pending_device_hash']);
$stmt->execute();

$_SESSION['user_id'] = $_SESSION['pending_user'];
$_SESSION['name'] = $_SESSION['pending_name'];
$_SESSION['ip'] = $_SESSION['pending_ip'];
$_SESSION['device'] = $_SESSION['pending_device'];


date_default_timezone_set('Asia/Kolkata');
$_SESSION['login_time'] = date("Y-m-d H:i:s");

// Clear temporary session data
unset($_SESSION['otp'], $_SESSION['pending_user'], $_SESSION['pending_device_hash'],$_SESSION['pending_name'], $_SESSION['pending_ip'], $_SESSION['pending_device']);
header("Location: dashboard.php");
exit;

} else {
  $error = "Invalid OTP. Please try again.";
}
}
?>
<!DOCTYPE html>
<html>
<head>
 <title>Verify Device </title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class ="card p-4 shadow-sm mx-auto" style="max-width: 400px;">
 <h4 class="text-center mb-3">Device Verification </h4>

<?php if ($error): ?>
 <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method = "post">
<input type="text" class = "form-control mb-3" name="otp" placeholder = "Enter OTP" required>
<button type="submit" class="btn btn-primary w-100">Verify</button>
</form>

<?php if ($show_otp): ?>
<div class="alert alert-info mt-3 text-center">
 <strong> Demo OTP:</strong><?= htmlspecialchars($otp) ?>
</div>
<?php endif; ?>
</div>
</body>
</html>