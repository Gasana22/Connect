<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/SimplePdf.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT p.*, t.name AS treatment_name, h.name AS hospital_name, d.name AS destination_name
    FROM packages p
    JOIN treatments t ON t.id = p.treatment_id
    JOIN hospitals h ON h.id = p.hospital_id
    JOIN destinations d ON d.id = h.destination_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$package = $stmt->fetch();
if (!$package) {
    flash_set('danger', 'Package not found.');
    redirect(BASE_URL . '/marinka/packages.php');
}

$itStmt = $pdo->prepare("SELECT * FROM package_itinerary WHERE package_id = ? ORDER BY day_number ASC, id ASC");
$itStmt->execute([$id]);
$itinerary = $itStmt->fetchAll();

$includesList = array_filter(array_map('trim', explode("\n", (string)$package['includes'])));
$excludesList = array_filter(array_map('trim', explode("\n", (string)$package['excludes'])));

$siteName = setting($pdo, 'site_name', "Let's Go Medical");
$sitePhone = setting($pdo, 'site_phone');
$siteEmail = setting($pdo, 'site_email');
$siteAddress = setting($pdo, 'site_address');

if ($package['valid_from'] && $package['valid_until']) {
    $validText = date('M j, Y', strtotime($package['valid_from'])) . ' - ' . date('M j, Y', strtotime($package['valid_until']));
} elseif ($package['valid_from']) {
    $validText = 'From ' . date('M j, Y', strtotime($package['valid_from']));
} elseif ($package['valid_until']) {
    $validText = 'Until ' . date('M j, Y', strtotime($package['valid_until']));
} else {
    $validText = 'Year-round';
}

$pdf = new SimplePdf();
$contentWidth = 595.28 - $pdf->marginLeft - $pdf->marginRight;

$pdf->setPageDecorator(function ($pdf, $pageNum) use ($siteName, $sitePhone, $siteEmail, $siteAddress) {
    $pageW = 595.28;
    $pageH = 841.89;

    $pdf->setFillColor(75, 46, 131);
    $pdf->rectTopDown(0, 0, $pageW, 80, 'F');
    $pdf->setTextColor(255, 255, 255);
    $pdf->setFont('B', 18);
    $pdf->text(50, 18, $siteName);
    $pdf->setFont('', 9);
    $contact = trim($sitePhone . ($sitePhone && $siteEmail ? '   |   ' : '') . $siteEmail);
    $pdf->text(50, 45, $contact);

    $pdf->setFillColor(75, 46, 131);
    $pdf->rectTopDown(0, $pageH - 40, $pageW, 40, 'F');
    $pdf->setTextColor(255, 255, 255);
    $pdf->setFont('', 8);
    $pdf->text(50, $pageH - 27, (string)$siteAddress);
    $pdf->text($pageW - 100, $pageH - 27, 'Page ' . $pageNum);

    $pdf->setTextColor(25, 22, 40);
    $pdf->setY(105);
});

$pdf->setFont('', 9);
$pdf->setTextColor(120, 120, 130);
$pdf->text($pdf->marginLeft, $pdf->getY(), 'Quote prepared on ' . date('F j, Y'));
$pdf->setY($pdf->getY() + 22);

$pdf->setTextColor(25, 22, 40);
$pdf->setFont('B', 17);
$pdf->text($pdf->marginLeft, $pdf->getY(), $package['title']);
$pdf->setY($pdf->getY() + 26);

$facts = [];
if ($package['location']) $facts[] = 'Location: ' . $package['location'];
if ($package['days']) $facts[] = 'Duration: ' . (int)$package['days'] . ' Days';
$facts[] = 'Hospital: ' . $package['hospital_name'];
$facts[] = 'Valid: ' . $validText;
$pdf->setFont('', 10);
$pdf->setTextColor(90, 90, 100);
$pdf->writeParagraph(implode('   |   ', $facts), $contentWidth, 15);
$pdf->setY($pdf->getY() + 10);

$pdf->setFont('B', 13);
$pdf->setTextColor(75, 46, 131);
$priceLine = ($package['show_price'] && $package['price']) ? 'Package Price: ' . format_price($package['price']) : 'Package Price: Contact us for pricing';
$pdf->text($pdf->marginLeft, $pdf->getY(), $priceLine);
$pdf->setY($pdf->getY() + 26);

$pdf->setTextColor(25, 22, 40);
$pdf->setFont('B', 13);
$pdf->text($pdf->marginLeft, $pdf->getY(), 'Overview');
$pdf->setY($pdf->getY() + 20);
$pdf->setFont('', 10.5);
$pdf->writeParagraph($package['description'], $contentWidth, 15);
$pdf->setY($pdf->getY() + 14);

if ($itinerary) {
    $pdf->checkPageBreak(30);
    $pdf->setFont('B', 13);
    $pdf->text($pdf->marginLeft, $pdf->getY(), 'Day-by-Day Itinerary');
    $pdf->setY($pdf->getY() + 20);
    foreach ($itinerary as $day) {
        $pdf->checkPageBreak(24);
        $pdf->setFont('B', 10.5);
        $label = 'Day ' . (int)$day['day_number'] . ($day['title'] ? ': ' . $day['title'] : '');
        $pdf->writeParagraph($label, $contentWidth, 15);
        if ($day['description']) {
            $pdf->setFont('', 10);
            $pdf->writeParagraph($day['description'], $contentWidth, 14);
        }
        $pdf->setY($pdf->getY() + 8);
    }
    $pdf->setY($pdf->getY() + 6);
}

if ($includesList) {
    $pdf->checkPageBreak(30);
    $pdf->setFont('B', 13);
    $pdf->setTextColor(25, 22, 40);
    $pdf->text($pdf->marginLeft, $pdf->getY(), "What's Included");
    $pdf->setY($pdf->getY() + 20);
    $pdf->setFont('', 10.5);
    foreach ($includesList as $item) {
        $pdf->checkPageBreak(16);
        $pdf->writeParagraph('+  ' . $item, $contentWidth, 15);
    }
    $pdf->setY($pdf->getY() + 10);
}

if ($excludesList) {
    $pdf->checkPageBreak(30);
    $pdf->setFont('B', 13);
    $pdf->text($pdf->marginLeft, $pdf->getY(), "What's Excluded");
    $pdf->setY($pdf->getY() + 20);
    $pdf->setFont('', 10.5);
    foreach ($excludesList as $item) {
        $pdf->checkPageBreak(16);
        $pdf->writeParagraph('-  ' . $item, $contentWidth, 15);
    }
}

$pdf->output(slugify($package['title']) . '-quote.pdf');
