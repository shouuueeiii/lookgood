<?php
// PayMongo callback endpoint (no spaces in URL path)
// Redirects to the real checkout cancel page.

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = '/lookgood/New%20folder/Checkout/cancel.php';
if ($query !== '') {
    $target .= '?' . $query;
}

header('Location: ' . $target);
exit;
