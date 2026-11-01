<?php
require_once __DIR__ . '/database/db.php';

$result = $conn->query("SELECT COUNT(*) AS total FROM site_visits");
$total = $result ? (int) $result->fetch_assoc()['total'] : 0;

header('Content-Type: image/svg+xml; charset=utf-8');

echo '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="30">
  <rect width="150" height="30" rx="5" fill="#24292f"/>
  <text x="75" y="20" text-anchor="middle" fill="#fff" font-family="Arial" font-size="14">Visits: ' . $total . '</text>
</svg>';
