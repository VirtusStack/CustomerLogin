<?php
session_start();

// Connect to database
$conn = new mysqli("localhost", "root", "", "customer_db");

// Utility functions
function getDevice() {
    return $_SERVER['HTTP_USER_AGENT'];
}

function getIP() {
    $ip = $_SERVER['REMOTE_ADDR'];
    return ($ip === '::1' || $ip === '127.0.0.1') ? 'localhost' : $ip;
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $ip = getIP();
    $device = getDevice();
    $device_hash = hash('sha256', $device);

    // Secure user fetch
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $success = 0;

    if ($user && password_verify($password, $user['password'])) {
        // Check if device is trusted
        $stmt = $conn->prepare("SELECT * FROM trusted_devices WHERE user_id = ? AND device_hash = ?");
        $stmt->bind_param("is", $user['id'], $device_hash);
        $stmt->execute();
        $trusted = $stmt->get_result()->fetch_assoc();

        if (!$trusted) {
            // Device not trusted → generate OTP
            $otp = rand(100000, 999999);

            // Store info in session temporarily
            $_SESSION['otp'] = $otp;
            $_SESSION['pending_user'] = $user['id'];
            $_SESSION['pending_name'] = $user['name'];
            $_SESSION['pending_ip'] = $ip;
            $_SESSION['pending_device'] = $device;
            $_SESSION['pending_device_hash'] = $device_hash;

            // Optional: log failed (pending) attempt
            $conn->query("INSERT INTO login_logs (user_id, ip_address, device, login_time, success)
                          VALUES ({$user['id']}, '$ip', '$device', NOW(), 0)");

            // Redirect to verify page
            header("Location: verify_otp.php?show=1"); // show=1 shows OTP (for demo)
            exit;
   }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['ip'] = $ip;
        $_SESSION['device'] = $device;
        $_SESSION['login_time'] = date("Y-m-d H:i:s");

       
        // Remember me cookie
        if (isset($_POST['remember'])) {
            setcookie("remember_email", $email, time() + 3600, "/", "", true, true);
        } else {
            setcookie("remember_email", "", time() - 3600, "/", "", true, true);
        }

        // Update user record and login log
        $conn->query("UPDATE users SET last_ip='$ip', last_device='$device' WHERE id={$user['id']}");
        $conn->query("INSERT INTO login_logs (user_id, ip_address, device, login_time, success)
                      VALUES ({$user['id']}, '$ip', '$device', NOW(), 1)");

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password.";
        if ($user) {
            $conn->query("INSERT INTO login_logs (user_id, ip_address, device, login_time, success)
                          VALUES ({$user['id']}, '$ip', '$device', NOW(), 0)");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head> 
<meta charset="UTF-8">    
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>login</title>
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
min-height: 400px;
padding: 30px;
background: #fff;
border-radius: 15px;
box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
}
</style>

</head>
<body>
<div class= "card">
<h3 class="text-center mb-4">Login</h3>

<form method ="post">
<input type="email" name="email" class="form-control mb-3" placeholder="Email" value="<?= htmlspecialchars($_COOKIE['remember_email'] ?? '') ?>" required> 

<div class="input-group mb-3">
<input type="password" name="password" id="password" class="form-control mb-3" placeholder="Password" required>
<button class="btn btn-outline-secondary" type="button" id="togglePassword" style="height: 100%;"><i class="bi bi-eye"></i></button>
</div>
<div class="form-check mb-3">
<label>
<input type="checkbox" name="remember">Remember Me </label>
</div>
<button type="submit" name="login" class="btn btn-primary w-100 mt-4">Login</button>
<div class="text-center mt-3">
<a href="auth.php" class="btn btn-link"> Don't have an account? signup</a>
</div>
</form>
<?php if (!empty($error)) echo"<div class='alert alert-danger'>$error</div>"; ?>
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
 



