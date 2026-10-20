<?php
// Maintenance/build task only. Never downloads assets during a web request or migration.
$url = 'https://inecnigeria.org/_nuxt/nigeria-democratic-congress_logo.BRx6oQl5.jpeg';
$curl = curl_init($url);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 45,
    CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_USERAGENT => 'CampaignManager-asset-preparation/1.1.4',
]);
$bytes = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
if ($status !== 200 || ! is_string($bytes) || strlen($bytes) > 5242880) {
    throw new RuntimeException('Could not download the official INEC NDC logo.');
}
$image = @imagecreatefromstring($bytes);
if (! $image || imagesx($image) > 4096 || imagesy($image) > 4096) {
    throw new RuntimeException('INEC did not return a supported logo image.');
}
foreach (['public/assets/img/parties/NDC.png', 'public/uploads/system_images/politicalparty/NDC-INEC-2026-10.png'] as $relative) {
    $path = dirname(__DIR__).'/'.$relative;
    if (! is_dir(dirname($path))) { mkdir(dirname($path), 0755, true); }
    if (! imagepng($image, $path)) { throw new RuntimeException('Cannot store the NDC logo.'); }
    echo $relative.' '.hash_file('sha256', $path).PHP_EOL;
}
imagedestroy($image);
