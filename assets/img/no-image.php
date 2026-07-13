<?php
// Generate a simple placeholder image
header('Content-Type: image/png');
$img = imagecreatetruecolor(100, 100);
$bg  = imagecolorallocate($img, 240, 240, 240);
$fg  = imagecolorallocate($img, 180, 180, 180);
imagefill($img, 0, 0, $bg);
imagestring($img, 3, 28, 42, 'No Img', $fg);
imagepng($img);
imagedestroy($img);
