<?php
// Shared handler for the Contact, Get-a-Quote and Package enquiry forms.
require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/contact.php');
}

csrf_verify();

$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$country = trim($_POST['country'] ?? '');
$message = trim($_POST['message'] ?? '');
$preferredDestination = trim($_POST['preferred_destination'] ?? '');
if ($preferredDestination !== '') {
    $message .= "\n\nPreferred destination: " . $preferredDestination;
}
$source = trim($_POST['source'] ?? 'contact_form');
$treatmentId = !empty($_POST['treatment_id']) ? (int)$_POST['treatment_id'] : null;
$packageId = !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null;
$returnTo = $_POST['return_to'] ?? BASE_URL . '/contact.php';

$errors = [];
if ($fullName === '') $errors[] = 'Please enter your full name.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
if ($message === '') $errors[] = 'Please tell us a little about your enquiry.';

if ($errors) {
    flash_set('danger', implode(' ', $errors));
    redirect($returnTo);
}

$stmt = $pdo->prepare("
    INSERT INTO leads (full_name, email, phone, country, treatment_id, package_id, message, source, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
$stmt->execute([$fullName, $email, $phone, $country, $treatmentId, $packageId, $message, $source]);

redirect(BASE_URL . '/thank-you.php');
