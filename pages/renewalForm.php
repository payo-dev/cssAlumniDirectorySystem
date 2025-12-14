<?php
// ==========================================================
// pages/renewalForm.php — Alumni Renewal Form (Fixed Logic)
// ==========================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';

// 1. Check Login Status
// If user is not logged in, send them to login page.
if (!Auth::isLoggedIn()) {
    header("Location: ../index.php");
    exit;
}

$pdo = Database::getPDO();
$user = Auth::getUser();

// 2. Fetch Alumni Data from Database for the Logged-In User
// This ensures the form is filled with their current live data
$stmt = $pdo->prepare("SELECT * FROM alumni WHERE user_id = ? LIMIT 1");
$stmt->execute([$user['id']]);
$alumni = $stmt->fetch(PDO::FETCH_ASSOC);

// Edge Case: User is logged in but has no alumni record yet
if (!$alumni) {
    // Determine the student ID (from User table or session if available)
    $studentId = $_SESSION['student_id'] ?? ''; 
} else {
    $studentId = $alumni['student_id'];
}

// 3. Handle 'No Data' Scenario
if (!$alumni && empty($studentId)) {
    echo "<div style='max-width:600px; margin:50px auto; padding:20px; border:1px solid #dc3545; color:#721c24; background:#f8d7da; border-radius:5px; text-align:center;'>";
    echo "<h3>Record Not Found</h3>";
    echo "<p>We could not find an alumni record linked to this account.</p>";
    echo "<a href='../index.php' style='display:inline-block; margin-top:10px; text-decoration:none; color:#721c24; font-weight:bold;'>&larr; Return to Dashboard</a>";
    echo "</div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Alumni Renewal - WMSU</title>
    <link rel="stylesheet" href="../assets/css/index.css">
</head>
<body style="background-color: #f4f6f9;">

<div style="background:#b30000; color:white; padding:10px 20px; display:flex; justify-content:space-between; align-items:center;">
    <span style="font-weight:bold;">WMSU Alumni Portal</span>
    <a href="auth/logout.php" style="color:white; text-decoration:none; font-size:0.9em;">Logout</a>
</div>

<div class="renewal-container">
  <h1>CCS Alumni Renewal Form</h1>
  <p class="instruction">Please review and update your latest information below.</p>

  <form method="POST" action="../functions/renewalSubmit.php" class="renewal-form">
    <input type="hidden" name="student_id" value="<?= htmlspecialchars($studentId) ?>">

    <fieldset>
      <legend>Personal Information</legend>

      <div class="two-col">
        <div class="form-group">
          <label for="surname">Last Name</label>
          <input type="text" id="surname" name="surname"
                 value="<?= htmlspecialchars($alumni['surname'] ?? '') ?>"
                 placeholder="Enter updated surname if changed">
        </div>
        <div class="form-group">
          <label for="given_name">First Name</label>
          <input type="text" id="given_name" name="given_name"
                 value="<?= htmlspecialchars($alumni['given_name'] ?? '') ?>"
                 readonly style="background-color:#e9ecef; cursor:not-allowed;">
        </div>
      </div>

      <div class="form-group">
        <label for="contact_number">Contact Number</label>
        <input type="tel" id="contact_number" name="contact_number"
               value="<?= htmlspecialchars($alumni['contact_number'] ?? '') ?>"
               placeholder="e.g. 09xxxxxxxxx">
      </div>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($user['email'] ?? ($alumni['email'] ?? '')) ?>"
               readonly style="background-color:#e9ecef; cursor:not-allowed;">
      </div>
    </fieldset>

    <fieldset>
      <legend>Address</legend>
      <div class="grid-2">
        <div class="form-group">
          <label>Region</label>
          <input type="text" name="region"
                 value="<?= htmlspecialchars($alumni['region'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Province</label>
          <input type="text" name="province"
                 value="<?= htmlspecialchars($alumni['province'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>City / Municipality</label>
          <input type="text" name="city_municipality"
                 value="<?= htmlspecialchars($alumni['city'] ?? '') ?>"> </div>
        <div class="form-group">
          <label>Barangay</label>
          <input type="text" name="barangay"
                 value="<?= htmlspecialchars($alumni['barangay'] ?? '') ?>">
        </div>
      </div>
    </fieldset>

    <fieldset>
      <legend>Employment Information</legend>
      <div class="two-col">
        <div class="form-group">
          <label for="company_name">Company Name</label>
          <input type="text" id="company_name" name="company_name"
                 value="<?= htmlspecialchars($alumni['company_name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="position">Position / Job Title</label>
          <input type="text" id="position" name="position"
                 value="<?= htmlspecialchars($alumni['position'] ?? '') ?>">
        </div>
      </div>

      <div class="two-col">
        <div class="form-group">
          <label for="company_address">Company Address</label>
          <input type="text" id="company_address" name="company_address"
                 value="<?= htmlspecialchars($alumni['company_address'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="company_contact">Company Contact</label>
          <input type="text" id="company_contact" name="company_contact"
                 value="<?= htmlspecialchars($alumni['company_contact'] ?? '') ?>">
        </div>
      </div>
    </fieldset>

    <fieldset>
      <legend>Additional Educational Background</legend>
      <p class="sub-instruction">If you have earned a new tertiary degree, you may add it below.</p>

      <div class="two-col">
        <div class="form-group">
          <label for="new_tertiary_school">New College / University</label>
          <input type="text" id="new_tertiary_school" name="new_tertiary_school"
                 placeholder="e.g. Western Mindanao State University">
        </div>
        <div class="form-group">
          <label for="new_tertiary_yr">Year Graduated</label>
          <input type="number" id="new_tertiary_yr" name="new_tertiary_yr"
                 min="1900" max="<?= date('Y') + 1 ?>">
        </div>
      </div>
    </fieldset>

    <fieldset>
      <legend>Emergency Contact</legend>
      <div class="two-col">
        <div class="form-group">
          <label for="emergency_name">Full Name</label>
          <input type="text" id="emergency_name" name="emergency_name"
                 value="<?= htmlspecialchars($alumni['emergency_name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="emergency_contact">Contact Number</label>
          <input type="text" id="emergency_contact" name="emergency_contact"
                 value="<?= htmlspecialchars($alumni['emergency_contact'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label for="emergency_address">Address</label>
        <input type="text" id="emergency_address" name="emergency_address"
               value="<?= htmlspecialchars($alumni['emergency_address'] ?? '') ?>">
      </div>
    </fieldset>

    <div class="btn-group">
      <button type="submit" class="submit-btn">Submit Renewal</button>
      </div>
  </form>
</div>

<style>
.renewal-container {
  max-width: 850px;
  margin: 40px auto;
  background: #f8fff8;
  padding: 35px;
  border-radius: 10px;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
  font-family: Arial, sans-serif;
  border-top: 5px solid #198754;
}
h1 {
  color: #198754;
  text-align: center;
  margin-bottom: 10px;
}
.instruction {
  text-align: center;
  color: #555;
  margin-bottom: 25px;
}
.sub-instruction {
  color: #777;
  margin-bottom: 10px;
  font-size: 0.9em;
}
fieldset {
  border: 1px solid #c3e6cb;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 25px;
  background-color: white;
}
legend {
  color: #157347;
  font-weight: bold;
  padding: 0 10px;
  text-transform: uppercase;
  font-size: 0.85em;
  letter-spacing: 0.5px;
}
.form-group {
  margin-bottom: 15px;
}
label {
  display: block;
  font-weight: bold;
  color: #333;
  margin-bottom: 5px;
  font-size: 0.95em;
}
input[type="text"],
input[type="email"],
input[type="tel"],
input[type="number"] {
  width: 100%;
  padding: 10px;
  border: 1px solid #ced4da;
  border-radius: 5px;
  font-size: 1rem;
}
input:focus {
    border-color: #198754;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}
.two-col {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}
.two-col .form-group {
  flex: 1;
  min-width: 250px;
}
.grid-2 {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 15px;
}
.btn-group {
  text-align: center;
  margin-top: 25px;
}
.submit-btn {
  background: #198754;
  color: white;
  border: none;
  padding: 12px 30px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1.1em;
  font-weight: bold;
  transition: background 0.2s;
}
.submit-btn:hover {
  background: #146c43;
}
.back-btn {
  display: inline-block;
  margin-left: 15px;
  color: #6c757d;
  text-decoration: none;
}
.back-btn:hover {
  text-decoration: underline;
  color: #495057;
}
</style>

</body>
</html>