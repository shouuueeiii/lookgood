<?php


$query = $_SERVER['QUERY_STRING'] ?? '';
$target = '/lookgood/user/cancel.php';
if ($query !== '') {
    $target .= '?' . $query;
}

header('Location: ' . $target);
exit;
