<?php
session_start();

// Optional: debug checks (remove after confirming font exists)
if (!file_exists(__DIR__ . '/Spectral-SemiBold.ttf')) {
    die('Font not found at: ' . __DIR__ . '/Spectral-SemiBold.ttf');
}
if (!extension_loaded('gd')) {
    die('GD extension is not enabled.');
}

// 1. Generate random code (6 characters: lowercase + digits, avoid confusing chars)
$characters = 'abcdefghjkmnpqrstuvwxyz23456789';
$length = 6;
$code = '';
for ($i = 0; $i < $length; $i++) {
    $code .= $characters[random_int(0, strlen($characters) - 1)];
}
$_SESSION['captcha_code'] = $code;

// 2. Create blank image (original dimensions: 120x46)
$width = 120;
$height = 46;
$image = imagecreatetruecolor($width, $height);

// 3. Colors
$bgColor    = imagecolorallocate($image, 245, 245, 245);
$textColor  = imagecolorallocate($image, 0, 0, 0);
$noiseColor = imagecolorallocate($image, 100, 100, 100);

imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

// 4. Add random noise (dots) – kept from original file
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $noiseColor);
}

// 5. Draw text with TrueType font – per‑character rotation & sine wave Y
$fonts = [
    __DIR__ . '/Spectral-SemiBold.ttf'   // <-- place your .ttf file here
];
$fontSize    = 22;       // adjusted for 120x46 canvas
$charSpacing = 12;       // space between characters
$startX      = (int)(($width - ($length * $charSpacing)) / 2) + 5;

$amplitude = 6;          // wave height
$frequency = 0.8;        // wave frequency

for ($i = 0; $i < $length; $i++) {
    $char  = $code[$i];
    $angle = 2;          // slight lean (italic‑like)

    $x = $startX + $i * $charSpacing;
    // Y follows a sine wave based on character position
    $y = (int)($height / 1.5) + (int)($amplitude * sin($i * $frequency));

    $font = $fonts[array_rand($fonts)];
    imagettftext($image, $fontSize, $angle, $x, $y, $textColor, $font, $char);
}

// 6. Global sine‑wave warp (produces the wavy/rippled distortion)
$warped = imagecreatetruecolor($width, $height);
$bgColor2 = imagecolorallocate($warped, 245, 245, 245);
imagefill($warped, 0, 0, $bgColor2);

for ($x = 0; $x < $width; $x++) {
    for ($y = 0; $y < $height; $y++) {
        $srcX = (int)($x + sin($y / 8) * 4);   // horizontal ripple
        $srcY = (int)($y + sin($x / 8) * 3);   // vertical ripple

        if ($srcX >= 0 && $srcX < $width && $srcY >= 0 && $srcY < $height) {
            $color = imagecolorat($image, $srcX, $srcY);
            imagesetpixel($warped, $x, $y, $color);
        }
    }
}

// 7. Output as PNG
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
imagepng($warped);

imagedestroy($image);
imagedestroy($warped);
?>