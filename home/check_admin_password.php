<?php
/**
 * DIAGNOSTIC TOOL — delete this file after use!
 * Place in your project root and visit it once in the browser.
 * It will tell you exactly what format the admin password is stored in.
 */

require_once __DIR__ . '/../../config.php';   // adjust path if needed

$email = 'messages@lookgoodframes.com';

$stmt = $conn->prepare("SELECT admin_id, admin_name, email, password FROM admin WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    die("<b>No admin row found for that email.</b>");
}

$stored = $row['password'];
$test   = 'MsgAdmin@2026';

echo "<pre>";
echo "admin_id   : " . $row['admin_id']   . "\n";
echo "admin_name : " . $row['admin_name'] . "\n";
echo "email      : " . $row['email']      . "\n\n";

echo "Stored password value  : " . $stored . "\n";
echo "Stored password length : " . strlen($stored) . "\n\n";

// Detect format
if (password_verify($test, $stored)) {
    echo "FORMAT: PHP password_hash() — already correct.\n";
    echo "password_verify result: TRUE\n";
    echo "\nConclusion: password_verify works fine. The bug is elsewhere.\n";
} elseif ($stored === md5($test)) {
    echo "FORMAT: plain MD5\n";
    echo "\nFIX: Run the UPDATE query below.\n";
} elseif ($stored === sha1($test)) {
    echo "FORMAT: plain SHA1\n";
    echo "\nFIX: Run the UPDATE query below.\n";
} elseif ($stored === $test) {
    echo "FORMAT: PLAIN TEXT (no hashing!)\n";
    echo "\nFIX: Run the UPDATE query below.\n";
} else {
    echo "FORMAT: UNKNOWN — does not match plain text, MD5, SHA1, or password_hash().\n";
    echo "The stored value does not match the test password in any known format.\n";
    echo "Either the password in the DB is different from what you typed, or a custom hash is used.\n";
}

echo "</pre>";

// Show the fix query regardless
$new_hash = password_hash($test, PASSWORD_DEFAULT);
echo "<hr>";
echo "<b>If the format is wrong, run this SQL to fix it:</b><br><br>";
echo "<code style='background:#f4f4f4;padding:8px;display:block'>";
echo "UPDATE admin SET password = '" . $new_hash . "' WHERE email = '" . $email . "';";
echo "</code>";
echo "<br><b style='color:red'>DELETE this file immediately after use!</b>";
?>