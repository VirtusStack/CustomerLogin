<?php
session_start();
// Connect to database
$conn = new mysqli("localhost", "root", "", "customer_db");

// Utility function
function getIP() {
    $ip = $_SERVER['REMOTE_ADDR'];
    return ($ip === '::1' || $ip === '127.0.0.1') ? 'localhost' : $ip;
}


//  Redirect to login if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['ip'] !== getIP()) {
    header("Location: login.php?session_expired=1");
    exit;
}
// Fetch user info from database
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();

// Geolocation function
function getGeoInfo($ip) {
 $url = "http://ip-api.com/json/{$ip}";
 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL, $url);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
 $response = curl_exec($ch);
 curl_close($ch);
 return json_decode($response, true);
}

// Weather function
function getWeather($lat, $lon, $apiKey) {
 $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL, $url);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 $weatherData = curl_exec($ch);
 curl_close($ch);
 return json_decode($weatherData, true );
}

$ip = $_SESSION['ip'] ?? getIP();
if ($ip === '::1' || $ip === '127.0.0.1' || $ip === 'localhost')  {
   $city = 'Mumbai'; 
   $country = 'India';
   $lat = 19.0760;
   $lon = 72.8777;
}else
 {
// Get user IP from session
$geo = getGeoInfo($ip);
$city = $geo['city'] ?? 'Unknown';
$country = $geo['country'] ?? 'Unknown';
$lat = $geo['lat'] ?? 0;
$lon = $geo['lon'] ?? 0;
}

// Whether info
$apiKey = "f791eb9c740444247a82eaa8f76b5fc2";
$weather = getWeather($lat, $lon, $apiKey);
$temperature = $weather['main']['temp'] ?? 'N/A';
$description = $weather['weather'][0]['description'] ?? 'N/A';

date_default_timezone_set('Asia/Kolkata');
$hour = (int)date("H");
if ($hour < 12 ) {
    $greeting = "Good morning! Have you eaten lunch yet?";
    $alertClass = "alert-warning";
} elseif ($hour < 17) {
    $greeting = "Good afternoon! Hope you're having a great day.";
    $alertClass = "alert-success";
} else {
    $greeting = "Good evening! Time to relax and unwind.";
    $alertClass = "alert-primary";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title> 
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
html, body {
   height: 100%;
   margin: 0;
}
body {
min-height: 100vh;
background-color: #f8f9fa;
display: flex;
padding-top: 30px;
justify-content: center;
align-items: flex-start;
}
.card {
width: 100%;
max-width: 450px;
padding: 30px;
background: #fff;
border-radius: 15px;
box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
}
.greeting-hover {
 transition: all 0.3s ease;
}
.greeting-hover:hover {
 background-color: #e8f4fd !important;
 transform: scale(1.02); /*slight zoom */
 box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}
</style>
</head>
<body >
<div class="card">
<div class="alert <?= $alertClass ?> text-center fw-semibold greeting-hover">
<?= $greeting ?>, <?= htmlspecialchars($_SESSION['name']) ?>!
</div>

<?php if (!empty($user['profile_photo'])): ?>
   <div class="text-center mb-3">
  <img src="<?= htmlspecialchars($user['profile_photo']) ?>" class="rounded-circle" width="100" height="100" alt="Profile Photo">
</div>
<?php endif; ?>

<p><strong>Account info</strong> </p>
<p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
<p><strong>IP:</strong> <?= htmlspecialchars($_SESSION['ip']) ?></p>
<p><strong>Device:</strong> <?= htmlspecialchars($_SESSION['device']) ?></p>
<p><strong>Login Time:</strong> <?= htmlspecialchars($_SESSION['login_time']) ?></p>
<hr>
<p><strong>Location:</strong> <?= htmlspecialchars($city) ?>, <?= htmlspecialchars($country) ?> </p>
<p><strong>Weather:</strong> <?= htmlspecialchars(ucwords($description)) ?>, <?= htmlspecialchars($temperature) ?>°C </p>

<?php if (isset($_COOKIE['remember_email'])): ?>
    <p><strong>Last login email (remembered):</strong> <?= htmlspecialchars($_COOKIE['remember_email']) ?></p>
<?php endif; ?>
<a href="edit_profile.php" class="btn btn-outline-primary mt-2">Edit Profile</a>
<a href="logout.php" class="btn btn-outline-danger mt-3">Logout</a>
</div>
</body>
</html>




