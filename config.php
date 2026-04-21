<?php
define('ADMIN_EMAIL',    'admin@lookgoodframes.com');
define('ADMIN_PASSWORD', password_hash('admin123', PASSWORD_DEFAULT));

define('PAYMONGO_SECRET_KEY', 'sk_test_TLDQzZkcyxNS7wVpj5tV2UrR'); 
define('PAYMONGO_PUBLIC_KEY', 'pk_test_oN2hWsj9hCdUywVKhFxWDvmS'); 
define('SITE_URL', 'http://localhost/lookgood/New%20folder/Checkout');

$host = "localhost";
$user = "root";
$password = "";
$database = "wst";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
