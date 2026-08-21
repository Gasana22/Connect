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
$expectedTravelDate = trim($_POST['expected_travel_date'] ?? '');
$flightBooked = trim($_POST['flight_booked'] ?? '');
$returnTo = $_POST['return_to'] ?? BASE_URL . '/contact.php';

$errors = [];
if ($fullName === '') $errors[] = 'Please enter your full name.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
if ($message === '') $errors[] = 'Please tell us a little about your enquiry.';

if ($source === 'quote_form') {
    if ($phone === '') $errors[] = 'Please enter your phone / WhatsApp number.';
    if ($country === '') $errors[] = 'Please enter your country of residence.';
    if (!$treatmentId) $errors[] = 'Please select a treatment.';
    if ($preferredDestination === '') $errors[] = 'Please select a preferred destination.';
    if ($expectedTravelDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expectedTravelDate)) $errors[] = 'Please select your expected travel date.';
    if (!in_array($flightBooked, ['yes', 'no'], true)) $errors[] = 'Please tell us whether you have booked a flight.';
}

if ($errors) {
    flash_set('danger', implode(' ', $errors));
    redirect($returnTo);
}

$stmt = $pdo->prepare("
    INSERT INTO leads (full_name, email, phone, country, treatment_id, package_id, message, expected_travel_date, flight_booked, source, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
$stmt->execute([
    $fullName, $email, $phone, $country, $treatmentId, $packageId, $message,
    $expectedTravelDate !== '' ? $expectedTravelDate : null,
    in_array($flightBooked, ['yes', 'no'], true) ? $flightBooked : null,
    $source,
]);

redirect(BASE_URL . '/thank-you.php');
