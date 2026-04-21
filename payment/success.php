<?php

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = '/lookgood/New%20folder/Checkout/success.php';
if ($query !== '') {
    $target .= '?' . $query;
}

header('Location: ' . $target);
exit;
