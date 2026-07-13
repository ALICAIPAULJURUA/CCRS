<?php
// Create default placeholder image
$width = 1920;
$height = 800;

$image = imagecreatetruecolor($width, $height);

// Colors
$dark_blue = imagecolorallocate($image, 44, 62, 80);
$light_blue = imagecolorallocate($image, 52, 152, 219);
$white = imagecolorallocate($image, 255, 255, 255);

// Fill background
imagefill($image, 0, 0, $dark_blue);

// Add gradient effect
for ($i = 0; $i < $height; $i++) {
    $color = imagecolorallocate($image, 44 + ($i * 0.01), 62 + ($i * 0.01), 80 + ($i * 0.05));
    imageline($image, 0, $i, $width, $i, $color);
}

// Add text
$text = "CCRS";
$font_size = 80;
$font_path = __DIR__ . '/assets/fonts/arial.ttf';

// If no font, use imagestring
$text_width = imagefontwidth(5) * strlen($text);
$text_x = ($width - $text_width) / 2;
$text_y = ($height / 2) - 20;

imagestring($image, 5, $text_x, $text_y, $text, $white);

$subtext = "Community Crime Reporting System";
$sub_width = imagefontwidth(3) * strlen($subtext);
$sub_x = ($width - $sub_width) / 2;
$sub_y = $text_y + 40;

imagestring($image, 3, $sub_x, $sub_y, $subtext, $white);

// Save image
$output_path = __DIR__ . '/assets/images/default-slide.jpg';
imagejpeg($image, $output_path, 80);
imagedestroy($image);

echo "Default image created at: " . $output_path;
?>